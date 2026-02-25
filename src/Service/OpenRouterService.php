<?php
// src/Service/OpenRouterService.php

namespace App\Service;

use App\Service\AI\AiServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * AI chat-completion client backed by OpenRouter.ai (Responses API).
 *
 * Implements {@see AiServiceInterface} with the same contract as
 * {@see OpenAIService}, making it a drop-in replacement configurable
 * through a single DI alias in services.yaml.
 *
 * API reference: https://openrouter.ai/docs/api/api-reference/responses/create-responses
 */
class OpenRouterService implements AiServiceInterface
{
    private const API_URL = 'https://openrouter.ai/api/v1/responses';

    /**
     * Default model used when no model is provided.
     * Override per-call or change this constant to switch models globally.
     */
    private const DEFAULT_MODEL = 'arcee-ai/trinity-large-preview:free';
    private const DEFAULT_TEMPERATURE = 0.4;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $openRouterApiKey,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Send a chat-style prompt to OpenRouter using the Responses API
     * and return the raw assistant text response.
     *
     * @param string $systemPrompt System-level instruction for the assistant
     * @param string $userPrompt   User-level content / question
     * @param string $model        OpenRouter model identifier (empty = provider default)
     * @param float  $temperature  Sampling temperature 0–2 (default: 0.4)
     * @return string Raw assistant message content
     *
     * @throws \RuntimeException on HTTP or API error
     */
    public function chat(
        string $systemPrompt,
        string $userPrompt,
        string $model = self::DEFAULT_MODEL,
        float $temperature = self::DEFAULT_TEMPERATURE,
    ): string {
        $model = $model !== '' ? $model : self::DEFAULT_MODEL;

        $response = $this->client->request('POST', self::API_URL, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->openRouterApiKey,
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'model'       => $model,
                'temperature' => $temperature,
                'input'       => [
                    [
                        'type'    => 'message',
                        'role'    => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'type'    => 'message',
                        'role'    => 'user',
                        'content' => $userPrompt,
                    ],
                ],
            ],
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            $this->logger->error('OpenRouter API HTTP error', [
                'status_code' => $statusCode,
                'body'        => $response->getContent(false),
            ]);

            throw new \RuntimeException(
                sprintf('OpenRouter API returned HTTP %d', $statusCode)
            );
        }

        $data = $response->toArray(false);

        if (!empty($data['error'])) {
            throw new \RuntimeException(
                'OpenRouter API error: ' . ($data['error']['message'] ?? 'Unknown error')
            );
        }

        $content = $this->extractContent($data);

        $this->logger->info('OpenRouter model raw response', [
            'model'          => $model,
            'usage'          => $data['usage'] ?? null,
            'status'         => $data['status'] ?? null,
            'content_length' => strlen($content),
            'content'        => $content,
        ]);

        return $content;
    }

    /**
     * Same as chat() but attempts to decode the response as JSON.
     *
     * Handles prose, markdown code-fences, and DeepSeek-R1 <think> blocks.
     *
     * @param string $systemPrompt System-level instruction
     * @param string $userPrompt   User-level content
     * @param string $model        OpenRouter model identifier (empty = provider default)
     * @param float  $temperature  Sampling temperature
     * @return array Decoded JSON, or ['error' => '<message>', 'raw' => '...'] on failure
     *
     * @throws \RuntimeException on HTTP or API error
     */
    public function chatJson(
        string $systemPrompt,
        string $userPrompt,
        string $model = self::DEFAULT_MODEL,
        float $temperature = self::DEFAULT_TEMPERATURE,
    ): array {
        $content = $this->chat($systemPrompt, $userPrompt, $model, $temperature);

        return $this->parseJson($content);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Extract the plain-text content from a Responses API payload.
     *
     * Response shape:
     * {
     *   "output": [
     *     {
     *       "role": "assistant",
     *       "type": "message",
     *       "content": [
     *         { "type": "output_text", "text": "..." }
     *       ]
     *     }
     *   ]
     * }
     */
    private function extractContent(array $data): string
    {
        $output = $data['output'] ?? [];

        foreach ($output as $item) {
            if (($item['type'] ?? '') !== 'message') {
                continue;
            }

            foreach ($item['content'] ?? [] as $block) {
                if (($block['type'] ?? '') === 'output_text') {
                    return $block['text'] ?? '';
                }
            }
        }

        $this->logger->warning('OpenRouter: could not find output_text in response', [
            'data' => $data,
        ]);

        return '';
    }

    /**
     * Attempt to decode JSON from the AI text response.
     * Handles plain JSON, markdown code fences, inline JSON blocks,
     * and DeepSeek-R1 <think>...</think> reasoning traces.
     */
    private function parseJson(string $content): array
    {
        $this->logger->debug('OpenRouter raw response content', [
            'length'  => strlen($content),
            'preview' => substr($content, 0, 500),
        ]);

        // 0) Strip DeepSeek-R1 <think>...</think> reasoning blocks
        $stripped = preg_replace('/<think>.*?<\/think>/s', '', $content);
        $stripped = trim($stripped ?? $content);

        // 1) Direct decode
        $json = json_decode($stripped, true);
        if (is_array($json)) {
            return $json;
        }

        // 2) Strip markdown code fences: ```json ... ``` or ``` ... ```
        if (preg_match('/```(?:json)?\s*(\{.*?\}|\[.*?\])\s*```/s', $stripped, $matches)) {
            $json = json_decode($matches[1], true);
            if (is_array($json)) {
                return $json;
            }
        }

        // 3) Extract first JSON object or array from the text
        if (preg_match('/(\{.*\}|\[.*\])/s', $stripped, $matches)) {
            $json = json_decode($matches[1], true);
            if (is_array($json)) {
                return $json;
            }
        }

        $this->logger->error('Failed to parse JSON from OpenRouter response', [
            'raw_content'      => $content,
            'stripped_content' => $stripped,
            'json_last_error'  => json_last_error_msg(),
        ]);

        return ['error' => 'Could not parse JSON from OpenRouter response', 'raw' => $stripped];
    }
}

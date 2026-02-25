<?php
// src/Service/OpenAIService.php

namespace App\Service;

use App\Service\AI\AiServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * OpenAI chat-completion client.
 *
 * Implements {@see AiServiceInterface} so it can be swapped transparently
 * with other AI providers (e.g. OpenRouterService) via the DI container.
 */
class OpenAIService implements AiServiceInterface
{
    private const API_URL = 'https://api.openai.com/v1/chat/completions';
    private const DEFAULT_MODEL = 'gpt-4';
    private const DEFAULT_TEMPERATURE = 0.2;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $openaiApiKey,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Send a chat completion request to OpenAI and return the raw text response.
     *
     * @param string $systemPrompt  System-level instruction for the assistant
     * @param string $userPrompt    User-level content / question
     * @param string $model         OpenAI model to use (default: gpt-4)
     * @param float  $temperature   Sampling temperature 0–2 (default: 0.2)
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
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'model'       => $model,
                'messages'    => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userPrompt],
                ],
                'temperature' => $temperature,
            ],
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            throw new \RuntimeException(
                sprintf('OpenAI API returned HTTP %d', $statusCode)
            );
        }

        $data = $response->toArray(false);

        if (!empty($data['error'])) {
            throw new \RuntimeException(
                'OpenAI API error: ' . ($data['error']['message'] ?? 'Unknown error')
            );
        }

        $content = $data['choices'][0]['message']['content'] ?? '';

        $this->logger->info('AI model raw response', [
            'model'           => $model,
            'usage'           => $data['usage'] ?? null,
            'finish_reason'   => $data['choices'][0]['finish_reason'] ?? null,
            'content_length'  => strlen($content),
            'content'         => $content,
        ]);

        return $content;
    }

    /**
     * Same as chat() but attempts to decode the response as JSON.
     *
     * Tries to extract a JSON object or array even when the model wraps it
     * in prose or markdown code fences.
     *
     * @param string $systemPrompt  System-level instruction
     * @param string $userPrompt    User-level content
     * @param string $model         OpenAI model to use
     * @param float  $temperature   Sampling temperature
     * @return array Decoded JSON array, or ['error' => '<message>'] on failure
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
     * Attempt to decode JSON from an OpenAI text response.
     * Handles plain JSON, markdown code fences, inline JSON blocks,
     * and DeepSeek-R1 <think>...</think> reasoning traces.
     */
    private function parseJson(string $content): array
    {
        $this->logger->debug('OpenAI raw response content', [
            'length' => strlen($content),
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

        $this->logger->error('Failed to parse JSON from AI response', [
            'raw_content'     => $content,
            'stripped_content' => $stripped,
            'json_last_error' => json_last_error_msg(),
        ]);

        return ['error' => 'Could not parse JSON from OpenAI response', 'raw' => $stripped];
    }
}

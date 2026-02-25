<?php
// src/Service/OpenAIService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Generic, reusable OpenAI chat-completion client.
 *
 * All domain-specific services (shopping list, meal import, etc.) should
 * depend on this service and NOT call the OpenAI API directly.
 */
class OpenAIService
{
    private const API_URL = 'https://api.openai.com/v1/chat/completions';
    private const DEFAULT_MODEL = 'gpt-4';
    private const DEFAULT_TEMPERATURE = 0.2;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $openaiApiKey,
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

        return $data['choices'][0]['message']['content'] ?? '';
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
     * Handles plain JSON, markdown code fences and inline JSON blocks.
     */
    private function parseJson(string $content): array
    {
        // 1) Direct decode
        $json = json_decode($content, true);
        if (is_array($json)) {
            return $json;
        }

        // 2) Strip markdown code fences: ```json ... ``` or ``` ... ```
        if (preg_match('/```(?:json)?\s*(\{.*?\}|\[.*?\])\s*```/s', $content, $matches)) {
            $json = json_decode($matches[1], true);
            if (is_array($json)) {
                return $json;
            }
        }

        // 3) Extract first JSON object or array from the text
        if (preg_match('/(\{.*\}|\[.*\])/s', $content, $matches)) {
            $json = json_decode($matches[1], true);
            if (is_array($json)) {
                return $json;
            }
        }

        return ['error' => 'Could not parse JSON from OpenAI response', 'raw' => $content];
    }
}

<?php
// src/Service/AI/AiServiceInterface.php

namespace App\Service\AI;

/**
 * Contract for AI chat-completion providers (OpenAI, OpenRouter, etc.).
 *
 * All domain services must depend on this interface so the underlying
 * provider can be swapped by changing a single DI alias in services.yaml.
 */
interface AiServiceInterface
{
    /**
     * Send a chat-style prompt and return the raw assistant text response.
     *
     * An empty $model string means "use the provider's default model".
     *
     * @param string $systemPrompt System-level instruction for the assistant
     * @param string $userPrompt   User-level content / question
     * @param string $model        Model identifier (empty = provider default)
     * @param float  $temperature  Sampling temperature 0–2 (default: 0.2)
     * @return string Raw assistant message content
     *
     * @throws \RuntimeException on HTTP or API error
     */
    public function chat(
        string $systemPrompt,
        string $userPrompt,
        string $model = '',
        float $temperature = 0.2,
    ): string;

    /**
     * Same as chat() but decodes the response as JSON.
     *
     * Handles prose / markdown code-fence wrappers transparently.
     * An empty $model string means "use the provider's default model".
     *
     * @param string $systemPrompt System-level instruction
     * @param string $userPrompt   User-level content
     * @param string $model        Model identifier (empty = provider default)
     * @param float  $temperature  Sampling temperature
     * @return array Decoded JSON, or ['error' => '<message>', 'raw' => '...'] on failure
     *
     * @throws \RuntimeException on HTTP or API error
     */
    public function chatJson(
        string $systemPrompt,
        string $userPrompt,
        string $model = '',
        float $temperature = 0.2,
    ): array;
}

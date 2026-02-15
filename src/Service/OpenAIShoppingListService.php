<?php
// src/Service/OpenAIShoppingListService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAIShoppingListService
{
    private $client;
    private $apiKey;

    public function __construct(HttpClientInterface $client, string $openaiApiKey)
    {
        $this->client = $client;
        $this->apiKey = $openaiApiKey;
    }

    /**
     * Generate a shopping list from meal data using OpenAI
     *
     * @param array $meals Array of meals with portions, calories, etc.
     * @return array Parsed shopping list
     */
    public function generateShoppingList(array $meals): array
    {
        $prompt = $this->buildPrompt($meals);
        $response = $this->client->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant that generates shopping lists from meal plans.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.2,
            ],
        ]);
        $data = $response->toArray(false);
        $content = $data['choices'][0]['message']['content'] ?? '';
        return $this->parseShoppingList($content);
    }

    private function buildPrompt(array $meals): string
    {
        // Build a detailed prompt for OpenAI
        $prompt = "Given the following meal plan, generate a shopping list with quantities for each ingredient. Consider portions and calories.\n";
        $prompt .= json_encode($meals, JSON_PRETTY_PRINT);
        $prompt .= "\nReturn the list grouped by food type (proteins, carbs, fats, etc) in JSON format.";
        return $prompt;
    }

    private function parseShoppingList(string $content): array
    {
        // Try to decode JSON from OpenAI response
        $json = json_decode($content, true);
        if (is_array($json)) {
            return $json;
        }
        // Fallback: try to extract JSON from text
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $json = json_decode($matches[0], true);
            if (is_array($json)) {
                return $json;
            }
        }
        return ['error' => 'Could not parse shopping list'];
    }
}

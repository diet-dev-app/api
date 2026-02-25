<?php
// src/Service/OpenAIShoppingListService.php

namespace App\Service;

/**
 * Shopping-list-specific service.
 * Delegates all OpenAI communication to the generic OpenAIService.
 */
class OpenAIShoppingListService
{
    public function __construct(private readonly OpenAIService $openAI) {}

    /**
     * Generate a shopping list from meal data using OpenAI.
     *
     * @param array $meals Array of meals with meal options, portions, calories, etc.
     * @return array Parsed shopping list grouped by food type
     */
    public function generateShoppingList(array $meals): array
    {
        $prompt = $this->buildPrompt($meals);

        return $this->openAI->chatJson(
            'You are a helpful assistant that generates shopping lists from meal plans.',
            $prompt,
        );
    }

    /**
     * Build the user prompt with the meal plan data.
     */
    private function buildPrompt(array $meals): string
    {
        $prompt  = "Given the following meal plan, generate a shopping list with quantities for each ingredient. Consider portions and calories.\n";
        $prompt .= json_encode($meals, JSON_PRETTY_PRINT);
        $prompt .= "\nReturn the list grouped by food type (proteins, carbs, fats, etc) in JSON format.";

        return $prompt;
    }
}

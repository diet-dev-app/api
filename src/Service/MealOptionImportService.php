<?php
// src/Service/MealOptionImportService.php

namespace App\Service;

use App\Entity\Ingredient;
use App\Entity\MealOption;
use App\Entity\MealTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Analyses nutritionist diet documents via OpenAI and persists the extracted
 * MealOption + Ingredient records to the database.
 *
 * OpenAI is responsible for:
 *   1. Identifying every distinct meal option in the document.
 *   2. Classifying each meal by meal time (breakfast, lunch, snack, dinner).
 *   3. Estimating the total calories for each meal option.
 *   4. Inferring the full ingredient list with realistic quantities.
 */
class MealOptionImportService
{
    /** Valid meal-time names as stored in the MealTime entity. */
    private const VALID_MEAL_TIMES = ['breakfast', 'lunch', 'snack', 'dinner'];

    /** Valid ingredient units. */
    private const VALID_UNITS = ['g', 'ml', 'unit', 'tbsp', 'tsp', 'cup'];

    /** OpenAI system prompt for nutritionist diet extraction. */
    private const SYSTEM_PROMPT = <<<'PROMPT'
You are a professional nutrition data extraction assistant.
You will receive the text of a diet plan written by a nutritionist.

Your job is to:
1. Identify every distinct meal option mentioned in the document.
2. Classify each meal into its meal time (breakfast, lunch, snack, dinner).
3. ESTIMATE the total calories for each meal option based on standard
   nutritional databases. The nutritionist document usually does NOT
   include calorie counts — you must calculate them.
4. INFER the full ingredient list with realistic quantities for each meal.
   Nutritionist documents typically only name the dish (e.g. "Greek yogurt
   with granola and berries") without listing every ingredient. You must
   decompose each meal into its individual ingredients with estimated
   weights/volumes.

Return ONLY valid JSON with this exact structure:
{
  "meal_options": [
    {
      "name": "Greek yogurt with granola and berries",
      "description": "Healthy breakfast option recommended by nutritionist",
      "meal_time": "breakfast",
      "estimated_calories": 320,
      "ingredients": [
        { "name": "Greek yogurt", "quantity": 200, "unit": "g" },
        { "name": "granola",      "quantity": 40,  "unit": "g" },
        { "name": "mixed berries","quantity": 80,  "unit": "g" }
      ]
    }
  ]
}

Rules:
- meal_time must be one of: breakfast, lunch, snack, dinner
- Always estimate quantities even when the document does not specify them;
  use standard single-serving portions.
- Always estimate calories based on the inferred ingredients and quantities.
- unit must be one of: g, ml, unit, tbsp, tsp, cup
- Do not invent meals that are not mentioned in the document.
- If a meal is ambiguous, use the most common healthy interpretation.
- Do NOT include any text outside the JSON object.
PROMPT;

    public function __construct(
        private readonly OpenAIService $openAI,
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Parse a nutritionist diet text, extract meal options via OpenAI and
     * persist them to the database.
     *
     * @param string $dietText Plain text extracted from the uploaded document
     * @return array{imported: int, meal_options: array} Summary with created options
     *
     * @throws \RuntimeException When OpenAI returns an unusable response
     */
    public function importFromText(string $dietText): array
    {
        $aiResponse = $this->openAI->chatJson(
            self::SYSTEM_PROMPT,
            "Nutritionist diet document:\n\n" . $dietText,
        );

        if (isset($aiResponse['error'])) {
            throw new \RuntimeException(
                'OpenAI could not extract meal options: ' . $aiResponse['error']
            );
        }

        if (empty($aiResponse['meal_options']) || !is_array($aiResponse['meal_options'])) {
            throw new \RuntimeException(
                'OpenAI response does not contain a valid "meal_options" array.'
            );
        }

        $created = [];

        foreach ($aiResponse['meal_options'] as $item) {
            $option = $this->createMealOption($item);
            if ($option !== null) {
                $created[] = $option;
            }
        }

        $this->em->flush();

        return [
            'imported'     => count($created),
            'meal_options' => array_map([$this, 'serializeOption'], $created),
        ];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Build and persist a single MealOption from the AI-returned data.
     * Returns null if the item is invalid and should be skipped.
     */
    private function createMealOption(array $item): ?MealOption
    {
        if (empty($item['name'])) {
            return null;
        }

        $mealTimeName = $this->normaliseMealTime($item['meal_time'] ?? '');
        $mealTime     = $this->em->getRepository(MealTime::class)
            ->findOneBy(['name' => $mealTimeName]);

        if (!$mealTime) {
            // Fall back to the first available MealTime when not matched
            $mealTime = $this->em->getRepository(MealTime::class)->findOneBy([]);
            if (!$mealTime) {
                return null; // No meal times configured at all – skip
            }
        }

        $option = new MealOption();
        $option->setName($item['name']);
        $option->setDescription($item['description'] ?? null);
        $option->setMealTime($mealTime);
        $option->setEstimatedCalories(
            isset($item['estimated_calories']) ? (float) $item['estimated_calories'] : null
        );

        foreach ($item['ingredients'] ?? [] as $ingredientData) {
            $ingredient = $this->createIngredient($ingredientData);
            if ($ingredient !== null) {
                $option->addIngredient($ingredient);
            }
        }

        $this->em->persist($option);

        return $option;
    }

    /**
     * Build an Ingredient entity from the AI-returned ingredient data.
     * Returns null when required fields are missing.
     */
    private function createIngredient(array $data): ?Ingredient
    {
        if (empty($data['name']) || !isset($data['quantity']) || empty($data['unit'])) {
            return null;
        }

        $unit = $this->normaliseUnit($data['unit']);

        $ingredient = new Ingredient();
        $ingredient->setName($data['name']);
        $ingredient->setQuantity((float) $data['quantity']);
        $ingredient->setUnit($unit);

        return $ingredient;
    }

    /**
     * Normalise a meal-time name to one of the accepted values.
     * Falls back to 'lunch' when the value is not recognised.
     */
    private function normaliseMealTime(string $input): string
    {
        $lower = strtolower(trim($input));
        if (in_array($lower, self::VALID_MEAL_TIMES, true)) {
            return $lower;
        }
        // Simple mapping for common synonyms
        return match (true) {
            str_contains($lower, 'break') || str_contains($lower, 'morning') => 'breakfast',
            str_contains($lower, 'snack') || str_contains($lower, 'merienda') => 'snack',
            str_contains($lower, 'dinner') || str_contains($lower, 'supper')  => 'dinner',
            default                                                             => 'lunch',
        };
    }

    /**
     * Normalise an ingredient unit to one of the accepted values.
     * Falls back to 'g' when not recognised.
     */
    private function normaliseUnit(string $input): string
    {
        $lower = strtolower(trim($input));
        return in_array($lower, self::VALID_UNITS, true) ? $lower : 'g';
    }

    /**
     * Serialize a MealOption entity to an array for the API response.
     */
    private function serializeOption(MealOption $option): array
    {
        return [
            'id'                 => $option->getId(),
            'name'               => $option->getName(),
            'description'        => $option->getDescription(),
            'estimated_calories' => $option->getEstimatedCalories(),
            'meal_time'          => $option->getMealTime() ? [
                'id'    => $option->getMealTime()->getId(),
                'name'  => $option->getMealTime()->getName(),
                'label' => $option->getMealTime()->getLabel(),
            ] : null,
            'ingredients' => array_map(
                fn(Ingredient $i) => [
                    'id'       => $i->getId(),
                    'name'     => $i->getName(),
                    'quantity' => $i->getQuantity(),
                    'unit'     => $i->getUnit(),
                ],
                $option->getIngredients()->toArray()
            ),
        ];
    }
}

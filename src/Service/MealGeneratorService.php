<?php

namespace App\Service;

use App\Entity\MealOption;
use App\Entity\User;
use App\Service\AI\AiServiceInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Generates a daily meal plan by asking OpenAI to select the best
 * combination of existing MealOption records to meet the user's caloric goal.
 *
 * Only uses meal options already stored in the database — it never invents
 * new options.
 */
class MealGeneratorService
{
    public function __construct(
        private readonly AiServiceInterface $openAI,
        private readonly EntityManagerInterface $em,
        private readonly CaloricGoalService $caloricGoalService,
    ) {}

    /**
     * Generate a meal plan for a specific date.
     *
     * @param User               $user           Authenticated user
     * @param \DateTimeImmutable $date           Target date for the plan
     * @param int|null           $targetCalories Override; falls back to active goal
     * @return array Structured meal plan (see action plan for response shape)
     *
     * @throws \RuntimeException if no caloric target can be determined
     * @throws \RuntimeException on OpenAI API failure
     * @throws \UnexpectedValueException if OpenAI returns invalid data
     */
    public function generateMealPlan(
        User $user,
        \DateTimeImmutable $date,
        ?int $targetCalories = null,
    ): array {
        // 1. Resolve caloric target
        if ($targetCalories === null) {
            $goal = $this->caloricGoalService->getActiveGoal($user, $date);
            if ($goal === null) {
                throw new \RuntimeException(
                    'No active caloric goal found for this date. '
                    . 'Please create a caloric goal or provide target_calories.'
                );
            }
            $targetCalories = $goal->getDailyCalories();
        }

        // 2. Fetch all MealOptions with their MealTime and Ingredients
        $options = $this->em->getRepository(MealOption::class)
            ->createQueryBuilder('o')
            ->leftJoin('o.mealTime', 't')->addSelect('t')
            ->leftJoin('o.ingredients', 'i')->addSelect('i')
            ->getQuery()
            ->getResult();

        if (empty($options)) {
            throw new \RuntimeException(
                'No meal options are available in the catalogue. '
                . 'Please import meal options first.'
            );
        }

        // 3. Build catalogue for the prompt
        $catalogue = array_map(
            fn(MealOption $o) => [
                'id'                 => $o->getId(),
                'name'               => $o->getName(),
                'description'        => $o->getDescription(),
                'meal_time'          => $o->getMealTime()?->getName() ?? 'unknown',
                'estimated_calories' => $o->getEstimatedCalories(),
                'ingredients'        => array_map(
                    fn($i) => ['name' => $i->getName(), 'quantity' => $i->getQuantity(), 'unit' => $i->getUnit()],
                    $o->getIngredients()->toArray()
                ),
            ],
            $options
        );

        // 4. Build system and user prompts
        $systemPrompt = <<<PROMPT
You are a professional dietitian meal planner.
You will receive:
1. A daily caloric target in kcal.
2. A catalogue of available meal options, each with: id, name, meal_time,
   estimated_calories, and ingredients.

Your job is to:
1. Select ONE meal option for each meal time (breakfast, lunch, snack, dinner)
   from the catalogue.
2. The total calories of all selected meals must be as close as possible to
   the daily target without exceeding it by more than 10%.
3. Prioritise nutritional variety and balance across macronutrients.
4. If no combination can match the target within ±10%, select the closest
   combination and explain the gap.

Return ONLY valid JSON with this exact structure (no prose, no code fences):
{
  "target_calories": 2000,
  "total_calories": 1950,
  "difference": -50,
  "meals": [
    {
      "meal_time": "breakfast",
      "meal_option_id": 5,
      "meal_option_name": "Greek yogurt with granola",
      "estimated_calories": 320,
      "reason": "High protein breakfast within calorie budget"
    }
  ],
  "notes": "Overall well-balanced plan..."
}

Rules:
- Only use meal_option_ids from the provided catalogue. Never invent new IDs.
- Each meal_time should have exactly one option (if available in the catalogue).
- If a meal_time has no options in the catalogue, skip it and mention it in notes.
PROMPT;

        $userPrompt = sprintf(
            "Daily caloric target: %d kcal\n\nAvailable meal options catalogue:\n%s",
            $targetCalories,
            json_encode($catalogue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        // 5. Call OpenAI
        $result = $this->openAI->chatJson($systemPrompt, $userPrompt);

        // 6. Validate
        if (isset($result['error'])) {
            throw new \UnexpectedValueException(
                'OpenAI could not generate a valid meal plan: ' . $result['error']
            );
        }

        if (empty($result['meals']) || !is_array($result['meals'])) {
            throw new \UnexpectedValueException(
                'OpenAI returned an unexpected response structure for the meal plan.'
            );
        }

        // 7. Add context fields
        $result['date'] = $date->format('Y-m-d');

        return $result;
    }
}

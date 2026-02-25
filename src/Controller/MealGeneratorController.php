<?php

namespace App\Controller;

use App\Entity\Meal;
use App\Entity\MealOption;
use App\Entity\User;
use App\Service\MealGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Endpoint for AI-powered daily meal plan generation.
 *
 * POST /api/meals/generate
 *   Asks OpenAI to select the best combination of existing MealOptions
 *   to meet the user's daily caloric goal.
 *
 * Optional ?save=true persists the generated plan as a Meal entity.
 */
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class MealGeneratorController extends AbstractController
{
    public function __construct(
        private readonly MealGeneratorService $generator,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('/api/meals/generate', name: 'api_meals_generate', methods: ['POST'])]
    public function generate(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];

        // Validate required field
        if (empty($data['date'])) {
            return $this->json(['error' => 'date is required (format: Y-m-d).'], 400);
        }

        try {
            $date = new \DateTimeImmutable($data['date']);
        } catch (\Exception) {
            return $this->json(['error' => 'Invalid date format. Use Y-m-d.'], 400);
        }

        $targetCalories = isset($data['target_calories'])
            ? (int) $data['target_calories']
            : null;

        // Generate plan
        try {
            $plan = $this->generator->generateMealPlan($user, $date, $targetCalories);
        } catch (\RuntimeException $e) {
            // No active goal / no meal options
            return $this->json(['error' => $e->getMessage()], 404);
        } catch (\UnexpectedValueException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return $this->json(['error' => 'OpenAI API failure: ' . $e->getMessage()], 500);
        }

        // Optional: persist as Meal
        $save = filter_var($request->query->get('save', 'false'), FILTER_VALIDATE_BOOLEAN);
        if ($save) {
            $plan['saved_meal'] = $this->saveMeal($user, $date, $plan);
        }

        return $this->json($plan, 201);
    }

    /**
     * Persists the generated plan as a Meal entity with associated MealOptions.
     *
     * @param User               $user
     * @param \DateTimeImmutable $date
     * @param array              $plan Generated plan from MealGeneratorService
     * @return array Serialised Meal summary
     */
    private function saveMeal(User $user, \DateTimeImmutable $date, array $plan): array
    {
        $meal = new Meal();
        $meal->setUser($user)
             ->setDate($date)
             ->setName('AI Generated Plan — ' . $date->format('Y-m-d'))
             ->setCalories($plan['total_calories'] ?? 0);

        $mealOptionRepo = $this->em->getRepository(MealOption::class);

        foreach ($plan['meals'] ?? [] as $entry) {
            $option = $mealOptionRepo->find((int) ($entry['meal_option_id'] ?? 0));
            if ($option !== null) {
                $meal->addMealOption($option);
            }
        }

        $this->em->persist($meal);
        $this->em->flush();

        return [
            'id'   => $meal->getId(),
            'name' => $meal->getName(),
            'date' => $meal->getDate()->format('Y-m-d'),
        ];
    }
}

<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class MealController extends AbstractController
{
    #[Route('/api/meals', name: 'api_meals_list', methods: ['GET'])]
    public function list(\Doctrine\ORM\EntityManagerInterface $em): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        $meals = $user->getMeals();
        $data = [];
        foreach ($meals as $meal) {
            $data[] = $this->serializeMeal($meal);
        }
        return $this->json($data);
    }

    #[Route('/api/meals', name: 'api_meals_create', methods: ['POST'])]
    public function create(\Symfony\Component\HttpFoundation\Request $request, \Doctrine\ORM\EntityManagerInterface $em, \Symfony\Component\Validator\Validator\ValidatorInterface $validator): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['date'])) {
            return $this->json(['error' => 'date is required'], 400);
        }

        $dateObj = new \DateTimeImmutable($data['date']);

        // Check if a Meal already exists for this user + date (same day)
        $existingMeal = $em->getRepository(\App\Entity\Meal::class)->findOneBy([
            'user' => $user,
            'date' => $dateObj,
        ]);

        // If no exact match, search by date range (start/end of the day)
        if (!$existingMeal) {
            $startOfDay = $dateObj->setTime(0, 0, 0);
            $endOfDay   = $dateObj->setTime(23, 59, 59);
            $existingMeal = $em->createQueryBuilder()
                ->select('m')
                ->from(\App\Entity\Meal::class, 'm')
                ->where('m.user = :user')
                ->andWhere('m.date >= :start')
                ->andWhere('m.date <= :end')
                ->setParameter('user', $user)
                ->setParameter('start', $startOfDay)
                ->setParameter('end', $endOfDay)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        $isNew = false;
        if ($existingMeal) {
            $meal = $existingMeal;
        } else {
            $isNew = true;
            $meal = new \App\Entity\Meal();
            $meal->setUser($user);
            $meal->setDate($dateObj);
        }

        if (isset($data['name'])) {
            $meal->setName($data['name']);
        } elseif ($isNew) {
            $meal->setName('');
        }
        if (isset($data['calories'])) {
            $meal->setCalories((int)$data['calories']);
        } elseif ($isNew) {
            $meal->setCalories(0);
        }
        if (array_key_exists('notes', $data)) {
            $meal->setNotes($data['notes']);
        } elseif ($isNew) {
            $meal->setNotes(null);
        }

        // Sync MealOptions by IDs (full replacement)
        if (array_key_exists('meal_option_ids', $data)) {
            $meal->getMealOptions()->clear();
            $mealOptionRepo = $em->getRepository(\App\Entity\MealOption::class);
            foreach ((array) $data['meal_option_ids'] as $optionId) {
                $option = $mealOptionRepo->find((int) $optionId);
                if ($option) {
                    $meal->addMealOption($option);
                }
            }
        }

        $errors = $validator->validate($meal);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        if ($isNew) {
            $em->persist($meal);
        }
        $em->flush();

        return $this->json($this->serializeMeal($meal), $isNew ? 201 : 200);
    }

    #[Route('/api/meals/{id}', name: 'api_meals_update', methods: ['PUT'])]
    public function update(int $id, \Symfony\Component\HttpFoundation\Request $request, \Doctrine\ORM\EntityManagerInterface $em, \Symfony\Component\Validator\Validator\ValidatorInterface $validator): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        $meal = $em->getRepository(\App\Entity\Meal::class)->find($id);
        if (!$meal || $meal->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Meal not found'], 404);
        }
        $data = json_decode($request->getContent(), true);
        if (isset($data['name'])) $meal->setName($data['name']);
        if (isset($data['calories'])) $meal->setCalories((int)$data['calories']);
        if (isset($data['date'])) $meal->setDate(new \DateTimeImmutable($data['date']));
        if (array_key_exists('notes', $data)) $meal->setNotes($data['notes']);

        // Sync meal_option_ids if provided (full replacement)
        if (array_key_exists('meal_option_ids', $data)) {
            $meal->getMealOptions()->clear();
            $mealOptionRepo = $em->getRepository(\App\Entity\MealOption::class);
            foreach ((array) $data['meal_option_ids'] as $optId) {
                $option = $mealOptionRepo->find((int) $optId);
                if ($option !== null) {
                    $meal->addMealOption($option);
                }
            }
        }

        $errors = $validator->validate($meal);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $em->flush();

        return $this->json($this->serializeMeal($meal));
    }

    #[Route('/api/meals/{id}', name: 'api_meals_delete', methods: ['DELETE'])]
    public function delete(int $id, \Doctrine\ORM\EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        $meal = $em->getRepository(\App\Entity\Meal::class)->find($id);
        if (!$meal || $meal->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Meal not found'], 404);
        }
        $em->remove($meal);
        $em->flush();
        return $this->json(['message' => 'Meal deleted']);
    }

    /**
     * Serializes a Meal entity into the standard API response array,
     * grouping its MealOptions by MealTime.
     */
    private function serializeMeal(\App\Entity\Meal $meal): array
    {
        $groupedOptions = [];
        foreach ($meal->getMealOptions() as $option) {
            $mealTime = $option->getMealTime();
            $mealTimeId = $mealTime ? $mealTime->getId() : null;
            if ($mealTimeId) {
                if (!isset($groupedOptions[$mealTimeId])) {
                    $groupedOptions[$mealTimeId] = [
                        'id'      => $mealTime->getId(),
                        'name'    => $mealTime->getName(),
                        'label'   => $mealTime->getLabel(),
                        'options' => [],
                    ];
                }
                $groupedOptions[$mealTimeId]['options'][] = [
                    'id'                 => $option->getId(),
                    'name'               => $option->getName(),
                    'description'        => $option->getDescription(),
                    'estimated_calories' => $option->getEstimatedCalories(),
                    'ingredients'        => array_map(
                        fn($i) => [
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

        return [
            'id'         => $meal->getId(),
            'name'       => $meal->getName(),
            'calories'   => $meal->getCalories(),
            'date'       => $meal->getDate()->format('c'),
            'notes'      => $meal->getNotes(),
            'meal_times' => array_values($groupedOptions),
        ];
    }
}

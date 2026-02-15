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
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        $meals = $user->getMeals();
        $data = [];
        foreach ($meals as $meal) {
            // Group meal options by meal time
            $groupedOptions = [];
            foreach ($meal->getMealOptions() as $option) {
                $mealTime = $option->getMealTime();
                $mealTimeId = $mealTime ? $mealTime->getId() : null;
                if ($mealTimeId) {
                    if (!isset($groupedOptions[$mealTimeId])) {
                        $groupedOptions[$mealTimeId] = [
                            'id' => $mealTime->getId(),
                            'name' => $mealTime->getName(),
                            'label' => $mealTime->getLabel(),
                            'options' => []
                        ];
                    }
                    $groupedOptions[$mealTimeId]['options'][] = [
                        'id' => $option->getId(),
                        'name' => $option->getName(),
                        'description' => $option->getDescription(),
                    ];
                }
            }
            $data[] = [
                'id' => $meal->getId(),
                'name' => $meal->getName(),
                'calories' => $meal->getCalories(),
                'date' => $meal->getDate()->format('c'),
                'notes' => $meal->getNotes(),
                'meal_times' => array_values($groupedOptions),
            ];
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
        if (!$data || !isset($data['name'], $data['calories'], $data['date'])) {
            return $this->json(['error' => 'name, calories, and date are required'], 400);
        }
        $meal = new \App\Entity\Meal();
        $meal->setUser($user);
        $meal->setName($data['name']);
        $meal->setCalories((int)$data['calories']);
        $meal->setDate(new \DateTimeImmutable($data['date']));
        $meal->setNotes($data['notes'] ?? null);

        $errors = $validator->validate($meal);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $em->persist($meal);
        $em->flush();

        return $this->json([
            'id' => $meal->getId(),
            'name' => $meal->getName(),
            'calories' => $meal->getCalories(),
            'date' => $meal->getDate()->format('c'),
            'notes' => $meal->getNotes(),
        ], 201);
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

        $errors = $validator->validate($meal);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $em->flush();
        return $this->json([
            'id' => $meal->getId(),
            'name' => $meal->getName(),
            'calories' => $meal->getCalories(),
            'date' => $meal->getDate()->format('c'),
            'notes' => $meal->getNotes(),
        ]);
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
}

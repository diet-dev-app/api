<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class MealOptionController extends AbstractController
{
    #[Route('/api/meal-options', name: 'api_meal_options_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        // TODO: Implement list meal options for authenticated user
        return $this->json(['message' => 'List meal options endpoint']);
    }

    #[Route('/api/meal-options', name: 'api_meal_options_create', methods: ['POST'])]
    public function create(): JsonResponse
    {
        // TODO: Implement create meal option
        return $this->json(['message' => 'Create meal option endpoint']);
    }

    #[Route('/api/meal-options/{id}', name: 'api_meal_options_update', methods: ['PUT'])]
    public function update(int $id): JsonResponse
    {
        // TODO: Implement update meal option
        return $this->json(['message' => 'Update meal option endpoint', 'id' => $id]);
    }

    #[Route('/api/meal-options/{id}', name: 'api_meal_options_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        // TODO: Implement delete meal option
        return $this->json(['message' => 'Delete meal option endpoint', 'id' => $id]);
    }
}

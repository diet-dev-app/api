<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class MealOptionController extends AbstractController
{
    #[Route('/api/meal-options', name: 'api_meal_options_list', methods: ['GET'])]
    public function list(\Doctrine\ORM\EntityManagerInterface $em): JsonResponse
    {
        $repo = $em->getRepository(\App\Entity\MealOption::class);
        $options = $repo->createQueryBuilder('o')
            ->leftJoin('o.mealTime', 't')
            ->addSelect('t')
            ->getQuery()->getResult();

        $data = array_map(function($option) {
            return [
                'id' => $option->getId(),
                'name' => $option->getName(),
                'description' => $option->getDescription(),
                'meal_time' => $option->getMealTime() ? [
                    'id' => $option->getMealTime()->getId(),
                    'name' => $option->getMealTime()->getName(),
                    'label' => $option->getMealTime()->getLabel(),
                ] : null,
            ];
        }, $options);

        return $this->json($data);
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

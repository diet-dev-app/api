<?php
// src/Controller/ShoppingListController.php

namespace App\Controller;

use App\Service\OpenAIShoppingListService;
use App\Repository\MealRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ShoppingListController extends AbstractController
{
    #[Route('/api/shopping-list', name: 'api_shopping_list', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getShoppingList(Request $request, MealRepository $mealRepository, OpenAIShoppingListService $shoppingListService): Response
    {
        $user = $this->getUser();
        $start = $request->query->get('start');
        $end = $request->query->get('end');
        if (!$start || !$end) {
            return $this->json(['error' => 'Missing start or end date'], 400);
        }
        $meals = $mealRepository->findByUserAndDateRange($user, $start, $end);
        if (empty($meals)) {
            return $this->json(['error' => 'No meals found for this range'], 404);
        }
        // Prepare meal data for OpenAI (including ingredients per meal option)
        $mealData = array_map(function($meal) {
            $options = [];
            foreach ($meal->getMealOptions() as $option) {
                $options[] = [
                    'name'               => $option->getName(),
                    'description'        => $option->getDescription(),
                    'estimated_calories' => $option->getEstimatedCalories(),
                    'meal_time'          => $option->getMealTime()?->getLabel() ?? $option->getMealTime()?->getName(),
                    'ingredients'        => array_map(
                        fn($i) => [
                            'name'     => $i->getName(),
                            'quantity' => $i->getQuantity(),
                            'unit'     => $i->getUnit(),
                        ],
                        $option->getIngredients()->toArray()
                    ),
                ];
            }
            return [
                'name'    => $meal->getName(),
                'date'    => $meal->getDate()->format('Y-m-d'),
                'notes'   => $meal->getNotes(),
                'options' => $options,
            ];
        }, $meals);
        $shoppingList = $shoppingListService->generateShoppingList($mealData);
        return $this->json($shoppingList);
    }
}

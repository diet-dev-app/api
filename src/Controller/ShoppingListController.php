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
        // Prepare meal data for OpenAI
        $mealData = array_map(function($meal) {
            return [
                'name' => $meal->getName(),
                'calories' => $meal->getCalories(),
                'date' => $meal->getDate()->format('Y-m-d'),
                'notes' => $meal->getNotes(),
                // Add more fields as needed
            ];
        }, $meals);
        $shoppingList = $shoppingListService->generateShoppingList($mealData);
        return $this->json($shoppingList);
    }
}

<?php

namespace App\Controller;

use App\Entity\Ingredient;
use App\Entity\MealOption;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class MealOptionController extends AbstractController
{
    // -------------------------------------------------------------------------
    // Helper: serialize a single MealOption (including ingredients)
    // -------------------------------------------------------------------------
    private function serializeOption(MealOption $option): array
    {
        $ingredients = array_map(
            fn(Ingredient $i) => [
                'id'       => $i->getId(),
                'name'     => $i->getName(),
                'quantity' => $i->getQuantity(),
                'unit'     => $i->getUnit(),
            ],
            $option->getIngredients()->toArray()
        );

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
            'ingredients' => $ingredients,
        ];
    }

    // -------------------------------------------------------------------------
    // GET /api/meal-options
    // -------------------------------------------------------------------------
    #[Route('/api/meal-options', name: 'api_meal_options_list', methods: ['GET'])]
    public function list(EntityManagerInterface $em): JsonResponse
    {
        $options = $em->getRepository(MealOption::class)
            ->createQueryBuilder('o')
            ->leftJoin('o.mealTime', 't')->addSelect('t')
            ->leftJoin('o.ingredients', 'i')->addSelect('i')
            ->getQuery()->getResult();

        return $this->json(array_map([$this, 'serializeOption'], $options));
    }

    // -------------------------------------------------------------------------
    // POST /api/meal-options
    // Body: { name, description?, meal_time_id, estimated_calories?,
    //         ingredients: [{ name, quantity, unit }] }
    // -------------------------------------------------------------------------
    #[Route('/api/meal-options', name: 'api_meal_options_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['name']) || empty($data['meal_time_id'])) {
            return $this->json(['error' => 'name and meal_time_id are required'], 400);
        }

        $mealTime = $em->getRepository(\App\Entity\MealTime::class)->find($data['meal_time_id']);
        if (!$mealTime) {
            return $this->json(['error' => 'MealTime not found'], 404);
        }

        $option = new MealOption();
        $option->setName($data['name']);
        $option->setDescription($data['description'] ?? null);
        $option->setMealTime($mealTime);
        $option->setEstimatedCalories(isset($data['estimated_calories']) ? (float)$data['estimated_calories'] : null);

        foreach ($data['ingredients'] ?? [] as $ingredientData) {
            if (empty($ingredientData['name']) || !isset($ingredientData['quantity']) || empty($ingredientData['unit'])) {
                return $this->json(['error' => 'Each ingredient requires name, quantity and unit'], 400);
            }
            $ingredient = new Ingredient();
            $ingredient->setName($ingredientData['name']);
            $ingredient->setQuantity((float)$ingredientData['quantity']);
            $ingredient->setUnit($ingredientData['unit']);
            $option->addIngredient($ingredient);
        }

        $em->persist($option);
        $em->flush();

        return $this->json($this->serializeOption($option), 201);
    }

    // -------------------------------------------------------------------------
    // PUT /api/meal-options/{id}
    // Body: { name?, description?, meal_time_id?, estimated_calories?,
    //         ingredients?: [{ name, quantity, unit }] }
    // Providing `ingredients` replaces the full ingredient list.
    // -------------------------------------------------------------------------
    #[Route('/api/meal-options/{id}', name: 'api_meal_options_update', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $option = $em->getRepository(MealOption::class)->find($id);
        if (!$option) {
            return $this->json(['error' => 'MealOption not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) $option->setName($data['name']);
        if (array_key_exists('description', $data)) $option->setDescription($data['description']);
        if (array_key_exists('estimated_calories', $data)) {
            $option->setEstimatedCalories($data['estimated_calories'] !== null ? (float)$data['estimated_calories'] : null);
        }

        if (isset($data['meal_time_id'])) {
            $mealTime = $em->getRepository(\App\Entity\MealTime::class)->find($data['meal_time_id']);
            if (!$mealTime) {
                return $this->json(['error' => 'MealTime not found'], 404);
            }
            $option->setMealTime($mealTime);
        }

        // Replace ingredient list when provided
        if (array_key_exists('ingredients', $data)) {
            foreach ($option->getIngredients()->toArray() as $existing) {
                $option->removeIngredient($existing);
            }
            foreach ($data['ingredients'] as $ingredientData) {
                if (empty($ingredientData['name']) || !isset($ingredientData['quantity']) || empty($ingredientData['unit'])) {
                    return $this->json(['error' => 'Each ingredient requires name, quantity and unit'], 400);
                }
                $ingredient = new Ingredient();
                $ingredient->setName($ingredientData['name']);
                $ingredient->setQuantity((float)$ingredientData['quantity']);
                $ingredient->setUnit($ingredientData['unit']);
                $option->addIngredient($ingredient);
            }
        }

        $em->flush();

        return $this->json($this->serializeOption($option));
    }

    // -------------------------------------------------------------------------
    // DELETE /api/meal-options/{id}
    // -------------------------------------------------------------------------
    #[Route('/api/meal-options/{id}', name: 'api_meal_options_delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $option = $em->getRepository(MealOption::class)->find($id);
        if (!$option) {
            return $this->json(['error' => 'MealOption not found'], 404);
        }
        $em->remove($option);
        $em->flush();
        return $this->json(['message' => 'MealOption deleted']);
    }
}

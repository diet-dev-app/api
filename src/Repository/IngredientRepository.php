<?php

namespace App\Repository;

use App\Entity\Ingredient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ingredient>
 */
class IngredientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ingredient::class);
    }

    /**
     * Returns all ingredients belonging to a given MealOption ID.
     *
     * @return Ingredient[]
     */
    public function findByMealOptionId(int $mealOptionId): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.mealOption = :mealOptionId')
            ->setParameter('mealOptionId', $mealOptionId)
            ->orderBy('i.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

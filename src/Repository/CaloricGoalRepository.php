<?php

namespace App\Repository;

use App\Entity\CaloricGoal;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CaloricGoal>
 */
class CaloricGoalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CaloricGoal::class);
    }

    /**
     * Returns all caloric goals for a user ordered by startDate descending.
     *
     * @return CaloricGoal[]
     */
    public function findAllByUser(User $user): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.user = :user')
            ->setParameter('user', $user)
            ->orderBy('g.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns the goal that covers the given date for the user.
     * Defaults to today when $date is null.
     */
    public function findActiveGoalForUser(User $user, ?\DateTimeImmutable $date = null): ?CaloricGoal
    {
        $date = ($date ?? new \DateTimeImmutable('today'))->format('Y-m-d');

        return $this->createQueryBuilder('g')
            ->where('g.user = :user')
            ->andWhere('g.startDate <= :date')
            ->andWhere('g.endDate IS NULL OR g.endDate >= :date')
            ->setParameter('user', $user)
            ->setParameter('date', $date)
            ->setMaxResults(1)
            ->orderBy('g.startDate', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Returns true if the date range [start, end] (where end=null means open-ended)
     * overlaps with any existing caloric goal of the user, excluding the goal
     * identified by $excludeId (useful when updating).
     */
    public function hasOverlap(
        User $user,
        \DateTimeImmutable $start,
        ?\DateTimeImmutable $end,
        ?int $excludeId = null,
    ): bool {
        $qb = $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->where('g.user = :user')
            ->setParameter('user', $user);

        // Overlap condition:
        // existing.start <= new.end  AND  (existing.end IS NULL OR existing.end >= new.start)
        if ($end !== null) {
            $qb->andWhere('g.startDate <= :end')
               ->setParameter('end', $end->format('Y-m-d'));
        }
        // new end IS NULL means it extends to infinity, so any existing goal
        // whose start >= new.start overlaps

        $qb->andWhere('g.endDate IS NULL OR g.endDate >= :start')
           ->setParameter('start', $start->format('Y-m-d'));

        if ($excludeId !== null) {
            $qb->andWhere('g.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}

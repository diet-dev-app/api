<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\WeeklyReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WeeklyReport>
 */
class WeeklyReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeeklyReport::class);
    }

    /**
     * Find a cached weekly report for a specific user and week.
     *
     * @param User               $user      Authenticated user
     * @param \DateTimeImmutable $weekStart The Monday of the requested week
     */
    public function findByUserAndWeek(User $user, \DateTimeImmutable $weekStart): ?WeeklyReport
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.weekStart = :weekStart')
            ->setParameter('user', $user)
            ->setParameter('weekStart', $weekStart->format('Y-m-d'))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Returns the most recent weekly reports for a user.
     *
     * @param User $user
     * @param int  $limit Maximum number of reports to return (default 8)
     * @return WeeklyReport[]
     */
    public function findRecentByUser(User $user, int $limit = 8): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.weekStart', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

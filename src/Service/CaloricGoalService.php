<?php

namespace App\Service;

use App\Entity\CaloricGoal;
use App\Entity\User;
use App\Repository\CaloricGoalRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Encapsulates all business logic for managing per-user caloric goals.
 *
 * Business rules enforced:
 * - Date ranges must not overlap for the same user.
 * - endDate can be null (open-ended / currently active goal).
 * - Only one goal covers any given date.
 */
class CaloricGoalService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CaloricGoalRepository $repository,
    ) {}

    /**
     * Returns the goal that covers the given date, or null if none exists.
     * Defaults to today when $date is null.
     */
    public function getActiveGoal(User $user, ?\DateTimeImmutable $date = null): ?CaloricGoal
    {
        return $this->repository->findActiveGoalForUser($user, $date);
    }

    /**
     * Returns all caloric goals for the user ordered by startDate descending.
     *
     * @return CaloricGoal[]
     */
    public function listForUser(User $user): array
    {
        return $this->repository->findAllByUser($user);
    }

    /**
     * Creates a new caloric goal after validating that its date range does
     * not overlap with any existing goal for the same user.
     *
     * @throws \InvalidArgumentException when daily_calories is out of range
     * @throws \DomainException          when the date range overlaps an existing goal
     */
    public function create(
        User $user,
        int $dailyCalories,
        \DateTimeImmutable $startDate,
        ?\DateTimeImmutable $endDate = null,
        ?string $label = null,
        ?string $notes = null,
    ): CaloricGoal {
        $this->validateDailyCalories($dailyCalories);
        $this->validateDateRange($startDate, $endDate);
        $this->assertNoOverlap($user, $startDate, $endDate);

        $goal = new CaloricGoal();
        $goal->setUser($user)
             ->setDailyCalories($dailyCalories)
             ->setStartDate($startDate)
             ->setEndDate($endDate)
             ->setLabel($label)
             ->setNotes($notes);

        $this->em->persist($goal);
        $this->em->flush();

        return $goal;
    }

    /**
     * Updates an existing caloric goal.
     *
     * @param array $data Associative array with optional keys:
     *                    daily_calories, start_date (Y-m-d), end_date (Y-m-d|null),
     *                    label, notes
     * @throws \InvalidArgumentException when daily_calories is out of range
     * @throws \DomainException          when the updated date range overlaps another goal
     */
    public function update(CaloricGoal $goal, array $data): CaloricGoal
    {
        $user       = $goal->getUser();
        $startDate  = $goal->getStartDate();
        $endDate    = $goal->getEndDate();

        if (isset($data['daily_calories'])) {
            $this->validateDailyCalories((int) $data['daily_calories']);
            $goal->setDailyCalories((int) $data['daily_calories']);
        }
        if (isset($data['start_date'])) {
            $startDate = new \DateTimeImmutable($data['start_date']);
            $goal->setStartDate($startDate);
        }
        if (array_key_exists('end_date', $data)) {
            $endDate = $data['end_date'] !== null
                ? new \DateTimeImmutable($data['end_date'])
                : null;
            $goal->setEndDate($endDate);
        }
        if (isset($data['label'])) {
            $goal->setLabel($data['label']);
        }
        if (array_key_exists('notes', $data)) {
            $goal->setNotes($data['notes']);
        }

        $this->validateDateRange($startDate, $endDate);
        $this->assertNoOverlap($user, $startDate, $endDate, $goal->getId());

        $goal->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $goal;
    }

    /**
     * Removes a caloric goal permanently.
     */
    public function delete(CaloricGoal $goal): void
    {
        $this->em->remove($goal);
        $this->em->flush();
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * @throws \InvalidArgumentException
     */
    private function validateDailyCalories(int $calories): void
    {
        if ($calories < 500 || $calories > 10_000) {
            throw new \InvalidArgumentException(
                'daily_calories must be between 500 and 10000 kcal.'
            );
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function validateDateRange(\DateTimeImmutable $start, ?\DateTimeImmutable $end): void
    {
        if ($end !== null && $end < $start) {
            throw new \InvalidArgumentException(
                'end_date must be equal to or after start_date.'
            );
        }
    }

    /**
     * @throws \DomainException
     */
    private function assertNoOverlap(
        User $user,
        \DateTimeImmutable $start,
        ?\DateTimeImmutable $end,
        ?int $excludeId = null,
    ): void {
        if ($this->repository->hasOverlap($user, $start, $end, $excludeId)) {
            throw new \DomainException(
                'The date range overlaps with an existing caloric goal.'
            );
        }
    }
}

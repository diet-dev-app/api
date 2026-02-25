<?php

namespace App\Entity;

use App\Repository\CaloricGoalRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a user's daily caloric target for a specific date range.
 *
 * A user can have multiple non-overlapping goals across different periods
 * (e.g. "cutting" in January, "maintenance" in February).
 * When endDate is null the goal is open-ended (currently active).
 */
#[ORM\Entity(repositoryClass: CaloricGoalRepository::class)]
#[ORM\Table(name: 'caloric_goal')]
class CaloricGoal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /**
     * Daily caloric target in kcal.
     */
    #[ORM\Column(type: 'integer')]
    private int $dailyCalories;

    /**
     * Optional human-readable label: "cutting", "bulking", "maintenance", etc.
     */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $label = null;

    /**
     * First day of this goal period (inclusive).
     */
    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $startDate;

    /**
     * Last day of this goal period (inclusive). Null means the goal is open-ended.
     */
    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    /**
     * Optional user notes about this goal period.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // -------------------------------------------------------------------------
    // Getters / Setters
    // -------------------------------------------------------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getDailyCalories(): int
    {
        return $this->dailyCalories;
    }

    public function setDailyCalories(int $dailyCalories): self
    {
        $this->dailyCalories = $dailyCalories;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Returns true if this goal is active on the given date (defaults to today).
     */
    public function isActiveOn(?\DateTimeImmutable $date = null): bool
    {
        $date = $date ?? new \DateTimeImmutable('today');
        $start = $this->startDate->setTime(0, 0, 0);
        if ($date < $start) {
            return false;
        }
        if ($this->endDate === null) {
            return true;
        }
        return $date <= $this->endDate->setTime(23, 59, 59);
    }
}

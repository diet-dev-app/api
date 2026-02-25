<?php

namespace App\Entity;

use App\Repository\WeeklyReportRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Caches an AI-generated weekly nutritional analysis report for a user.
 *
 * Stored so it can be re-served without re-calling OpenAI.
 * Regeneration is triggered explicitly via ?regenerate=true.
 */
#[ORM\Entity(repositoryClass: WeeklyReportRepository::class)]
#[ORM\Table(name: 'weekly_report')]
#[ORM\UniqueConstraint(name: 'uq_user_week', columns: ['user_id', 'week_start'])]
class WeeklyReport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /**
     * Monday of the report week (ISO week start).
     */
    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $weekStart;

    /**
     * Sunday of the report week (ISO week end, 6 days after weekStart).
     */
    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $weekEnd;

    /**
     * Daily caloric target used for this week's analysis.
     */
    #[ORM\Column(type: 'integer')]
    private int $targetCalories;

    /**
     * Average actual calories consumed per tracked day.
     */
    #[ORM\Column(type: 'integer')]
    private int $averageCalories;

    /**
     * Total calories consumed across the whole week.
     */
    #[ORM\Column(type: 'integer')]
    private int $totalCalories;

    /**
     * Number of days in the week that had at least one registered meal.
     */
    #[ORM\Column(type: 'integer')]
    private int $daysTracked;

    /**
     * Full AI-generated analysis stored as JSON.
     * Contains goal_adherence, calorie_analysis, nutritional_gaps,
     * achievements, notes_analysis, recommendations, summary.
     */
    #[ORM\Column(type: 'json')]
    private array $analysis = [];

    /**
     * Short AI-generated motivational summary paragraph.
     */
    #[ORM\Column(type: 'text')]
    private string $summary = '';

    /**
     * Timestamp when this report was generated / last regenerated.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $generatedAt;

    public function __construct()
    {
        $this->generatedAt = new \DateTimeImmutable();
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

    public function getWeekStart(): \DateTimeImmutable
    {
        return $this->weekStart;
    }

    public function setWeekStart(\DateTimeImmutable $weekStart): self
    {
        $this->weekStart = $weekStart;
        return $this;
    }

    public function getWeekEnd(): \DateTimeImmutable
    {
        return $this->weekEnd;
    }

    public function setWeekEnd(\DateTimeImmutable $weekEnd): self
    {
        $this->weekEnd = $weekEnd;
        return $this;
    }

    public function getTargetCalories(): int
    {
        return $this->targetCalories;
    }

    public function setTargetCalories(int $targetCalories): self
    {
        $this->targetCalories = $targetCalories;
        return $this;
    }

    public function getAverageCalories(): int
    {
        return $this->averageCalories;
    }

    public function setAverageCalories(int $averageCalories): self
    {
        $this->averageCalories = $averageCalories;
        return $this;
    }

    public function getTotalCalories(): int
    {
        return $this->totalCalories;
    }

    public function setTotalCalories(int $totalCalories): self
    {
        $this->totalCalories = $totalCalories;
        return $this;
    }

    public function getDaysTracked(): int
    {
        return $this->daysTracked;
    }

    public function setDaysTracked(int $daysTracked): self
    {
        $this->daysTracked = $daysTracked;
        return $this;
    }

    public function getAnalysis(): array
    {
        return $this->analysis;
    }

    public function setAnalysis(array $analysis): self
    {
        $this->analysis = $analysis;
        return $this;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): self
    {
        $this->summary = $summary;
        return $this;
    }

    public function getGeneratedAt(): \DateTimeImmutable
    {
        return $this->generatedAt;
    }

    public function setGeneratedAt(\DateTimeImmutable $generatedAt): self
    {
        $this->generatedAt = $generatedAt;
        return $this;
    }
}

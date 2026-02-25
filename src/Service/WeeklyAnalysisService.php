<?php

namespace App\Service;

use App\Entity\Meal;
use App\Entity\User;
use App\Entity\WeeklyReport;
use App\Repository\MealRepository;
use App\Repository\WeeklyReportRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Analyses a user's weekly meal data (actual calories, meal choices, and
 * personal notes) and asks OpenAI to produce a structured nutritional report.
 *
 * Reports are cached in the `weekly_report` table and only regenerated when
 * explicitly requested via the `regenerate` flag.
 */
class WeeklyAnalysisService
{
    public function __construct(
        private readonly OpenAIService $openAI,
        private readonly EntityManagerInterface $em,
        private readonly CaloricGoalService $caloricGoalService,
        private readonly MealRepository $mealRepository,
        private readonly WeeklyReportRepository $reportRepository,
    ) {}

    /**
     * Generate (or retrieve cached) weekly nutritional analysis.
     *
     * @param User               $user       The authenticated user
     * @param \DateTimeImmutable $weekStart  Monday of the target week
     * @param bool               $regenerate Force re-generation even if cached
     * @return array Full analysis report (serialised into array)
     *
     * @throws \RuntimeException             when no caloric goal exists for the week
     * @throws \UnexpectedValueException     when OpenAI returns invalid data
     */
    public function generateWeeklyReport(
        User $user,
        \DateTimeImmutable $weekStart,
        bool $regenerate = false,
    ): array {
        // Normalise weekStart to midnight
        $weekStart = $weekStart->setTime(0, 0, 0);
        $weekEnd   = $weekStart->modify('+6 days')->setTime(23, 59, 59);

        // Return cached report unless regeneration was requested
        if (!$regenerate) {
            $cached = $this->reportRepository->findByUserAndWeek($user, $weekStart);
            if ($cached !== null) {
                return $this->serializeReport($cached);
            }
        }

        // Resolve caloric goal for the week (use Monday's goal)
        $goal = $this->caloricGoalService->getActiveGoal($user, $weekStart);
        if ($goal === null) {
            throw new \RuntimeException(
                'No caloric goal found for this week. '
                . 'Please create a caloric goal that covers the requested period.'
            );
        }
        $targetCalories = $goal->getDailyCalories();

        // Fetch meals in the date range
        $meals = $this->mealRepository->findByUserAndDateRange(
            $user,
            $weekStart->format('Y-m-d'),
            $weekEnd->format('Y-m-d')
        );

        // Compute raw statistics
        $stats = $this->computeStats($meals, $weekStart, $targetCalories);

        // Build OpenAI prompt
        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt   = $this->buildUserPrompt($targetCalories, $stats, $meals, $weekStart);

        // Call OpenAI
        $analysis = $this->openAI->chatJson($systemPrompt, $userPrompt);

        if (isset($analysis['error'])) {
            throw new \UnexpectedValueException(
                'OpenAI could not generate a valid weekly analysis: ' . $analysis['error']
            );
        }

        // Persist / overwrite report
        $report = $this->reportRepository->findByUserAndWeek($user, $weekStart)
            ?? new WeeklyReport();

        $summary = $analysis['summary'] ?? '';

        $report
            ->setUser($user)
            ->setWeekStart($weekStart)
            ->setWeekEnd($weekStart->modify('+6 days'))
            ->setTargetCalories($targetCalories)
            ->setAverageCalories($stats['average_calories'])
            ->setTotalCalories($stats['total_calories'])
            ->setDaysTracked($stats['days_tracked'])
            ->setAnalysis($analysis)
            ->setSummary(is_string($summary) ? $summary : '')
            ->setGeneratedAt(new \DateTimeImmutable());

        $this->em->persist($report);
        $this->em->flush();

        return $this->serializeReport($report);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Compute raw statistics from the meal list for the week.
     *
     * @param Meal[]             $meals
     * @param \DateTimeImmutable $weekStart
     * @param int                $targetCalories daily goal
     */
    private function computeStats(array $meals, \DateTimeImmutable $weekStart, int $targetCalories): array
    {
        // Index meals by date (Y-m-d) → calories
        $byDate = [];
        foreach ($meals as $meal) {
            $dateKey = $meal->getDate()->format('Y-m-d');
            $byDate[$dateKey] = ($byDate[$dateKey] ?? 0) + $meal->getCalories();
        }

        $daysTracked   = count($byDate);
        $totalCalories = (int) array_sum($byDate);
        $avgCalories   = $daysTracked > 0 ? (int) round($totalCalories / $daysTracked) : 0;

        // Build day-by-day breakdown for 7 days
        $dailyBreakdown = [];
        for ($i = 0; $i < 7; $i++) {
            $day    = $weekStart->modify("+{$i} days");
            $key    = $day->format('Y-m-d');
            $actual = $byDate[$key] ?? 0;
            $diff   = $actual - $targetCalories;

            if ($actual === 0) {
                $status = 'not_tracked';
            } elseif (abs($diff) <= $targetCalories * 0.1) {
                $status = 'on_target';
            } elseif ($diff > 0) {
                $status = 'over';
            } else {
                $status = 'under';
            }

            $dailyBreakdown[] = [
                'date'       => $key,
                'target'     => $targetCalories,
                'actual'     => $actual,
                'difference' => $diff,
                'status'     => $status,
            ];
        }

        return [
            'daily_breakdown' => $dailyBreakdown,
            'total_calories'  => $totalCalories,
            'average_calories'=> $avgCalories,
            'days_tracked'    => $daysTracked,
        ];
    }

    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
You are a professional nutritionist reviewing a client's weekly diet log.
You will receive:
1. The client's daily caloric goal.
2. A day-by-day breakdown of what they ate (meals chosen, calories consumed, the difference from their target, and status).
3. The client's personal notes for each day (may include feelings, energy levels, symptoms, or general observations).

Provide a comprehensive weekly analysis. Return ONLY valid JSON (no prose, no code fences) with this exact structure:
{
  "goal_adherence": {
    "score": 78,
    "days_on_target": 4,
    "days_over": 1,
    "days_under": 1,
    "days_not_tracked": 1
  },
  "calorie_analysis": {
    "daily_breakdown": [...],
    "weekly_total": 12950,
    "weekly_target": 14000,
    "weekly_difference": -1050,
    "average_daily": 1850
  },
  "nutritional_gaps": [
    { "area": "protein", "severity": "moderate", "detail": "..." }
  ],
  "achievements": [
    "Met caloric goal on 4 out of 7 days"
  ],
  "notes_analysis": {
    "patterns": ["..."],
    "concerns": ["..."],
    "mood_trend": "mixed"
  },
  "recommendations": [
    "Add a vegetable serving to lunch on days it was missing"
  ],
  "summary": "2-3 motivational sentences summarising the week."
}

Rules:
- severity in nutritional_gaps must be one of: low, moderate, high.
- mood_trend must be one of: positive, neutral, negative, mixed.
- recommendations must contain 3 to 5 items.
- All text values must be in the same language as the user's notes (default: English).
- Return ONLY the JSON object, nothing else.
PROMPT;
    }

    /**
     * @param Meal[] $meals
     */
    private function buildUserPrompt(
        int $targetCalories,
        array $stats,
        array $meals,
        \DateTimeImmutable $weekStart,
    ): string {
        // Collect notes per day
        $notesByDate = [];
        foreach ($meals as $meal) {
            if ($meal->getNotes()) {
                $key = $meal->getDate()->format('Y-m-d');
                $notesByDate[$key] = $meal->getNotes();
            }
        }

        // Enrich daily_breakdown with meal summaries and notes
        $enrichedBreakdown = array_map(function (array $day) use ($meals, $notesByDate) {
            $dayMeals = array_filter(
                $meals,
                fn(Meal $m) => $m->getDate()->format('Y-m-d') === $day['date']
            );

            $optionNames = [];
            foreach ($dayMeals as $meal) {
                foreach ($meal->getMealOptions() as $option) {
                    $optionNames[] = $option->getName()
                        . ' (' . $option->getMealTime()?->getName() . ')';
                }
            }

            return array_merge($day, [
                'meals_chosen' => $optionNames,
                'notes'        => $notesByDate[$day['date']] ?? null,
            ]);
        }, $stats['daily_breakdown']);

        $weekEnd = $weekStart->modify('+6 days');

        return sprintf(
            "Week: %s to %s\nDaily caloric goal: %d kcal\n\nDay-by-day data:\n%s",
            $weekStart->format('Y-m-d'),
            $weekEnd->format('Y-m-d'),
            $targetCalories,
            json_encode($enrichedBreakdown, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    private function serializeReport(WeeklyReport $report): array
    {
        $analysis = $report->getAnalysis();

        return array_merge([
            'id'               => $report->getId(),
            'week_start'       => $report->getWeekStart()->format('Y-m-d'),
            'week_end'         => $report->getWeekEnd()->format('Y-m-d'),
            'target_calories'  => $report->getTargetCalories(),
            'average_calories' => $report->getAverageCalories(),
            'total_calories'   => $report->getTotalCalories(),
            'days_tracked'     => $report->getDaysTracked(),
            'generated_at'     => $report->getGeneratedAt()->format('c'),
        ], $analysis);
    }
}

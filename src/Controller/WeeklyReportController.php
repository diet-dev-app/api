<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\WeeklyReportRepository;
use App\Service\WeeklyAnalysisService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Endpoints for AI-generated weekly nutritional reports.
 *
 * GET /api/reports/weekly         — generate or retrieve a weekly analysis
 * GET /api/reports/weekly/history — list recent weekly reports
 */
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class WeeklyReportController extends AbstractController
{
    public function __construct(
        private readonly WeeklyAnalysisService $analysisService,
        private readonly WeeklyReportRepository $reportRepository,
    ) {}

    // ── GET /api/reports/weekly ───────────────────────────────────────────────

    #[Route('/api/reports/weekly', name: 'api_reports_weekly', methods: ['GET'])]
    public function weekly(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Resolve weekStart: default to current Monday
        if ($request->query->has('week_start')) {
            try {
                $weekStart = new \DateTimeImmutable($request->query->getString('week_start'));
            } catch (\Exception) {
                return $this->json(['error' => 'Invalid week_start format. Use Y-m-d.'], 400);
            }

            // Validate that week_start is a Monday (ISO weekday = 1)
            if ((int) $weekStart->format('N') !== 1) {
                return $this->json(['error' => 'week_start must be a Monday (ISO 8601).'], 400);
            }
        } else {
            // Current Monday
            $weekStart = new \DateTimeImmutable('monday this week');
        }

        $regenerate = filter_var(
            $request->query->get('regenerate', 'false'),
            FILTER_VALIDATE_BOOLEAN
        );

        try {
            $report = $this->analysisService->generateWeeklyReport($user, $weekStart, $regenerate);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        } catch (\UnexpectedValueException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return $this->json(['error' => 'OpenAI API failure: ' . $e->getMessage()], 500);
        }

        return $this->json($report);
    }

    // ── GET /api/reports/weekly/history ──────────────────────────────────────

    #[Route('/api/reports/weekly/history', name: 'api_reports_weekly_history', methods: ['GET'])]
    public function history(Request $request): JsonResponse
    {
        /** @var User $user */
        $user  = $this->getUser();
        $limit = max(1, min(52, (int) $request->query->get('limit', 8)));

        $reports = $this->reportRepository->findRecentByUser($user, $limit);

        return $this->json(array_map([$this, 'serializeSummary'], $reports));
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Light serialisation for the history list (no full analysis payload).
     */
    private function serializeSummary(\App\Entity\WeeklyReport $report): array
    {
        $adherence = $report->getAnalysis()['goal_adherence'] ?? [];

        return [
            'id'               => $report->getId(),
            'week_start'       => $report->getWeekStart()->format('Y-m-d'),
            'week_end'         => $report->getWeekEnd()->format('Y-m-d'),
            'score'            => $adherence['score'] ?? null,
            'target_calories'  => $report->getTargetCalories(),
            'average_calories' => $report->getAverageCalories(),
            'total_calories'   => $report->getTotalCalories(),
            'days_tracked'     => $report->getDaysTracked(),
            'summary'          => $report->getSummary(),
            'generated_at'     => $report->getGeneratedAt()->format('c'),
        ];
    }
}

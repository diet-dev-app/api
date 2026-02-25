<?php

namespace App\Controller;

use App\Entity\CaloricGoal;
use App\Entity\User;
use App\Service\CaloricGoalService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * REST controller for managing per-user caloric goals.
 *
 * All endpoints require a valid JWT (IS_AUTHENTICATED_FULLY).
 */
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class CaloricGoalController extends AbstractController
{
    public function __construct(private readonly CaloricGoalService $service) {}

    // ── List all ──────────────────────────────────────────────────────────────

    #[Route('/api/caloric-goals', name: 'api_caloric_goals_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var User $user */
        $user  = $this->getUser();
        $goals = $this->service->listForUser($user);

        return $this->json(array_map([$this, 'serialize'], $goals));
    }

    // ── Active goal ───────────────────────────────────────────────────────────

    #[Route('/api/caloric-goals/active', name: 'api_caloric_goals_active', methods: ['GET'])]
    public function active(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $date = null;
        if ($request->query->has('date')) {
            try {
                $date = new \DateTimeImmutable($request->query->getString('date'));
            } catch (\Exception) {
                return $this->json(['error' => 'Invalid date format. Use Y-m-d.'], 400);
            }
        }

        $goal = $this->service->getActiveGoal($user, $date);
        if ($goal === null) {
            return $this->json(['error' => 'No active caloric goal found for this date.'], 404);
        }

        return $this->json($this->serialize($goal));
    }

    // ── Get by ID ─────────────────────────────────────────────────────────────

    #[Route('/api/caloric-goals/{id}', name: 'api_caloric_goals_get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $goal = $this->findOwnedGoal($id);
        if ($goal instanceof JsonResponse) {
            return $goal;
        }

        return $this->json($this->serialize($goal));
    }

    // ── Create ────────────────────────────────────────────────────────────────

    #[Route('/api/caloric-goals', name: 'api_caloric_goals_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];

        if (empty($data['daily_calories']) || empty($data['start_date'])) {
            return $this->json(['error' => 'daily_calories and start_date are required.'], 400);
        }

        try {
            $startDate = new \DateTimeImmutable($data['start_date']);
            $endDate   = !empty($data['end_date']) ? new \DateTimeImmutable($data['end_date']) : null;

            $goal = $this->service->create(
                $user,
                (int) $data['daily_calories'],
                $startDate,
                $endDate,
                $data['label'] ?? null,
                $data['notes'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid date format. Use Y-m-d.'], 400);
        }

        return $this->json($this->serialize($goal), 201);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    #[Route('/api/caloric-goals/{id}', name: 'api_caloric_goals_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $goal = $this->findOwnedGoal($id);
        if ($goal instanceof JsonResponse) {
            return $goal;
        }

        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $goal = $this->service->update($goal, $data);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid date format. Use Y-m-d.'], 400);
        }

        return $this->json($this->serialize($goal));
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    #[Route('/api/caloric-goals/{id}', name: 'api_caloric_goals_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $goal = $this->findOwnedGoal($id);
        if ($goal instanceof JsonResponse) {
            return $goal;
        }

        $this->service->delete($goal);

        return $this->json(['message' => 'Caloric goal deleted successfully.']);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Finds a CaloricGoal by ID and verifies ownership.
     * Returns a JsonResponse on failure.
     *
     * @return CaloricGoal|JsonResponse
     */
    private function findOwnedGoal(int $id): CaloricGoal|JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $goal = $this->service->listForUser($user);

        foreach ($goal as $g) {
            if ($g->getId() === $id) {
                return $g;
            }
        }

        return $this->json(['error' => 'Caloric goal not found.'], 404);
    }

    /**
     * Serializes a CaloricGoal entity into the standard API response array.
     */
    private function serialize(CaloricGoal $goal): array
    {
        return [
            'id'              => $goal->getId(),
            'daily_calories'  => $goal->getDailyCalories(),
            'start_date'      => $goal->getStartDate()->format('Y-m-d'),
            'end_date'        => $goal->getEndDate()?->format('Y-m-d'),
            'label'           => $goal->getLabel(),
            'notes'           => $goal->getNotes(),
            'created_at'      => $goal->getCreatedAt()->format('c'),
            'updated_at'      => $goal->getUpdatedAt()?->format('c'),
        ];
    }
}

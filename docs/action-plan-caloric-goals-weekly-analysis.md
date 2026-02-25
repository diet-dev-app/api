# Action Plan: Caloric Goals, AI Meal Generation & Weekly Nutritional Analysis

## Objective

Provide users with a comprehensive **caloric-goal tracking** system that:

1. **Stores daily caloric goals** per user, supporting different targets across time periods (e.g. cutting phase vs. maintenance).
2. **Generates or suggests daily meal plans** using existing `MealOption` records to meet the user's current caloric goal.
3. **Analyses user meal notes** (the `notes` field in `Meal`) combined with actual vs. target calorie data to produce a **weekly summary** of nutritional deficiencies, achievements, and recommendations.

This plan builds on top of the existing `OpenAIService` (already refactored as a generic client) and the current data model (`User`, `Meal`, `MealOption`, `MealTime`, `Ingredient`).

---

## Scope

| Area | Change |
|------|--------|
| **New entity** | `CaloricGoal` — stores per-user caloric targets with date ranges |
| **New entity** | `WeeklyReport` — caches generated weekly analysis reports |
| **New endpoints** | CRUD for caloric goals, meal generation, weekly analysis |
| **Service layer** | `CaloricGoalService`, `MealGeneratorService`, `WeeklyAnalysisService` |
| **AI integration** | OpenAI for smart meal plan generation and note sentiment/nutritional analysis |
| **Migrations** | New tables: `caloric_goal`, `weekly_report` |
| **OpenAPI** | New paths and schemas in `public/openapi.yaml` |
| **Documentation** | Endpoint docs + update `AGENTS.md` |

---

## Architecture

```
┌─────────────────────────────────┐
│         Client / Frontend       │
└────────┬──────────┬─────────────┘
         │          │
    ┌────▼───┐  ┌───▼──────────────────┐  ┌──────────────────────┐
    │Caloric │  │ Meal Generation      │  │ Weekly Analysis      │
    │Goal    │  │ POST /api/meals/     │  │ GET /api/reports/    │
    │CRUD    │  │      generate        │  │     weekly           │
    └────┬───┘  └───┬──────────────────┘  └───┬──────────────────┘
         │          │                          │
         ▼          ▼                          ▼
┌──────────────────────────────────────────────────────────────┐
│                    Service Layer                             │
│                                                              │
│  CaloricGoalService    MealGeneratorService    WeeklyAnalysis│
│  (CRUD + validation)   (AI meal planning)      Service       │
│                              │                  (AI analysis)│
│                              ▼                       │       │
│                      ┌──────────────┐                │       │
│                      │ OpenAIService│◄───────────────┘       │
│                      │  (generic)   │                        │
│                      └──────────────┘                        │
└──────────────────────────────────────────────────────────────┘
         │                    │                    │
         ▼                    ▼                    ▼
┌──────────────────────────────────────────────────────────────┐
│                    Database (Doctrine)                        │
│  CaloricGoal  │  Meal  │  MealOption  │  WeeklyReport        │
└──────────────────────────────────────────────────────────────┘
```

---

## Detailed Steps

### Phase 1 – New Entities & Migrations

#### 1.1 Create `App\Entity\CaloricGoal`

Stores a user's caloric target for a specific date range. A user can have multiple goals across different periods (e.g. a "cutting" phase in January, "maintenance" in February).

```php
#[ORM\Entity(repositoryClass: CaloricGoalRepository::class)]
#[ORM\Table(name: 'caloric_goal')]
class CaloricGoal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    /** @var int Daily caloric target in kcal */
    #[ORM\Column(type: 'integer')]
    private int $dailyCalories;

    /** @var string Optional label: "cutting", "bulking", "maintenance", etc. */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $label = null;

    /** @var \DateTimeImmutable Start date of this goal period */
    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $startDate;

    /** @var \DateTimeImmutable|null End date (null = ongoing / no end) */
    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    /** @var string|null Additional notes about this goal */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    // ... getters / setters
}
```

**Business rules:**
- Date ranges must not overlap for the same user.
- `endDate` can be null (open-ended / current goal).
- Only one goal can be "active" (covers today's date) at any time.

#### 1.2 Create `App\Entity\WeeklyReport`

Caches generated weekly reports so they don't need to be regenerated each time.

```php
#[ORM\Entity(repositoryClass: WeeklyReportRepository::class)]
#[ORM\Table(name: 'weekly_report')]
class WeeklyReport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    /** @var \DateTimeImmutable Monday of the report week */
    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $weekStart;

    /** @var \DateTimeImmutable Sunday of the report week */
    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $weekEnd;

    /** @var int Target daily calories for that week */
    #[ORM\Column(type: 'integer')]
    private int $targetCalories;

    /** @var int Average actual daily calories consumed */
    #[ORM\Column(type: 'integer')]
    private int $averageCalories;

    /** @var int Total calories consumed in the week */
    #[ORM\Column(type: 'integer')]
    private int $totalCalories;

    /** @var int Number of days with registered meals */
    #[ORM\Column(type: 'integer')]
    private int $daysTracked;

    /** @var array Full AI-generated analysis (JSON stored) */
    #[ORM\Column(type: 'json')]
    private array $analysis = [];

    /** @var string Short AI-generated textual summary */
    #[ORM\Column(type: 'text')]
    private string $summary;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $generatedAt;

    // ... getters / setters
}
```

#### 1.3 Create `App\Repository\CaloricGoalRepository`

Custom methods:
- `findActiveGoalForUser(User $user, ?\DateTimeImmutable $date = null): ?CaloricGoal` — returns the goal covering the given date (defaults to today).
- `findAllByUser(User $user): array` — returns all goals ordered by `startDate DESC`.
- `hasOverlap(User $user, \DateTimeImmutable $start, ?\DateTimeImmutable $end, ?int $excludeId = null): bool` — checks for date range overlaps.

#### 1.4 Create `App\Repository\WeeklyReportRepository`

Custom methods:
- `findByUserAndWeek(User $user, \DateTimeImmutable $weekStart): ?WeeklyReport`
- `findRecentByUser(User $user, int $limit = 8): array`

#### 1.5 Generate Doctrine Migrations

```bash
docker-compose exec php bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

---

### Phase 2 – Caloric Goal CRUD

#### 2.1 Create `App\Service\CaloricGoalService`

Encapsulates business logic:

```php
class CaloricGoalService
{
    public function __construct(
        private EntityManagerInterface $em,
        private CaloricGoalRepository $repository,
    ) {}

    /**
     * Get the user's active caloric goal for a given date.
     */
    public function getActiveGoal(User $user, ?\DateTimeImmutable $date = null): ?CaloricGoal { ... }

    /**
     * Create a new caloric goal, validating no overlaps.
     *
     * @throws \InvalidArgumentException on overlap
     */
    public function create(User $user, int $dailyCalories, \DateTimeImmutable $startDate, ?\DateTimeImmutable $endDate = null, ?string $label = null, ?string $notes = null): CaloricGoal { ... }

    /**
     * Update an existing caloric goal.
     */
    public function update(CaloricGoal $goal, array $data): CaloricGoal { ... }

    /**
     * Delete a caloric goal.
     */
    public function delete(CaloricGoal $goal): void { ... }

    /**
     * List all goals for a user.
     */
    public function listForUser(User $user): array { ... }
}
```

#### 2.2 Create `App\Controller\CaloricGoalController`

Endpoints:

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/caloric-goals` | List all caloric goals for the authenticated user |
| `GET` | `/api/caloric-goals/active` | Get the currently active goal |
| `GET` | `/api/caloric-goals/{id}` | Get a specific goal by ID |
| `POST` | `/api/caloric-goals` | Create a new caloric goal |
| `PUT` | `/api/caloric-goals/{id}` | Update an existing goal |
| `DELETE` | `/api/caloric-goals/{id}` | Delete a goal |

**POST / PUT body example:**

```json
{
  "daily_calories": 2000,
  "start_date": "2026-03-01",
  "end_date": "2026-03-31",
  "label": "cutting",
  "notes": "Reducing intake after holiday season"
}
```

**Response example (single goal):**

```json
{
  "id": 1,
  "daily_calories": 2000,
  "start_date": "2026-03-01",
  "end_date": "2026-03-31",
  "label": "cutting",
  "notes": "Reducing intake after holiday season",
  "created_at": "2026-02-24T10:00:00+00:00",
  "updated_at": null
}
```

**Error responses:**

| Code | Condition |
|------|-----------|
| 400 | Missing required fields or invalid data |
| 404 | Goal not found or not owned by user |
| 409 | Date range overlaps with an existing goal |
| 401 | Unauthorized |

---

### Phase 3 – AI Meal Plan Generation

#### 3.1 Create `App\Service\MealGeneratorService`

This service generates a daily meal plan using existing `MealOption` records to match the user's caloric goal.

```php
class MealGeneratorService
{
    public function __construct(
        private OpenAIService $openAI,
        private EntityManagerInterface $em,
        private CaloricGoalService $caloricGoalService,
    ) {}

    /**
     * Generate a meal plan for a specific date using existing MealOptions.
     *
     * @param User $user The authenticated user
     * @param \DateTimeImmutable $date Target date for the meal plan
     * @param int|null $targetCalories Override target (if null, uses active goal)
     * @return array Generated meal plan with selected MealOptions per MealTime
     *
     * @throws \RuntimeException if no active caloric goal and no override provided
     */
    public function generateMealPlan(User $user, \DateTimeImmutable $date, ?int $targetCalories = null): array { ... }
}
```

**How it works:**

1. Determine the target daily calories:
   - Uses `$targetCalories` if provided, otherwise queries the user's active `CaloricGoal`.
2. Fetch all available `MealOption` records (with ingredients and estimated calories).
3. Fetch all `MealTime` records (breakfast, lunch, snack, dinner).
4. Build an OpenAI prompt that:
   - Lists all available meal options with their calories and meal times.
   - Specifies the daily caloric target.
   - Asks OpenAI to select the best combination of existing meal options to meet the target.
   - Allows OpenAI to suggest portion adjustments if exact calorie matching is not possible.
5. Parse OpenAI's JSON response → return the selected meal option IDs and reasoning.

**System prompt design:**

```
You are a professional dietitian meal planner.
You will receive:
1. A daily caloric target in kcal.
2. A catalogue of available meal options, each with: id, name, meal_time,
   estimated_calories, and ingredients.

Your job is to:
1. Select ONE meal option for each meal time (breakfast, lunch, snack, dinner)
   from the catalogue.
2. The total calories of all selected meals must be as close as possible to
   the daily target without exceeding it by more than 10%.
3. Prioritise nutritional variety and balance across macronutrients.
4. If no combination can match the target within ±10%, select the closest
   combination and explain the gap.

Return ONLY valid JSON with this structure:
{
  "target_calories": 2000,
  "total_calories": 1950,
  "difference": -50,
  "meals": [
    {
      "meal_time": "breakfast",
      "meal_option_id": 5,
      "meal_option_name": "Greek yogurt with granola",
      "estimated_calories": 320,
      "reason": "High protein breakfast within calorie budget"
    },
    ...
  ],
  "notes": "Overall well-balanced plan. Consider adding a small snack
            to reach the exact target."
}

Rules:
- Only use meal_option_ids from the provided catalogue.
- Each meal_time should have exactly one option (if available in the catalogue).
- Never invent meal options that are not in the catalogue.
- If a meal_time has no options in the catalogue, skip it and explain why.
```

#### 3.2 Create endpoint in `App\Controller\MealGeneratorController`

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/api/meals/generate` | Generate a meal plan for a specific date |

**Request body:**

```json
{
  "date": "2026-03-01",
  "target_calories": 2000
}
```

- `date` (required): The date for the meal plan.
- `target_calories` (optional): Override the user's active caloric goal.

**Response (201):**

```json
{
  "date": "2026-03-01",
  "target_calories": 2000,
  "total_calories": 1950,
  "difference": -50,
  "meals": [
    {
      "meal_time": "breakfast",
      "meal_option_id": 5,
      "meal_option_name": "Greek yogurt with granola",
      "estimated_calories": 320,
      "reason": "High protein breakfast within calorie budget"
    },
    {
      "meal_time": "lunch",
      "meal_option_id": 12,
      "meal_option_name": "Grilled chicken salad",
      "estimated_calories": 550,
      "reason": "Lean protein with vegetables for balanced lunch"
    },
    {
      "meal_time": "snack",
      "meal_option_id": 8,
      "meal_option_name": "Fruit and nuts mix",
      "estimated_calories": 280,
      "reason": "Healthy fats and natural sugars for afternoon energy"
    },
    {
      "meal_time": "dinner",
      "meal_option_id": 15,
      "meal_option_name": "Salmon with steamed broccoli",
      "estimated_calories": 800,
      "reason": "Omega-3 rich dinner to complete daily nutrition"
    }
  ],
  "notes": "This plan provides 1950 kcal, 50 below target. Consider a small extra snack."
}
```

**Optional: Auto-save generated plan as a `Meal`:**

An additional query parameter `?save=true` can optionally persist the generated plan directly as a `Meal` entity with the selected `MealOption` IDs linked.

**Error responses:**

| Code | Condition |
|------|-----------|
| 400 | Missing `date` or invalid format |
| 404 | No active caloric goal and no `target_calories` provided |
| 422 | OpenAI could not generate a valid meal plan |
| 500 | OpenAI API failure |

---

### Phase 4 – Weekly Nutritional Analysis

#### 4.1 Create `App\Service\WeeklyAnalysisService`

Analyses a user's week of meals, notes, and caloric goal adherence.

```php
class WeeklyAnalysisService
{
    public function __construct(
        private OpenAIService $openAI,
        private EntityManagerInterface $em,
        private CaloricGoalService $caloricGoalService,
    ) {}

    /**
     * Generate a weekly nutritional analysis report.
     *
     * @param User $user The authenticated user
     * @param \DateTimeImmutable $weekStart The Monday of the target week
     * @return array The full analysis report
     */
    public function generateWeeklyReport(User $user, \DateTimeImmutable $weekStart): array { ... }
}
```

**How it works:**

1. Calculate `weekEnd` = `weekStart` + 6 days (Monday→Sunday).
2. Fetch the user's active `CaloricGoal` for that week.
3. Fetch all `Meal` records for the user in that date range (with their `MealOption`s and `Ingredient`s).
4. Compute raw statistics:
   - Total calories per day.
   - Average daily calories.
   - Days tracked vs. days missed.
   - Caloric surplus/deficit per day relative to goal.
5. Extract all `notes` from meals in the period.
6. Build an OpenAI prompt that includes:
   - The caloric goal and actual daily intake data.
   - The meal options chosen each day.
   - The user's notes (which may contain mood, energy levels, cravings, symptoms).
7. Ask OpenAI to provide:
   - **Achievement summary**: What goals were met.
   - **Deficiency analysis**: Nutritional gaps (e.g. low protein days, missing vegetables).
   - **Notes analysis**: Patterns or concerns from the user's notes (e.g. "User reported fatigue on 3 days — possibly related to low carb intake").
   - **Recommendations**: Actionable suggestions for the following week.
   - **Score**: An overall adherence score 0–100.
8. Cache the report in `WeeklyReport` entity.

**System prompt design:**

```
You are a professional nutritionist reviewing a client's weekly diet log.
You will receive:
1. The client's daily caloric goal.
2. A day-by-day breakdown of what they ate (meals, options chosen, calories).
3. The client's personal notes for each day (may include feelings, energy
   levels, symptoms, or general observations).

Provide a comprehensive weekly analysis including:

1. **goal_adherence**: Object with:
   - score (0-100)
   - days_on_target (number of days within ±10% of goal)
   - days_over (days exceeding goal by >10%)
   - days_under (days below goal by >10%)
   - days_not_tracked (days with no registered meals)

2. **calorie_analysis**: Object with:
   - daily_breakdown: array of { date, target, actual, difference, status }
   - weekly_total, weekly_target, weekly_difference
   - average_daily

3. **nutritional_gaps**: Array of identified deficiencies, e.g.:
   - { "area": "protein", "severity": "moderate",
       "detail": "Only 2 of 7 days included adequate protein sources" }

4. **achievements**: Array of positive observations, e.g.:
   - "Consistently chose balanced breakfasts"
   - "Met caloric goal 5 out of 7 days"

5. **notes_analysis**: Interpretation of the user's own notes:
   - patterns (e.g. "reported tiredness on low-calorie days")
   - concerns (e.g. "mentioned stomach discomfort after dairy meals")
   - mood_trend: "positive" | "neutral" | "negative" | "mixed"

6. **recommendations**: Array of 3-5 actionable tips for the following week.

7. **summary**: A short (2-3 sentence) overall summary in a motivational tone.

Return ONLY valid JSON.
```

#### 4.2 Create endpoint in `App\Controller\WeeklyReportController`

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/reports/weekly` | Generate or retrieve a weekly analysis |
| `GET` | `/api/reports/weekly/history` | List recent weekly reports |

**GET `/api/reports/weekly` query parameters:**

- `week_start` (optional): ISO date string for the Monday of the desired week. Defaults to the current week's Monday.
- `regenerate` (optional): `true` to force regeneration even if a cached report exists.

**Response (200):**

```json
{
  "id": 1,
  "week_start": "2026-02-17",
  "week_end": "2026-02-23",
  "target_calories": 2000,
  "average_calories": 1850,
  "total_calories": 12950,
  "days_tracked": 6,
  "goal_adherence": {
    "score": 78,
    "days_on_target": 4,
    "days_over": 1,
    "days_under": 1,
    "days_not_tracked": 1
  },
  "calorie_analysis": {
    "daily_breakdown": [
      { "date": "2026-02-17", "target": 2000, "actual": 1950, "difference": -50, "status": "on_target" },
      { "date": "2026-02-18", "target": 2000, "actual": 2300, "difference": 300, "status": "over" },
      { "date": "2026-02-19", "target": 2000, "actual": 0, "difference": -2000, "status": "not_tracked" }
    ],
    "weekly_total": 12950,
    "weekly_target": 14000,
    "weekly_difference": -1050,
    "average_daily": 1850
  },
  "nutritional_gaps": [
    {
      "area": "vegetables",
      "severity": "moderate",
      "detail": "Only 3 of 6 tracked days included vegetables in lunch or dinner"
    }
  ],
  "achievements": [
    "Met caloric goal on 4 out of 7 days",
    "Consistent breakfast routine with high-protein options"
  ],
  "notes_analysis": {
    "patterns": ["Reported low energy on Tuesday after skipping snack"],
    "concerns": ["Mentioned bloating after Wednesday dinner"],
    "mood_trend": "mixed"
  },
  "recommendations": [
    "Add a vegetable serving to lunch on days it was missing",
    "Keep a consistent snack to maintain energy levels",
    "Track meals every day for more accurate analysis",
    "Consider swapping Wednesday dinner option to reduce bloating"
  ],
  "summary": "Good week overall! You met your caloric goal most days and maintained a solid breakfast routine. Focus on adding more vegetables and tracking consistently for even better results next week.",
  "generated_at": "2026-02-24T10:30:00+00:00"
}
```

**GET `/api/reports/weekly/history` query parameters:**

- `limit` (optional): Number of reports to return (default 8).

**Response (200):**

```json
[
  {
    "id": 1,
    "week_start": "2026-02-17",
    "week_end": "2026-02-23",
    "score": 78,
    "average_calories": 1850,
    "target_calories": 2000,
    "days_tracked": 6,
    "summary": "Good week overall! ...",
    "generated_at": "2026-02-24T10:30:00+00:00"
  }
]
```

**Error responses:**

| Code | Condition |
|------|-----------|
| 400 | Invalid `week_start` format (must be a Monday) |
| 404 | No meals found for the requested week |
| 404 | No active caloric goal for the requested week |
| 422 | OpenAI could not generate a valid analysis |
| 401 | Unauthorized |

---

### Phase 5 – OpenAPI Documentation

#### 5.1 New schemas

```yaml
CaloricGoal:
  type: object
  properties:
    id:
      type: integer
    daily_calories:
      type: integer
      example: 2000
    start_date:
      type: string
      format: date
      example: "2026-03-01"
    end_date:
      type: string
      format: date
      nullable: true
      example: "2026-03-31"
    label:
      type: string
      nullable: true
      example: "cutting"
    notes:
      type: string
      nullable: true
    created_at:
      type: string
      format: date-time
    updated_at:
      type: string
      format: date-time
      nullable: true

CaloricGoalRequest:
  type: object
  required: [daily_calories, start_date]
  properties:
    daily_calories:
      type: integer
      minimum: 500
      maximum: 10000
      example: 2000
    start_date:
      type: string
      format: date
    end_date:
      type: string
      format: date
      nullable: true
    label:
      type: string
      maxLength: 100
    notes:
      type: string

MealPlanRequest:
  type: object
  required: [date]
  properties:
    date:
      type: string
      format: date
      example: "2026-03-01"
    target_calories:
      type: integer
      nullable: true
      description: "Override the user's active caloric goal"

MealPlanResponse:
  type: object
  properties:
    date:
      type: string
      format: date
    target_calories:
      type: integer
    total_calories:
      type: integer
    difference:
      type: integer
    meals:
      type: array
      items:
        type: object
        properties:
          meal_time:
            type: string
          meal_option_id:
            type: integer
          meal_option_name:
            type: string
          estimated_calories:
            type: number
          reason:
            type: string
    notes:
      type: string

WeeklyReport:
  type: object
  properties:
    id:
      type: integer
    week_start:
      type: string
      format: date
    week_end:
      type: string
      format: date
    target_calories:
      type: integer
    average_calories:
      type: integer
    total_calories:
      type: integer
    days_tracked:
      type: integer
    goal_adherence:
      type: object
    calorie_analysis:
      type: object
    nutritional_gaps:
      type: array
    achievements:
      type: array
    notes_analysis:
      type: object
    recommendations:
      type: array
    summary:
      type: string
    generated_at:
      type: string
      format: date-time
```

#### 5.2 New paths

- `GET /api/caloric-goals` — List caloric goals
- `GET /api/caloric-goals/active` — Get active goal
- `GET /api/caloric-goals/{id}` — Get goal by ID
- `POST /api/caloric-goals` — Create a caloric goal
- `PUT /api/caloric-goals/{id}` — Update a caloric goal
- `DELETE /api/caloric-goals/{id}` — Delete a caloric goal
- `POST /api/meals/generate` — Generate AI meal plan
- `GET /api/reports/weekly` — Get/generate weekly report
- `GET /api/reports/weekly/history` — List recent weekly reports

---

### Phase 6 – Endpoint Documentation

Create the following doc files:

| File | Content |
|------|---------|
| `docs/endpoint-caloric-goals-list.md` | GET /api/caloric-goals |
| `docs/endpoint-caloric-goals-active.md` | GET /api/caloric-goals/active |
| `docs/endpoint-caloric-goals-create.md` | POST /api/caloric-goals |
| `docs/endpoint-caloric-goals-update.md` | PUT /api/caloric-goals/{id} |
| `docs/endpoint-caloric-goals-delete.md` | DELETE /api/caloric-goals/{id} |
| `docs/endpoint-meals-generate.md` | POST /api/meals/generate |
| `docs/endpoint-reports-weekly.md` | GET /api/reports/weekly |
| `docs/endpoint-reports-weekly-history.md` | GET /api/reports/weekly/history |

---

### Phase 7 – Testing & QA

#### Unit tests

- `CaloricGoalServiceTest` — overlap detection, active goal resolution, CRUD
- `MealGeneratorServiceTest` — mock OpenAI, verify option selection logic
- `WeeklyAnalysisServiceTest` — mock OpenAI, verify statistics computation and report caching

#### Integration tests

- CRUD endpoints for caloric goals (create, read, update, delete, overlap error)
- `POST /api/meals/generate` with active goal → valid meal plan
- `POST /api/meals/generate` without goal and no override → 404
- `GET /api/reports/weekly` → generates and caches report
- `GET /api/reports/weekly?regenerate=true` → regenerates cached report
- `GET /api/reports/weekly/history` → returns past reports

#### QA checklist

- [ ] Caloric goals enforce non-overlapping date ranges
- [ ] Only the authenticated user's goals are accessible
- [ ] Active goal correctly determined for any date
- [ ] Meal generator uses only existing MealOption records
- [ ] Generated meal plan respects the ±10% calorie tolerance
- [ ] All MealTimes are covered when options are available
- [ ] Weekly report correctly computes daily/weekly statistics
- [ ] User notes are included in AI analysis
- [ ] Reports are cached and re-served without re-calling OpenAI
- [ ] `regenerate=true` forces a fresh OpenAI call
- [ ] JWT required — anonymous access returns 401 on all endpoints
- [ ] Edge case: week with zero meals → report indicates no data
- [ ] Edge case: goal with no end date works as ongoing

---

## Dependency on Existing Action Plan

This plan **depends on and extends** the work defined in `docs/action-plan-file-import-meal-options.md`:

| Dependency | Status | Impact |
|------------|--------|--------|
| `OpenAIService` (generic) | Already implemented | Reused by `MealGeneratorService` and `WeeklyAnalysisService` |
| `MealOption` + `Ingredient` entities | Existing | MealGenerator selects from these |
| `MealTime` entity | Existing | Used for meal time categorisation |
| `Meal.notes` field | Existing | Analysed in weekly reports |
| File import endpoint | In progress | Imports MealOptions that meal generator can use |

**Recommended execution order:**
1. Complete **Phase 1-3 of file-import plan** first (OpenAI refactor + file extraction + import service).
2. Then start **Phase 1 of this plan** (new entities + migrations).
3. Phases 2-4 can proceed independently after Phase 1.

---

## File Summary

| File | Action |
|------|--------|
| `src/Entity/CaloricGoal.php` | **CREATE** — Caloric goal entity |
| `src/Entity/WeeklyReport.php` | **CREATE** — Weekly report cache entity |
| `src/Repository/CaloricGoalRepository.php` | **CREATE** — Goal queries |
| `src/Repository/WeeklyReportRepository.php` | **CREATE** — Report queries |
| `src/Service/CaloricGoalService.php` | **CREATE** — Goal business logic |
| `src/Service/MealGeneratorService.php` | **CREATE** — AI meal plan generation |
| `src/Service/WeeklyAnalysisService.php` | **CREATE** — AI weekly analysis |
| `src/Controller/CaloricGoalController.php` | **CREATE** — Goal CRUD endpoints |
| `src/Controller/MealGeneratorController.php` | **CREATE** — Meal generation endpoint |
| `src/Controller/WeeklyReportController.php` | **CREATE** — Weekly report endpoints |
| `migrations/VersionXXX_caloric_goal.php` | **CREATE** — Doctrine migration |
| `migrations/VersionXXX_weekly_report.php` | **CREATE** — Doctrine migration |
| `config/services.yaml` | **MODIFY** — Register new services if explicit config needed |
| `public/openapi.yaml` | **MODIFY** — Add new endpoints + schemas |
| `docs/endpoint-caloric-goals-*.md` | **CREATE** — Endpoint docs (5 files) |
| `docs/endpoint-meals-generate.md` | **CREATE** — Endpoint doc |
| `docs/endpoint-reports-weekly.md` | **CREATE** — Endpoint doc |
| `docs/endpoint-reports-weekly-history.md` | **CREATE** — Endpoint doc |
| `AGENTS.md` | **MODIFY** — Add action plan reference |

---

## Future Extensibility

Building on this infrastructure, the system can later support:

- **Monthly reports** — Aggregate weekly reports into monthly trends.
- **Goal auto-adjustment** — AI suggests adjusting daily calories based on weekly trends.
- **Macro tracking** — Extend `CaloricGoal` with protein/carb/fat targets.
- **Meal rating** — Users rate meals; AI uses ratings to improve future suggestions.
- **Progress charts** — Weekly scores over time, calorie trend graphs.
- **Push notifications** — Alert users if they're consistently under/over target mid-week.

---

## References

- `docs/action-plan-file-import-meal-options.md` — File import plan (prerequisite)
- `docs/action-plan-openai-shopping-list.md` — Shopping list OpenAI integration
- `src/Service/OpenAIService.php` — Generic OpenAI client (already implemented)
- `src/Entity/Meal.php` — Meal entity with `notes` field
- `src/Entity/MealOption.php` — MealOption with calories and ingredients
- `AGENTS.md` — Project methodology and action plan history

---

*Prepared: 2026-02-24*

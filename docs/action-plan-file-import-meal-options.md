# Action Plan: File Upload & AI-Powered Meal Option Import from Nutritionist Diets

## Objective

Allow users to upload a **diet document provided by their nutritionist** (`.pdf`, `.docx`, `.md`). These documents typically describe daily meal plans with meal names grouped by meal time (breakfast, lunch, snack, dinner), but often **lack detailed ingredient lists and calorie counts**.

The system will:
1. Extract the document's text.
2. Send it to **OpenAI** to identify every distinct meal option.
3. Use OpenAI to **estimate the calories** for each meal option.
4. Use OpenAI to **infer the likely ingredients and quantities** for each meal option (since nutritionist documents often only name the dish, not the full recipe).
5. Automatically create `MealOption` and `Ingredient` records in the database.

As a prerequisite, the current `OpenAIShoppingListService` must be refactored into a **generic, reusable OpenAI service** that can serve this use case and future ones.

---

## Scope

| Area | Change |
|------|--------|
| **Service layer** | Refactor `OpenAIShoppingListService` → generic `OpenAIService` + specialised sub-services |
| **File handling** | New `FileTextExtractorService` to convert PDF/DOCX/MD to plain text |
| **New endpoint** | `POST /api/meal-options/import` (multipart file upload of nutritionist diet) |
| **AI processing** | OpenAI identifies meals, **estimates calories**, and **infers ingredients** from diet text |
| **Entities** | No schema changes – we reuse `MealOption`, `Ingredient`, `MealTime` |
| **OpenAPI** | Update `public/openapi.yaml` with the new endpoint and schemas |
| **Documentation** | New endpoint doc + update `AGENTS.md` |

---

## Architecture

```
┌──────────────────────┐
│    Client Upload      │  POST /api/meal-options/import
│  (Nutritionist diet   │  Content-Type: multipart/form-data
│   PDF/DOCX/MD)        │
└────────┬──────────────┘
         │
         ▼
┌──────────────────────────┐
│  FileImportController    │  Validates file, delegates processing
└────────┬─────────────────┘
         │
         ▼
┌──────────────────────────┐
│ FileTextExtractorService │  Extracts plain text from file
│ (PDF → Smalot/PdfParser  │   based on MIME type
│  DOCX → PhpWord          │
│  MD   → native read)     │
└────────┬─────────────────┘
         │  plain text
         ▼
┌──────────────────────────┐
│      OpenAIService       │  Generic chat-completion caller
│  (refactored from        │  • chat(system, user, options)
│   OpenAIShoppingList…)   │  • Handles auth, HTTP, parsing
└────────┬─────────────────┘
         │
         ▼
┌──────────────────────────┐
│ MealOptionImportService  │  Builds prompt, calls OpenAIService,
│                          │  OpenAI identifies meals, estimates
│                          │  calories & infers ingredients.
│                          │  Maps JSON → MealOption + Ingredient
│                          │  entities, persists to DB
└────────┬─────────────────┘
         │
         ▼
┌──────────────────────────┐
│   Database (Doctrine)    │  MealOption, Ingredient, MealTime
└──────────────────────────┘
```

---

## Detailed Steps

### Phase 1 – Refactor OpenAI Service (Generic)

#### 1.1 Create `App\Service\OpenAIService`

A generic, reusable OpenAI HTTP client:

```php
class OpenAIService
{
    public function __construct(
        private HttpClientInterface $client,
        private string $openaiApiKey,
    ) {}

    /**
     * Send a chat completion request to OpenAI.
     *
     * @param string $systemPrompt  System-level instruction
     * @param string $userPrompt    User-level content
     * @param string $model         Model name (default: gpt-4)
     * @param float  $temperature   Sampling temperature
     * @return string  Raw content from the assistant response
     */
    public function chat(
        string $systemPrompt,
        string $userPrompt,
        string $model = 'gpt-4',
        float $temperature = 0.2,
    ): string { ... }

    /**
     * Same as chat() but attempts to decode JSON from the response.
     *
     * @return array Decoded JSON or ['error' => '...']
     */
    public function chatJson(
        string $systemPrompt,
        string $userPrompt,
        string $model = 'gpt-4',
        float $temperature = 0.2,
    ): array { ... }
}
```

#### 1.2 Refactor `OpenAIShoppingListService`

- Remove HTTP/parsing logic; delegate to `OpenAIService`.
- Keep only shopping-list-specific prompt building.

```php
class OpenAIShoppingListService
{
    public function __construct(private OpenAIService $openAI) {}

    public function generateShoppingList(array $meals): array
    {
        $prompt = $this->buildPrompt($meals);
        return $this->openAI->chatJson(
            'You are a helpful assistant that generates shopping lists from meal plans.',
            $prompt,
        );
    }
    // buildPrompt() stays the same
}
```

#### 1.3 Update `services.yaml`

```yaml
App\Service\OpenAIService:
    arguments:
        $openaiApiKey: '%env(OPENAI_API_KEY)%'

# OpenAIShoppingListService autowires OpenAIService automatically
```

---

### Phase 2 – File Text Extraction

#### 2.1 Install PHP dependencies

Run inside the PHP container:

```bash
composer require smalot/pdfparser phpoffice/phpword
```

#### 2.2 Create `App\Service\FileTextExtractorService`

```php
class FileTextExtractorService
{
    private const SUPPORTED_MIMES = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/markdown',
        'text/plain',
    ];

    /**
     * Extract plain text from an uploaded file.
     *
     * @param UploadedFile $file
     * @return string Extracted text
     * @throws \InvalidArgumentException on unsupported type
     */
    public function extract(UploadedFile $file): string { ... }

    private function extractPdf(string $path): string { ... }
    private function extractDocx(string $path): string { ... }
    private function extractMarkdown(string $path): string { ... }
}
```

Supported MIME types:
| Extension | MIME |
|-----------|------|
| `.pdf` | `application/pdf` |
| `.docx` | `application/vnd.openxmlformats-officedocument.wordprocessingml.document` |
| `.md` | `text/markdown` / `text/plain` |

---

### Phase 3 – Meal Option Import Service

#### 3.1 Create `App\Service\MealOptionImportService`

Responsibilities:
1. Build a specialised prompt that instructs OpenAI to analyse a **nutritionist diet document**.
2. Identify every distinct meal option mentioned in the diet.
3. **Estimate calories** for each meal option (nutritionist docs rarely include them).
4. **Infer the likely ingredients and quantities** for each meal (nutritionist docs usually name the dish but not the full recipe — e.g. "Grilled chicken salad" without listing each ingredient).
5. Call `OpenAIService::chatJson()`.
6. Validate the structured response.
7. Map to `MealOption` + `Ingredient` entities and persist them.

**Prompt design** (system message):

```
You are a professional nutrition data extraction assistant.
You will receive the text of a diet plan written by a nutritionist.

Your job is to:
1. Identify every distinct meal option mentioned in the document.
2. Classify each meal into its meal time (breakfast, lunch, snack, dinner).
3. ESTIMATE the total calories for each meal option based on standard
   nutritional databases. The nutritionist document usually does NOT
   include calorie counts — you must calculate them.
4. INFER the full ingredient list with realistic quantities for each meal.
   Nutritionist documents typically only name the dish (e.g. "Greek yogurt
   with granola and berries") without listing every ingredient. You must
   decompose each meal into its individual ingredients with estimated
   weights/volumes.

Return ONLY valid JSON with this exact structure:
{
  "meal_options": [
    {
      "name": "Greek yogurt with granola and berries",
      "description": "Healthy breakfast option recommended by nutritionist",
      "meal_time": "breakfast",
      "estimated_calories": 320,
      "ingredients": [
        { "name": "Greek yogurt", "quantity": 200, "unit": "g" },
        { "name": "granola", "quantity": 40, "unit": "g" },
        { "name": "mixed berries", "quantity": 80, "unit": "g" }
      ]
    }
  ]
}

Rules:
- meal_time must be one of: breakfast, lunch, snack, dinner
- Always estimate quantities even when the document does not specify them;
  use standard single-serving portions.
- Always estimate calories based on the inferred ingredients and quantities.
- unit should be one of: g, ml, unit, tbsp, tsp, cup
- Do not invent meals that are not mentioned in the document.
- If a meal is ambiguous, use the most common interpretation.
```

**Entity mapping logic:**
- Look up `MealTime` by name.
- Create `MealOption` with name, description, estimatedCalories (from OpenAI estimation), and linked MealTime.
- Create `Ingredient` children for each inferred ingredient.
- Persist and flush.

#### 3.2 Return format

```json
{
  "imported": 5,
  "meal_options": [ /* serialised MealOption objects */ ]
}
```

---

### Phase 4 – Controller & Endpoint

#### 4.1 Create `App\Controller\FileImportController`

```
POST /api/meal-options/import
Content-Type: multipart/form-data

Form fields:
  - file (required): Nutritionist diet document (.pdf, .docx, .md)
```

**Authentication:** JWT Bearer (IS_AUTHENTICATED_FULLY).

**Flow:**
1. Validate uploaded file (presence, size ≤ 5 MB, allowed MIME type).
2. Call `FileTextExtractorService::extract()`.
3. Validate extracted text is non-empty.
4. Call `MealOptionImportService::importFromText()`.
5. Return JSON with created meal options (201).

**Error responses:**

| Code | Condition |
|------|-----------|
| 400 | No file uploaded / invalid MIME / empty content |
| 413 | File too large (> 5 MB) |
| 422 | OpenAI could not extract valid meal data |
| 500 | OpenAI API failure |

---

### Phase 5 – OpenAPI Documentation

#### 5.1 New schema: `FileImportResponse`

```yaml
FileImportResponse:
  type: object
  properties:
    imported:
      type: integer
      description: Number of meal options created
      example: 3
    meal_options:
      type: array
      items:
        $ref: '#/components/schemas/MealOption'
```

#### 5.2 New path: `POST /api/meal-options/import`

```yaml
/api/meal-options/import:
  post:
    tags: [Meal Options]
    summary: Import meal options from a nutritionist diet document
    description: |
      Upload a PDF, DOCX, or Markdown file containing a diet plan from a
      nutritionist. The system extracts the text, sends it to OpenAI to
      identify meal options, estimate their calories, and infer their full
      ingredient lists. MealOption + Ingredient records are created automatically.
    security:
      - bearerAuth: []
    requestBody:
      required: true
      content:
        multipart/form-data:
          schema:
            type: object
            required: [file]
            properties:
              file:
                type: string
                format: binary
                description: "Nutritionist diet document (.pdf, .docx, .md). Max 5 MB."
    responses:
      '201':
        description: Meal options imported successfully
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/FileImportResponse'
      '400':
        description: Invalid file or missing upload
      '413':
        description: File exceeds 5 MB limit  
      '422':
        description: Could not extract valid meal data from file
      '401':
        description: Unauthorized – missing or invalid JWT
```

---

### Phase 6 – Endpoint Documentation

Create `docs/endpoint-meal-options-import.md` with:
- URL, method, auth, content type
- Request/response examples
- Supported file formats
- Error codes

---

### Phase 7 – Testing & QA

#### Unit tests
- `OpenAIServiceTest` – mock HTTP responses, verify prompt forwarding
- `FileTextExtractorServiceTest` – test each format with sample files
- `MealOptionImportServiceTest` – mock OpenAI response, verify entity creation

#### Integration tests
- `POST /api/meal-options/import` with valid PDF → 201 + correct meal options
- Upload unsupported file type → 400
- Upload empty file → 400
- Upload oversized file → 413
- OpenAI returns invalid JSON → 422

#### QA checklist
- [ ] PDF extraction preserves meal names from nutritionist diet
- [ ] DOCX extraction handles tables and lists commonly used in diet plans
- [ ] Markdown extraction works with headers and bullet points
- [ ] OpenAI correctly estimates calories for each meal option
- [ ] OpenAI infers realistic ingredients even when the diet only names the dish
- [ ] Inferred ingredient quantities match standard single-serving portions
- [ ] MealTime mapping falls back gracefully for unknown meal times
- [ ] Duplicate meal option names are handled (create new vs skip)
- [ ] Ingredients are correctly linked to their meal options
- [ ] JWT required – anonymous access returns 401

---

## File Summary

| File | Action |
|------|--------|
| `src/Service/OpenAIService.php` | **CREATE** – Generic OpenAI chat client |
| `src/Service/OpenAIShoppingListService.php` | **MODIFY** – Delegate to `OpenAIService` |
| `src/Service/FileTextExtractorService.php` | **CREATE** – PDF/DOCX/MD text extraction |
| `src/Service/MealOptionImportService.php` | **CREATE** – AI extraction + DB persistence |
| `src/Controller/FileImportController.php` | **CREATE** – Upload endpoint |
| `config/services.yaml` | **MODIFY** – Register `OpenAIService` with API key |
| `public/openapi.yaml` | **MODIFY** – Add import endpoint + schemas |
| `docs/endpoint-meal-options-import.md` | **CREATE** – Endpoint documentation |
| `AGENTS.md` | **MODIFY** – Add action plan reference |
| `composer.json` | **MODIFY** – Add `smalot/pdfparser`, `phpoffice/phpword` |

---

## Dependencies to Install

```bash
docker-compose exec php bash
composer require smalot/pdfparser phpoffice/phpword
```

---

## Future Extensibility

The refactored `OpenAIService` is designed for reuse. Future use cases:

- **Calorie estimation** – Send ingredient list, receive calorie breakdown
- **Menu suggestions** – Ask OpenAI to suggest meals based on preferences
- **Nutritional analysis** – Analyse a full day's meals for macros
- **Recipe generation** – Generate recipes from available ingredients

Each new feature only needs its own specialised service that calls `OpenAIService::chat()` or `chatJson()`.

---

## References

- `docs/action-plan-openai-shopping-list.md` – Prior OpenAI integration plan
- `docs/endpoint-shopping-list.md` – Shopping list endpoint
- `AGENTS.md` – Project methodology and action plan history

---

*Prepared: 2026-02-24*

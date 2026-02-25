# Endpoint: Import Meal Options from Nutritionist Diet Document

## Overview

This endpoint allows users to upload a diet document provided by their nutritionist. The system extracts the text content, sends it to OpenAI, and automatically creates `MealOption` + `Ingredient` records in the database.

OpenAI is responsible for:
- **Identifying** every distinct meal option in the document.
- **Classifying** each meal by meal time (breakfast, lunch, snack, dinner).
- **Estimating** total calories per meal (nutritionist documents rarely include them).
- **Inferring** the full ingredient list with realistic quantities for each meal (nutritionist documents typically only name the dish, not the recipe).

---

## Request

| Property | Value |
|----------|-------|
| **URL** | `POST /api/meal-options/import` |
| **Auth** | JWT Bearer token (required) |
| **Content-Type** | `multipart/form-data` |

### Form Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `file` | binary | ✅ | Nutritionist diet document. Max 5 MB. |

### Supported File Formats

| Extension | MIME type |
|-----------|-----------|
| `.pdf` | `application/pdf` |
| `.docx` | `application/vnd.openxmlformats-officedocument.wordprocessingml.document` |
| `.md` | `text/markdown` |
| `.txt` | `text/plain` |

---

## Example Request

```bash
curl -X POST http://localhost/api/meal-options/import \
  -H "Authorization: Bearer <JWT_TOKEN>" \
  -F "file=@/path/to/my-diet.pdf"
```

---

## Example Nutritionist Document (input text)

```
WEEKLY DIET PLAN – Patient: John Doe

MONDAY
Breakfast: Oatmeal with banana and honey
Lunch: Grilled chicken breast with steamed broccoli and brown rice
Snack: Greek yogurt with granola and blueberries
Dinner: Baked salmon with mixed salad and olive oil dressing

TUESDAY
Breakfast: Scrambled eggs with whole wheat toast and avocado
Lunch: Turkey sandwich on rye bread with tomato and lettuce
Snack: Apple with almond butter
Dinner: Lentil soup with a slice of whole grain bread
```

---

## Example Response (201 Created)

```json
{
  "imported": 4,
  "meal_options": [
    {
      "id": 42,
      "name": "Oatmeal with banana and honey",
      "description": "Breakfast option recommended by nutritionist",
      "estimated_calories": 380,
      "meal_time": {
        "id": 1,
        "name": "breakfast",
        "label": "Breakfast"
      },
      "ingredients": [
        { "id": 101, "name": "Rolled oats",  "quantity": 80, "unit": "g" },
        { "id": 102, "name": "Banana",        "quantity": 1,  "unit": "unit" },
        { "id": 103, "name": "Honey",         "quantity": 1,  "unit": "tbsp" },
        { "id": 104, "name": "Skimmed milk",  "quantity": 200,"unit": "ml" }
      ]
    },
    {
      "id": 43,
      "name": "Grilled chicken breast with steamed broccoli and brown rice",
      "description": "Balanced lunch option with protein and complex carbohydrates",
      "estimated_calories": 520,
      "meal_time": {
        "id": 2,
        "name": "lunch",
        "label": "Lunch"
      },
      "ingredients": [
        { "id": 105, "name": "Chicken breast", "quantity": 180, "unit": "g" },
        { "id": 106, "name": "Broccoli",       "quantity": 150, "unit": "g" },
        { "id": 107, "name": "Brown rice",     "quantity": 80,  "unit": "g" },
        { "id": 108, "name": "Olive oil",      "quantity": 1,   "unit": "tbsp" }
      ]
    }
  ]
}
```

---

## Error Responses

| Status | Condition | Example body |
|--------|-----------|--------------|
| `400` | No file uploaded | `{"error": "No file uploaded. Send the document as a multipart/form-data field named \"file\"."}` |
| `400` | Unsupported file type | `{"error": "Unsupported file type \"application/zip\".", "allowed_types": [...]}` |
| `400` | Empty file / no readable text | `{"error": "The uploaded file contains no readable text."}` |
| `401` | Missing or invalid JWT | *(standard Symfony JWT error)* |
| `413` | File > 5 MB | `{"error": "File exceeds the maximum allowed size of 5 MB."}` |
| `422` | OpenAI could not extract valid meal options | `{"error": "OpenAI could not extract meal options: ..."}` |
| `500` | OpenAI API unreachable or unexpected error | `{"error": "OpenAI API returned HTTP 500"}` |

---

## Processing Pipeline

```
Uploaded file
     │
     ▼
FileTextExtractorService        ← converts PDF/DOCX/MD to plain text
     │
     ▼
OpenAIService::chatJson()       ← sends text + specialised prompt to GPT-4
     │
     ▼
MealOptionImportService         ← maps AI response → MealOption + Ingredient entities
     │
     ▼
Doctrine EntityManager          ← persists to database
     │
     ▼
JSON response (201)             ← returns created meal options
```

---

## Notes

- The endpoint is idempotent in the sense that uploading the same document again will create **additional** duplicate meal options. Duplicate-checking is not performed at this stage.
- OpenAI ingredient estimates are based on standard single-serving portions and standard nutritional databases. Values are approximations and should be reviewed by the user.
- The `meal_time` is inferred from context (e.g. "Breakfast", "Cena", "Merienda"). Unknown meal times fall back to the first available `MealTime` in the database.
- Ingredient units are normalised to one of: `g`, `ml`, `unit`, `tbsp`, `tsp`, `cup`.

---

## Related

- [action-plan-file-import-meal-options.md](action-plan-file-import-meal-options.md) — Full design and implementation plan
- [endpoint-meal-options-create.md](endpoint-meal-options-create.md) — Manual meal option creation
- [endpoint-shopping-list.md](endpoint-shopping-list.md) — AI-generated shopping list

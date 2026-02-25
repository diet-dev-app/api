# OpenAPI Source Files

This folder contains the **source** OpenAPI 3.0 specification split by domain.
The canonical bundled file consumed by Swagger UI is `public/openapi.yaml`.

## Folder structure

```
openapi/
  openapi.yaml                  ← main entry point (info, servers, tags, $refs)
  components/
    schemas/
      auth.yaml                 ← RegisterRequest, LoginRequest, LoginResponse
      user.yaml                 ← UserProfile
      ingredients.yaml          ← Ingredient, IngredientInput
      meal-times.yaml           ← MealTime
      meal-options.yaml         ← MealOption, MealOptionCreateRequest, MealOptionUpdateRequest
      meals.yaml                ← Meal, MealCreateRequest, MealUpdateRequest, MealSimple, ...
      shopping-list.yaml        ← ShoppingListItem, ShoppingListResponse
      file-import.yaml          ← FileImportResponse
      caloric-goals.yaml        ← CaloricGoal, CaloricGoalRequest
      meal-generation.yaml      ← MealPlanRequest, MealPlanResponse
      weekly-reports.yaml       ← WeeklyReport, WeeklyReportSummary
      common.yaml               ← MessageResponse, ErrorResponse, ValidationErrorResponse
  paths/
    auth.yaml                   ← /, /api/register, /api/login
    user.yaml                   ← /api/user
    meals.yaml                  ← /api/meals, /api/meals/{id}
    meal-options.yaml           ← /api/meal-options, /api/meal-options/{id}, /api/meal-options/import
    shopping-list.yaml          ← /api/shopping-list
    caloric-goals.yaml          ← /api/caloric-goals, /api/caloric-goals/active, /api/caloric-goals/{id}
    meal-generation.yaml        ← /api/meals/generate
    weekly-reports.yaml         ← /api/reports/weekly, /api/reports/weekly/history
```

## How to edit

Edit the relevant domain file. For example:
- New endpoint for meals → edit `paths/meals.yaml`
- New schema → add to the relevant `components/schemas/*.yaml` file
- New domain → create new files in `paths/` and `components/schemas/`, then register in `openapi.yaml`

## Bundling into public/openapi.yaml

After any change, regenerate the bundled file for Swagger UI:

```bash
# Using Redocly CLI (recommended)
npx @redocly/cli bundle openapi/openapi.yaml -o public/openapi.yaml

# Using swagger-cli
npx swagger-cli bundle openapi/openapi.yaml -o public/openapi.yaml --type yaml
```

Or use the provided helper script:

```bash
./scripts/bundle-openapi.sh
```

## Validation

```bash
npx @redocly/cli lint openapi/openapi.yaml
```

# Action Plan: OpenAI Integration for Dynamic Shopping List Generation

## Objective
Integrate OpenAI to generate a dynamic shopping list based on a user's meal plan for a selected date range, considering meal portions, food options, and caloric needs. Expose this as a new API endpoint.

---

## Steps

### 1. Requirements & Analysis
- Review current data models for meals, meal options, and user menus.
- Define the input structure: user, date range, selected meals/options, portions, and caloric targets.
- Define the output: structured shopping list (grouped by food type, quantities, optionally nutritional info).

### 2. OpenAI Integration
- Choose OpenAI API (GPT-4 or function calling) for generating shopping lists.
- Create a service class (e.g., `OpenAIShoppingListService`) to:
  - Format prompt with user menu data.
  - Call OpenAI API and handle responses.
  - Parse and validate the returned shopping list.
- Store API keys securely (env vars, Symfony secrets).

### 3. Backend Changes
- Add new endpoint: `GET /api/shopping-list?start=YYYY-MM-DD&end=YYYY-MM-DD`
- Controller action:
  - Validate user and input dates.
  - Fetch user's menu for the range.
  - Aggregate meal options, portions, and calories.
  - Call `OpenAIShoppingListService`.
  - Return the generated shopping list as JSON.
- Update or create tests for the new endpoint and service.

### 4. Frontend Changes (if applicable)
- Add UI to select date range and view generated shopping list.
- Optionally allow user to adjust portions or exclude items.

### 5. Documentation
- Document the new endpoint in `docs/endpoint-shopping-list.md`.
- Update `AGENTS.md` and action plan history.
- Add usage and QA notes.

### 6. QA & Validation
- Test with various menus, date ranges, and edge cases (empty menu, extreme portions).
- Validate OpenAI output for accuracy and completeness.
- Ensure error handling for API failures and invalid input.

---

## QA Guidelines
- Verify shopping list matches menu items and portions.
- Check for correct grouping and quantities.
- Test endpoint authentication and permissions.
- Simulate OpenAI API errors and check fallback/response.

---

## References
- See `AGENTS.md` for methodology.
- See `docs/` for endpoint documentation.

---

*Prepared: 2026-02-15*
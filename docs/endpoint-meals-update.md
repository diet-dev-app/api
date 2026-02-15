# API Endpoint: Update Meal

**Endpoint:** `/api/meals/{id}`
**Method:** `PUT`

Updates a meal for the authenticated user.

## Headers
- `Authorization: Bearer <jwt>`

## Request Body Parameters
- `name` (string, optional): Name of the meal
- `calories` (integer, optional): Calories in the meal
- `date` (string, optional, ISO 8601): Date of the meal
- `notes` (string, optional): Notes about the meal
- `meal_options` (array of int, optional): IDs of MealOption to assign to this meal (replaces all previous options)

## Example Request
```
curl -X PUT http://localhost:8000/api/meals/1 \
  -H "Authorization: Bearer <jwt>" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Dinner",
    "calories": 700
  }'
```

## Success Response
- **Code:** 200
- **Content:** Updated meal object (see [List Meals](endpoint-meals-list.md) for structure)

## Error Response
- **Code:** 400 Bad Request
- **Content:** `{ "errors": { ... } }`
- **Code:** 401 Unauthorized
- **Content:** `{ "error": "Unauthorized" }`
- **Code:** 404 Not Found
- **Content:** `{ "error": "Meal not found" }`

# API Endpoint: Create Meal

**Endpoint:** `/api/meals`
**Method:** `POST`

Creates a new meal for the authenticated user.

## Headers
- `Authorization: Bearer <jwt>`

## Request Body Parameters
- `name` (string, required): Name of the meal
- `calories` (integer, required): Calories in the meal
- `date` (string, required, ISO 8601): Date of the meal
- `notes` (string, optional): Notes about the meal

## Example Request
```
curl -X POST http://localhost:8000/api/meals \
  -H "Authorization: Bearer <jwt>" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Lunch",
    "calories": 600,
    "date": "2024-02-15T13:00:00Z",
    "notes": "Chicken salad"
  }'
```

## Success Response
- **Code:** 201
- **Content:** Meal object

## Error Response
- **Code:** 400 Bad Request
- **Content:** `{ "error": "name, calories, and date are required" }`
- **Code:** 401 Unauthorized
- **Content:** `{ "error": "Unauthorized" }`

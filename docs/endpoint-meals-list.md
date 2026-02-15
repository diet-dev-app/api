# API Endpoint: List Meals

**Endpoint:** `/api/meals`
**Method:** `GET`


Returns a list of meals for the authenticated user. Each meal includes its meal options grouped by meal time.

## Headers
- `Authorization: Bearer <jwt>`

## Example Request
```
curl http://localhost:8000/api/meals \
  -H "Authorization: Bearer <jwt>"
```

## Success Response
- **Code:** 200
- **Content:** Array of meal objects

Each meal object contains:

```
{
  "id": 1,
  "name": "Breakfast",
  "calories": 350,
  "date": "2026-02-15T08:00:00+00:00",
  "notes": "Oatmeal and fruit",
  "meal_times": [
    {
      "id": 1,
      "name": "desayuno",
      "label": "Desayuno",
      "options": [
        {
          "id": 3,
          "name": "Bol de avena con fruta",
          "description": "Avena + fruta + (opcional) crema cacahuete"
        },
        // ... more options for this meal time
      ]
    },
    // ... more meal times for this meal
  ]
}
```

If a meal has no options, `meal_times` will be an empty array.

## Error Response
- **Code:** 401 Unauthorized
- **Content:** `{ "error": "Unauthorized" }`

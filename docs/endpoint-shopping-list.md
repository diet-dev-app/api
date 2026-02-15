# API Endpoint: Shopping List by Menu

**Endpoint:** `/api/shopping-list`
**Method:** `GET`

Generates a shopping list based on the user's meals for a given date range, using OpenAI to aggregate ingredients and quantities.

## Headers
- `Authorization: Bearer <jwt>`

## Query Parameters
- `start` (string, required): Start date (YYYY-MM-DD)
- `end` (string, required): End date (YYYY-MM-DD)

## Example Request
```
curl -X GET "http://localhost:8000/api/shopping-list?start=2026-02-15&end=2026-02-21" \
  -H "Authorization: Bearer <jwt>"
```

## Success Response
- **Code:** 200
- **Content:**
```
{
  "proteins": [
    { "name": "Chicken breast", "quantity": "1.2kg" },
    { "name": "Eggs", "quantity": "12" }
  ],
  "carbohydrates": [
    { "name": "Rice", "quantity": "500g" }
  ],
  ...
}
```

## Error Responses
- **400 Bad Request:** `{ "error": "Missing start or end date" }`
- **404 Not Found:** `{ "error": "No meals found for this range" }`
- **500 Internal Server Error:** `{ "error": "Could not parse shopping list" }`

---

*See also: docs/action-plan-openai-shopping-list.md*
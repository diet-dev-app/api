# API Endpoint: List Meal Options

**Endpoint:** `/api/meal-options`
**Method:** `GET`

Returns a list of meal options for the authenticated user.

## Headers
- `Authorization: Bearer <jwt>`

## Example Request
```
curl http://localhost:8000/api/meal-options \
  -H "Authorization: Bearer <jwt>"
```

## Success Response
- **Code:** 200
- **Content:** Array of meal option objects

## Error Response
- **Code:** 401 Unauthorized
- **Content:** `{ "error": "Unauthorized" }`

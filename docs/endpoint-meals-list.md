# API Endpoint: List Meals

**Endpoint:** `/api/meals`
**Method:** `GET`

Returns a list of meals for the authenticated user.

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

## Error Response
- **Code:** 401 Unauthorized
- **Content:** `{ "error": "Unauthorized" }`

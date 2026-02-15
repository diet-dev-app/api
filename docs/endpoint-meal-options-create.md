# API Endpoint: Create Meal Option

**Endpoint:** `/api/meal-options`
**Method:** `POST`

Creates a new meal option for the authenticated user.

## Headers
- `Authorization: Bearer <jwt>`

## Request Body Parameters
- (parameters TBD)

## Example Request
```
curl -X POST http://localhost:8000/api/meal-options \
  -H "Authorization: Bearer <jwt>" \
  -H "Content-Type: application/json" \
  -d '{
    // parameters TBD
  }'
```

## Success Response
- **Code:** 201
- **Content:** Meal option object

## Error Response
- **Code:** 400 Bad Request
- **Content:** `{ "errors": { ... } }`
- **Code:** 401 Unauthorized
- **Content:** `{ "error": "Unauthorized" }`

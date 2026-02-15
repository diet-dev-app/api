# API Endpoint: Update Meal Option

**Endpoint:** `/api/meal-options/{id}`
**Method:** `PUT`

Updates a meal option for the authenticated user.

## Headers
- `Authorization: Bearer <jwt>`

## Request Body Parameters
- (parameters TBD)

## Example Request
```
curl -X PUT http://localhost:8000/api/meal-options/1 \
  -H "Authorization: Bearer <jwt>" \
  -H "Content-Type: application/json" \
  -d '{
    // parameters TBD
  }'
```

## Success Response
- **Code:** 200
- **Content:** Updated meal option object

## Error Response
- **Code:** 400 Bad Request
- **Content:** `{ "errors": { ... } }`
- **Code:** 401 Unauthorized
- **Content:** `{ "error": "Unauthorized" }`
- **Code:** 404 Not Found
- **Content:** `{ "error": "Meal option not found" }`

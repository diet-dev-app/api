# API Endpoint: Delete Meal Option

**Endpoint:** `/api/meal-options/{id}`
**Method:** `DELETE`

Deletes a meal option for the authenticated user.

## Headers
- `Authorization: Bearer <jwt>`

## Example Request
```
curl -X DELETE http://localhost:8000/api/meal-options/1 \
  -H "Authorization: Bearer <jwt>"
```

## Success Response
- **Code:** 200
- **Content:** `{ "message": "Delete meal option endpoint", "id": 1 }`

## Error Response
- **Code:** 401 Unauthorized
- **Content:** `{ "error": "Unauthorized" }`
- **Code:** 404 Not Found
- **Content:** `{ "error": "Meal option not found" }`

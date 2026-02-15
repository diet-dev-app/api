# API Endpoint: Delete Meal

**Endpoint:** `/api/meals/{id}`
**Method:** `DELETE`

Deletes a meal for the authenticated user.

## Headers
- `Authorization: Bearer <jwt>`

## Example Request
```
curl -X DELETE http://localhost:8000/api/meals/1 \
  -H "Authorization: Bearer <jwt>"
```

## Success Response
- **Code:** 200
- **Content:** `{ "message": "Meal deleted" }`

## Error Response
- **Code:** 401 Unauthorized
- **Content:** `{ "error": "Unauthorized" }`
- **Code:** 404 Not Found
- **Content:** `{ "error": "Meal not found" }`

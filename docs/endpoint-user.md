# API Endpoint: Get User Profile

**Endpoint:** `/api/user`
**Method:** `GET`

Returns the authenticated user's profile information.

## Headers
- `Authorization: Bearer <jwt>`

## Example Request
```
curl http://localhost:8000/api/user \
  -H "Authorization: Bearer <jwt>"
```

## Success Response
- **Code:** 200
- **Content:**
  ```json
  {
    "id": 1,
    "email": "testuser@example.com",
    ...
  }
  ```

## Error Response
- **Code:** 401 Unauthorized
- **Content:** `{ "error": "Unauthorized" }`

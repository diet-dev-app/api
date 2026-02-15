# API Endpoint: Login

**Endpoint:** `/api/login`
**Method:** `POST`

Authenticates a user and returns a JWT token (handled by LexikJWTAuthenticationBundle).

## Request Body Parameters
- `email` (string, required): User's email address
- `password` (string, required): User's password

## Example Request
```
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "testuser@example.com",
    "password": "yourpassword"
  }'
```

## Success Response
- **Code:** 200
- **Content:** `{ "token": "...jwt..." }`

## Error Response
- **Code:** 401 Unauthorized
- **Content:** `{ "message": "Invalid credentials." }`

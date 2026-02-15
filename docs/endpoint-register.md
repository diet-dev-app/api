# User Registration Endpoint

**Endpoint:** `/api/register`

**Method:** `POST`

## Description
Registers a new user in the system.

## Request Body Parameters
- `email` (string, required): User's email address
- `password` (string, required): User's password
- `name` (string, optional): User's name

## Example Request (cURL)
```
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "testuser@example.com",
    "password": "yourpassword",
    "name": "Test User"
  }'
```

## Success Response
- **Code:** 201
- **Content:** `{ "message": "User registered successfully" }`

## Error Responses
- **Code:** 400
- **Content:** `{ "error": "Email and password are required" }`
- **Code:** 400
- **Content:** `{ "errors": { ...validation errors... } }`

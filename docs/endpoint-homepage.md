# API Endpoint: Homepage

**Endpoint:** `/`
**Method:** `GET`

Returns a status message indicating the API is running.

## Example Request
```
curl http://localhost:8000/
```

## Success Response
- **Code:** 200
- **Content:**
  ```json
  {
    "message": "API Diet is running",
    "status": "ok",
    "timestamp": "..."
  }
  ```

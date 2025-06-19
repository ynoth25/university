# Laravel API Documentation

## Overview
This Laravel application has been configured as a RESTful API with proper versioning, authentication, and error handling.

## Base URL
```
http://localhost:8000/api
```

## API Versioning
All API endpoints are versioned under `/v1/` for future compatibility.

## Authentication
The API uses Laravel Sanctum for authentication. Protected routes require a valid Bearer token.

## Response Format
All API responses follow a consistent format:

### Success Response
```json
{
    "success": true,
    "data": {...},
    "message": "Success message"
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error message",
    "data": {...} // Optional validation errors
}
```

## Available Endpoints

### 1. Health Check
**GET** `/api/v1/health`

Check if the API is running.

**Response:**
```json
{
    "status": "success",
    "message": "API is running",
    "timestamp": "2025-06-19T15:25:50.396649Z"
}
```

### 2. User Authentication
**GET** `/api/user`

Get the authenticated user's information.

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": "2025-06-19T15:25:50.396649Z",
    "created_at": "2025-06-19T15:25:50.396649Z",
    "updated_at": "2025-06-19T15:25:50.396649Z"
}
```

## Rate Limiting
API requests are limited to 60 requests per minute per user/IP address.

## Error Codes
- `200` - Success
- `201` - Created
- `204` - No Content (Deleted)
- `400` - Bad Request
- `401` - Unauthorized
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

## Development

### Adding New API Endpoints

1. **Create Controller:**
```bash
php artisan make:controller Api/YourController --api
```

2. **Extend BaseController:**
```php
<?php

namespace App\Http\Controllers\Api;

class YourController extends BaseController
{
    public function index()
    {
        return $this->sendResponse($data, 'Data retrieved successfully');
    }
}
```

3. **Add Routes:**
```php
// In routes/api.php
Route::apiResource('your-resource', YourController::class);
```

### Creating API Resources
```bash
php artisan make:resource YourResource
```

### Testing API Endpoints
```bash
# Test health endpoint
curl -X GET http://localhost:8000/api/v1/health

# Test with authentication
curl -X GET http://localhost:8000/api/user \
  -H "Authorization: Bearer {your-token}" \
  -H "Accept: application/json"
```

## Database Configuration
The API is configured to work with AWS RDS MySQL. Update your `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=your-rds-endpoint.region.rds.amazonaws.com
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## Security Features
- CORS protection
- Rate limiting
- Input validation
- SQL injection protection
- XSS protection
- CSRF protection (for web routes)

## Deployment Considerations
1. Set `APP_ENV=production` in production
2. Set `APP_DEBUG=false` in production
3. Configure proper CORS settings
4. Set up SSL/TLS certificates
5. Configure proper database connections
6. Set up monitoring and logging 
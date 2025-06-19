# Document Request API Documentation

## Overview
This API allows users to submit, manage, and track document requests for educational institutions. The API uses API key authentication for security.

## Base URL
```
http://localhost:8000/api/v1
```

## Authentication
All endpoints require an API key to be included in the request headers:

```
X-API-Key: your-api-key-here
```

or

```
Authorization: Bearer your-api-key-here
```

## API Endpoints

### 1. Create Document Request
**POST** `/document-requests`

Create a new document request.

**Request Body:**
```json
{
    "learning_reference_number": "123456789",
    "name_of_student": "John Doe",
    "last_schoolyear_attended": "2023-2024",
    "gender": "male",
    "grade": "12",
    "section": "A",
    "major": "STEM",
    "adviser": "Mrs. Smith",
    "contact_number": "09123456789",
    "person_requesting": {
        "name": "John Doe",
        "request_for": "SF10",
        "signature": "https://example.com/signature.jpg"
    }
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "request_id": "DOC-2025-KQ2NIRUW",
        "learning_reference_number": "123456789",
        "name_of_student": "John Doe",
        "last_schoolyear_attended": "2023-2024",
        "gender": "male",
        "grade": "12",
        "section": "A",
        "major": "STEM",
        "adviser": "Mrs. Smith",
        "contact_number": "09123456789",
        "person_requesting": {
            "name": "John Doe",
            "request_for": "SF10",
            "signature": "https://example.com/signature.jpg"
        },
        "status": "pending",
        "remarks": null,
        "processed_at": null,
        "created_at": "2025-06-19T16:00:59.000000Z",
        "updated_at": "2025-06-19T16:00:59.000000Z"
    },
    "message": "Document request created successfully"
}
```

### 2. List Document Requests
**GET** `/document-requests`

Retrieve a list of document requests with optional filtering and pagination.

**Query Parameters:**
- `status` - Filter by status (pending, processing, completed, rejected)
- `request_type` - Filter by document type
- `search` - Search by student name, LRN, or request ID
- `per_page` - Number of items per page (default: 15)
- `page` - Page number

**Example:**
```
GET /document-requests?status=pending&search=John&per_page=10
```

### 3. Get Document Request by ID
**GET** `/document-requests/{id}`

Retrieve a specific document request by its database ID.

### 4. Get Document Request by Request ID
**GET** `/document-requests/request/{requestId}`

Retrieve a specific document request by its unique request ID (e.g., DOC-2025-KQ2NIRUW).

### 5. Update Document Request
**PUT** `/document-requests/{id}`

Update an existing document request.

### 6. Update Document Request Status
**PATCH** `/document-requests/{id}/status`

Update the status of a document request.

**Request Body:**
```json
{
    "status": "processing",
    "remarks": "Document is being processed"
}
```

**Available Statuses:**
- `pending` - Request is pending
- `processing` - Request is being processed
- `completed` - Request is completed
- `rejected` - Request is rejected

### 7. Delete Document Request
**DELETE** `/document-requests/{id}`

Delete a document request.

### 8. Get Statistics
**GET** `/document-requests/statistics`

Get statistics about document requests.

**Response:**
```json
{
    "success": true,
    "data": {
        "total": 25,
        "pending": 10,
        "processing": 5,
        "completed": 8,
        "rejected": 2,
        "by_type": {
            "SF10": 8,
            "ENROLLMENT_CERT": 5,
            "DIPLOMA": 3,
            "CAV": 2,
            "ENG. INST.": 4,
            "CERT OF GRAD": 2,
            "OTHERS": 1
        }
    },
    "message": "Statistics retrieved successfully"
}
```

## Document Types
The following document types are supported:
- `SF10` - Form 10 (Permanent Record)
- `ENROLLMENT_CERT` - Certificate of Enrollment
- `DIPLOMA` - Diploma
- `CAV` - Certificate, Authentication and Verification
- `ENG. INST.` - English Institute Certificate
- `CERT OF GRAD` - Certificate of Graduation
- `OTHERS` - Other documents

## Validation Rules

### Required Fields:
- `learning_reference_number` - String, max 255 characters
- `name_of_student` - String, max 255 characters
- `last_schoolyear_attended` - String, max 255 characters
- `gender` - Enum: male, female, other
- `grade` - String, max 50 characters
- `section` - String, max 50 characters
- `adviser` - String, max 255 characters
- `contact_number` - String, max 20 characters
- `person_requesting.name` - String, max 255 characters
- `person_requesting.request_for` - One of the supported document types
- `person_requesting.signature` - Valid URL, max 500 characters

### Optional Fields:
- `major` - String, max 255 characters

## Error Responses

### Validation Error (422)
```json
{
    "success": false,
    "message": "Validation failed",
    "data": {
        "learning_reference_number": ["Learning reference number is required."],
        "gender": ["Gender must be male, female, or other."]
    }
}
```

### Not Found Error (404)
```json
{
    "success": false,
    "message": "Document request not found"
}
```

### Unauthorized Error (401)
```json
{
    "success": false,
    "message": "API key is required"
}
```

## API Key Management

### Create API Key
```bash
php artisan api:create-key "Key Name" --expires="2025-12-31"
```

### List API Keys
```bash
php artisan tinker
>>> App\Models\ApiKey::all(['name', 'is_active', 'last_used_at', 'expires_at']);
```

## Testing Examples

### Using cURL

1. **Create a document request:**
```bash
curl -X POST http://localhost:8000/api/v1/document-requests \
  -H "X-API-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "learning_reference_number": "123456789",
    "name_of_student": "Jane Smith",
    "last_schoolyear_attended": "2023-2024",
    "gender": "female",
    "grade": "11",
    "section": "B",
    "major": "HUMSS",
    "adviser": "Mr. Johnson",
    "contact_number": "09187654321",
    "person_requesting": {
        "name": "Jane Smith",
        "request_for": "ENROLLMENT_CERT",
        "signature": "https://example.com/jane-signature.jpg"
    }
  }'
```

2. **Get all document requests:**
```bash
curl -X GET http://localhost:8000/api/v1/document-requests \
  -H "X-API-Key: your-api-key"
```

3. **Get statistics:**
```bash
curl -X GET http://localhost:8000/api/v1/document-requests/statistics \
  -H "X-API-Key: your-api-key"
```

### Using JavaScript/Fetch

```javascript
const API_KEY = 'your-api-key';
const BASE_URL = 'http://localhost:8000/api/v1';

// Create document request
const createRequest = async (data) => {
    const response = await fetch(`${BASE_URL}/document-requests`, {
        method: 'POST',
        headers: {
            'X-API-Key': API_KEY,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    });
    return response.json();
};

// Get all requests
const getRequests = async () => {
    const response = await fetch(`${BASE_URL}/document-requests`, {
        headers: {
            'X-API-Key': API_KEY,
        }
    });
    return response.json();
};
```

## Best Practices

1. **API Key Security:**
   - Keep API keys secure and don't expose them in client-side code
   - Rotate API keys regularly
   - Use different keys for different environments

2. **Error Handling:**
   - Always check the `success` field in responses
   - Handle validation errors appropriately
   - Implement proper error logging

3. **Rate Limiting:**
   - The API has rate limiting (60 requests per minute)
   - Implement exponential backoff for retries

4. **Data Validation:**
   - Validate all input data on the client side
   - Handle server-side validation errors gracefully

## Database Schema

### document_requests table
- `id` - Primary key
- `request_id` - Unique request identifier (auto-generated)
- `learning_reference_number` - Student's LRN
- `name_of_student` - Student's full name
- `last_schoolyear_attended` - Last school year
- `gender` - Student's gender
- `grade` - Student's grade level
- `section` - Student's section
- `major` - Student's major (optional)
- `adviser` - Student's adviser
- `contact_number` - Contact number
- `person_requesting` - JSON object with requester details
- `status` - Request status
- `remarks` - Additional remarks
- `processed_at` - When the request was processed
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

### api_keys table
- `id` - Primary key
- `name` - Human-readable name
- `key` - The actual API key
- `is_active` - Whether the key is active
- `last_used_at` - Last usage timestamp
- `expires_at` - Expiration date (optional)
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp 
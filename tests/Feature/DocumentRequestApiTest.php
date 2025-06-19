<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\DocumentRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentRequestApiTest extends TestCase
{
    use RefreshDatabase;

    private array $validDocumentRequestData;

    protected function setUp(): void
    {
        parent::setUp();

        // Valid document request data
        $this->validDocumentRequestData = [
            'learning_reference_number' => '123456789',
            'name_of_student' => 'John Doe',
            'last_schoolyear_attended' => '2023-2024',
            'gender' => 'male',
            'grade' => '12',
            'section' => 'A',
            'major' => 'STEM',
            'adviser' => 'Mrs. Smith',
            'contact_number' => '09123456789',
            'person_requesting' => [
                'name' => 'John Doe',
                'request_for' => 'SF10',
                'signature' => 'https://example.com/signature.jpg'
            ]
        ];
    }

    /**
     * Test API key authentication
     */
    public function test_api_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/document-requests');

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'API key is required'
                ]);
    }

    /**
     * Test invalid API key
     */
    public function test_invalid_api_key_returns_401(): void
    {
        $response = $this->withHeaders([
            'X-API-Key' => 'invalid-key'
        ])->getJson('/api/v1/document-requests');

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid or expired API key'
                ]);
    }

    /**
     * Test creating a document request
     */
    public function test_can_create_document_request(): void
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->getTestApiKey()->key
        ])->postJson('/api/v1/document-requests', $this->validDocumentRequestData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Document request created successfully'
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'request_id',
                        'learning_reference_number',
                        'name_of_student',
                        'last_schoolyear_attended',
                        'gender',
                        'grade',
                        'section',
                        'major',
                        'adviser',
                        'contact_number',
                        'person_requesting',
                        'status',
                        'created_at',
                        'updated_at'
                    ],
                    'message'
                ]);

        // Verify the request ID format
        $this->assertMatchesRegularExpression('/^DOC-\d{4}-[A-Z0-9]{8}$/', $response['data']['request_id']);

        // Verify data was saved correctly
        $this->assertDatabaseHas('document_requests', [
            'learning_reference_number' => '123456789',
            'name_of_student' => 'John Doe',
            'status' => 'pending'
        ]);
    }

    /**
     * Test validation errors for required fields
     */
    public function test_validation_errors_for_required_fields(): void
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->getTestApiKey()->key
        ])->postJson('/api/v1/document-requests', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'learning_reference_number',
                    'name_of_student',
                    'last_schoolyear_attended',
                    'gender',
                    'grade',
                    'section',
                    'adviser',
                    'contact_number',
                    'person_requesting'
                ]);
    }

    /**
     * Test validation for invalid gender
     */
    public function test_validation_for_invalid_gender(): void
    {
        $data = $this->validDocumentRequestData;
        $data['gender'] = 'invalid';

        $response = $this->withHeaders([
            'X-API-Key' => $this->getTestApiKey()->key
        ])->postJson('/api/v1/document-requests', $data);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['gender']);
    }

    /**
     * Test validation for invalid document type
     */
    public function test_validation_for_invalid_document_type(): void
    {
        $data = $this->validDocumentRequestData;
        $data['person_requesting']['request_for'] = 'INVALID_TYPE';

        $response = $this->withHeaders([
            'X-API-Key' => $this->getTestApiKey()->key
        ])->postJson('/api/v1/document-requests', $data);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['person_requesting.request_for']);
    }

    /**
     * Test getting all document requests
     */
    public function test_can_get_all_document_requests(): void
    {
        // Create multiple document requests
        DocumentRequest::factory()->count(3)->create();

        $response = $this->withHeaders([
            'X-API-Key' => $this->getTestApiKey()->key
        ])->getJson('/api/v1/document-requests');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Document requests retrieved successfully'
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'request_id',
                            'name_of_student',
                            'status',
                            'created_at'
                        ]
                    ],
                    'message'
                ]);
    }

    /**
     * Test filtering by status
     */
    public function test_can_filter_by_status(): void
    {
        // Create requests with different statuses
        DocumentRequest::factory()->create(['status' => 'pending']);
        DocumentRequest::factory()->create(['status' => 'processing']);
        DocumentRequest::factory()->create(['status' => 'completed']);

        $response = $this->withHeaders([
            'X-API-Key' => $this->getTestApiKey()->key
        ])->getJson('/api/v1/document-requests?status=pending');

        $response->assertStatus(200);
        $this->assertCount(1, $response['data']);
        $this->assertEquals('pending', $response['data'][0]['status']);
    }

    /**
     * Test searching document requests
     */
    public function test_can_search_document_requests(): void
    {
        DocumentRequest::factory()->create([
            'name_of_student' => 'John Doe',
            'learning_reference_number' => '123456789'
        ]);
        DocumentRequest::factory()->create([
            'name_of_student' => 'Jane Smith',
            'learning_reference_number' => '987654321'
        ]);

        $response = $this->withHeaders([
            'X-API-Key' => $this->getTestApiKey()->key
        ])->getJson('/api/v1/document-requests?search=John');

        $response->assertStatus(200);
        $this->assertCount(1, $response['data']);
        $this->assertEquals('John Doe', $response['data'][0]['name_of_student']);
    }

    /**
     * Test getting a specific document request by ID
     */
    public function test_can_get_document_request_by_id(): void
    {
        $documentRequest = DocumentRequest::factory()->create();

        $response = $this->withHeaders([
            'X-API-Key' => $this->getTestApiKey()->key
        ])->getJson("/api/v1/document-requests/{$documentRequest->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $documentRequest->id,
                        'request_id' => $documentRequest->request_id
                    ]
                ]);
    }

    /**
     * Test getting a document request by request ID
     */
    public function test_can_get_document_request_by_request_id(): void
    {
        $documentRequest = DocumentRequest::factory()->create();

        $response = $this->withHeaders([
            'X-API-Key' => $this->getTestApiKey()->key
        ])->getJson("/api/v1/document-requests/request/{$documentRequest->request_id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $documentRequest->id,
                        'request_id' => $documentRequest->request_id
                    ]
                ]);
    }

    /**
     * Test getting non-existent document request
     */
    public function test_returns_404_for_non_existent_document_request(): void
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->getTestApiKey()->key
        ])->getJson('/api/v1/document-requests/999');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Document request not found'
                ]);
    }

    /**
     * Test updating a document request
     */
    public function test_can_update_document_request(): void
    {
        $documentRequest = DocumentRequest::factory()->create();
        $updateData = $this->validDocumentRequestData;
        $updateData['name_of_student'] = 'Jane Smith Updated';

        $response = $this->withHeaders([
            'X-API-Key' => $this->getTestApiKey()->key
        ])->putJson("/api/v1/document-requests/{$documentRequest->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Document request updated successfully'
                ]);

        $this->assertDatabaseHas('document_requests', [
            'id' => $documentRequest->id,
            'name_of_student' => 'Jane Smith Updated'
        ]);
    }

    /**
     * Test updating document request status
     */
    public function test_can_update_document_request_status(): void
    {
        $documentRequest = DocumentRequest::factory()->create(['status' => 'pending']);

        $response = $this->withHeaders([
            'X-API-Key' => $this->getTestApiKey()->key
        ])->patchJson("/api/v1/document-requests/{$documentRequest->id}/status", [
            'status' => 'processing',
            'remarks' => 'Document is being processed'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Document request status updated successfully'
                ]);

        $this->assertDatabaseHas('document_requests', [
            'id' => $documentRequest->id,
            'status' => 'processing'
        ]);
    }

    /**
     * Test updating status to completed sets processed_at
     */
    public function test_completed_status_sets_processed_at(): void
    {
        $documentRequest = DocumentRequest::factory()->create(['status' => 'pending']);

        $response = $this->withHeaders([
            'X-API-Key' => $this->getTestApiKey()->key
        ])->patchJson("/api/v1/document-requests/{$documentRequest->id}/status", [
            'status' => 'completed'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('document_requests', [
            'id' => $documentRequest->id,
            'status' => 'completed'
        ]);

        $this->assertNotNull($documentRequest->fresh()->processed_at);
    }

    /**
     * Test deleting a document request
     */
    public function test_can_delete_document_request(): void
    {
        $documentRequest = DocumentRequest::factory()->create();

        $response = $this->withHeaders([
            'X-API-Key' => $this->getTestApiKey()->key
        ])->deleteJson("/api/v1/document-requests/{$documentRequest->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('document_requests', [
            'id' => $documentRequest->id
        ]);
    }

    /**
     * Test getting statistics
     */
    public function test_can_get_statistics(): void
    {
        // Create requests with different statuses and types
        DocumentRequest::factory()->create(['status' => 'pending']);
        DocumentRequest::factory()->create(['status' => 'processing']);
        DocumentRequest::factory()->create(['status' => 'completed']);
        DocumentRequest::factory()->create([
            'status' => 'pending',
            'person_requesting' => ['request_for' => 'SF10']
        ]);

        $response = $this->withHeaders([
            'X-API-Key' => $this->getTestApiKey()->key
        ])->getJson('/api/v1/document-requests/statistics');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Statistics retrieved successfully'
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'total',
                        'pending',
                        'processing',
                        'completed',
                        'rejected',
                        'by_type'
                    ],
                    'message'
                ]);

        $this->assertEquals(4, $response['data']['total']);
        $this->assertEquals(2, $response['data']['pending']);
        $this->assertEquals(1, $response['data']['processing']);
        $this->assertEquals(1, $response['data']['completed']);
    }

    /**
     * Test pagination
     */
    public function test_pagination_works_correctly(): void
    {
        DocumentRequest::factory()->count(25)->create();

        $response = $this->withHeaders([
            'X-API-Key' => $this->getTestApiKey()->key
        ])->getJson('/api/v1/document-requests?per_page=10&page=1');

        $response->assertStatus(200);
        $this->assertCount(10, $response['data']);
    }

    /**
     * Test API key usage tracking
     */
    public function test_api_key_usage_is_tracked(): void
    {
        $apiKey = $this->getTestApiKey();
        $this->assertNull($apiKey->last_used_at);

        $this->withHeaders([
            'X-API-Key' => $apiKey->key
        ])->getJson('/api/v1/document-requests');

        $apiKey->refresh();
        $this->assertNotNull($apiKey->last_used_at);
    }

    /**
     * Test that file uploads use the correct naming convention with requestor name
     */
    public function test_file_upload_uses_requestor_name_in_filename()
    {
        // Create a document request first
        $documentRequest = DocumentRequest::factory()->create([
            'person_requesting' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '1234567890'
            ]
        ]);

        $apiKey = ApiKey::factory()->create(['is_active' => true]);

        // Mock the S3 upload
        Storage::fake('s3');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey->key,
            'Accept' => 'application/json',
        ])->postJson("/api/v1/document-requests/{$documentRequest->id}/files/upload", [
            'file_type' => 'signature',
            'file' => UploadedFile::fake()->image('signature.png', 100, 100)
        ]);

        $response->assertStatus(201);

        // Check that the filename contains the requestor name
        $uploadedFile = $response->json('data');
        $this->assertStringContainsString('John_Doe', $uploadedFile['file_name']);
        $this->assertStringContainsString($documentRequest->request_id, $uploadedFile['file_name']);
        $this->assertStringContainsString('signature', $uploadedFile['file_name']);
    }
}

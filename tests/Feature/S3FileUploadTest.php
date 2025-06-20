<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\DocumentFile;
use App\Models\DocumentRequest;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class S3FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected ApiKey $apiKey;

    protected User $user;

    protected DocumentRequest $documentRequest;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create API key (without user_id since it's not in the schema)
        $this->apiKey = ApiKey::factory()->create([
            'key' => 'test-api-key-12345',
            'is_active' => true,
        ]);

        // Create document request using only fields that exist in the migration
        $this->documentRequest = DocumentRequest::factory()->create([
            'learning_reference_number' => '1234567890',
            'name_of_student' => 'John Doe',
            'last_schoolyear_attended' => '2024-2025',
            'gender' => 'male',
            'grade' => '12',
            'section' => 'A',
            'major' => 'STEM',
            'adviser' => 'Dr. Smith',
            'contact_number' => '09123456789',
            'person_requesting_name' => 'Burnice Reilly',
            'request_for' => 'ENROLLMENT_CERT',
            'signature_url' => 'https://via.placeholder.com/640x480.png/00ffcc?text=signature+dolorem',
            'status' => 'pending',
        ]);

        // Configure test storage
        Storage::fake('s3');
    }

    public function test_upload_single_file_success()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
            'Content-Type' => 'multipart/form-data',
        ])->post("/api/v1/document-requests/{$this->documentRequest->id}/files/upload", [
            'file' => $file,
            'file_type' => 'transcript_of_records',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'file_type',
                    'original_name',
                    'file_name',
                    'file_path',
                    'mime_type',
                    'file_size',
                    'formatted_size',
                    'uploaded_at',
                ],
            ]);

        $this->assertDatabaseHas('document_files', [
            'document_request_id' => $this->documentRequest->id,
            'file_type' => 'transcript_of_records',
            'original_name' => 'document.pdf',
        ]);
    }

    public function test_upload_multiple_files_success()
    {
        $files = [
            UploadedFile::fake()->create('document1.pdf', 1024, 'application/pdf'),
            UploadedFile::fake()->create('document2.pdf', 1024, 'application/pdf'),
        ];

        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
            'Content-Type' => 'multipart/form-data',
        ])->post("/api/v1/document-requests/{$this->documentRequest->id}/files/upload-multiple", [
            'files' => $files,
            'file_type' => 'transcript_of_records',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uploaded_files',
                    'total_uploaded',
                    'total_files',
                ],
            ]);

        $this->assertDatabaseCount('document_files', 2);
    }

    public function test_get_files_for_document_request()
    {
        // Create some test files
        DocumentFile::factory()->count(3)->create([
            'document_request_id' => $this->documentRequest->id,
            'file_type' => 'transcript_of_records',
        ]);

        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
        ])->get("/api/v1/document-requests/{$this->documentRequest->id}/files");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'files',
                    'total_files',
                    'file_types',
                ],
            ]);

        $this->assertEquals(3, $response->json('data.total_files'));
    }

    public function test_get_files_by_type()
    {
        // Create files of different types
        DocumentFile::factory()->count(2)->create([
            'document_request_id' => $this->documentRequest->id,
            'file_type' => 'transcript_of_records',
        ]);
        DocumentFile::factory()->count(1)->create([
            'document_request_id' => $this->documentRequest->id,
            'file_type' => 'signature',
        ]);

        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
        ])->get("/api/v1/document-requests/{$this->documentRequest->id}/files/type/transcript_of_records");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'files',
                    'total_files',
                    'file_type',
                ],
            ]);

        $this->assertEquals(2, $response->json('data.total_files'));
        $this->assertEquals('transcript_of_records', $response->json('data.file_type'));
    }

    public function test_delete_file_success()
    {
        $file = DocumentFile::factory()->create([
            'document_request_id' => $this->documentRequest->id,
            'file_type' => 'transcript_of_records',
        ]);

        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
        ])->delete("/api/v1/document-requests/{$this->documentRequest->id}/files/{$file->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('document_files', ['id' => $file->id]);
    }

    public function test_update_file_metadata()
    {
        $file = DocumentFile::factory()->create([
            'document_request_id' => $this->documentRequest->id,
            'file_type' => 'transcript_of_records',
        ]);

        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
        ])->patch("/api/v1/document-requests/{$this->documentRequest->id}/files/{$file->id}/metadata", [
            'metadata' => [
                'description' => 'Updated description',
                'tags' => ['important', 'urgent'],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'file_type',
                    'original_name',
                    'metadata',
                ],
            ]);

        $this->assertDatabaseHas('document_files', [
            'id' => $file->id,
            'metadata->description' => 'Updated description',
        ]);
    }

    public function test_get_file_info()
    {
        $file = DocumentFile::factory()->create([
            'document_request_id' => $this->documentRequest->id,
            'file_type' => 'transcript_of_records',
        ]);

        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
        ])->get("/api/v1/document-requests/{$this->documentRequest->id}/files/{$file->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'file_type',
                    'original_name',
                    'file_name',
                    'file_path',
                    'mime_type',
                    'file_size',
                    'formatted_size',
                    'uploaded_at',
                    'metadata',
                ],
            ]);
    }

    public function test_get_allowed_file_types()
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
        ])->get('/api/v1/file-types');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'allowed_types',
                    'max_file_size',
                    'allowed_extensions',
                ],
            ]);
    }

    public function test_upload_file_invalid_type()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
            'Content-Type' => 'multipart/form-data',
        ])->post("/api/v1/document-requests/{$this->documentRequest->id}/files/upload", [
            'file' => $file,
            'file_type' => 'invalid_type',
        ]);

        $response->assertStatus(422);
    }

    public function test_upload_file_missing_file()
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
            'Content-Type' => 'multipart/form-data',
        ])->post("/api/v1/document-requests/{$this->documentRequest->id}/files/upload", [
            'file_type' => 'transcript_of_records',
        ]);

        $response->assertStatus(422);
    }

    public function test_upload_file_document_request_not_found()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
            'Content-Type' => 'multipart/form-data',
        ])->post('/api/v1/document-requests/99999/files/upload', [
            'file' => $file,
            'file_type' => 'transcript_of_records',
        ]);

        $response->assertStatus(404);
    }

    public function test_delete_file_not_found()
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
        ])->delete("/api/v1/document-requests/{$this->documentRequest->id}/files/99999");

        $response->assertStatus(404);
    }

    public function test_get_files_document_request_not_found()
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
        ])->get('/api/v1/document-requests/99999/files');

        $response->assertStatus(404);
    }

    public function test_upload_file_without_api_key()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = $this->post("/api/v1/document-requests/{$this->documentRequest->id}/files/upload", [
            'file' => $file,
            'file_type' => 'transcript_of_records',
        ]);

        $response->assertStatus(401);
    }

    public function test_upload_file_invalid_api_key()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = $this->withHeaders([
            'X-API-Key' => 'invalid-key',
            'Content-Type' => 'multipart/form-data',
        ])->post("/api/v1/document-requests/{$this->documentRequest->id}/files/upload", [
            'file' => $file,
            'file_type' => 'transcript_of_records',
        ]);

        $response->assertStatus(401);
    }

    public function test_upload_file_inactive_api_key()
    {
        $inactiveKey = ApiKey::factory()->create([
            'is_active' => false,
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = $this->withHeaders([
            'X-API-Key' => $inactiveKey->key,
            'Content-Type' => 'multipart/form-data',
        ])->post("/api/v1/document-requests/{$this->documentRequest->id}/files/upload", [
            'file' => $file,
            'file_type' => 'transcript_of_records',
        ]);

        $response->assertStatus(401);
    }

    public function test_upload_multiple_files_partial_failure()
    {
        // Create one valid file and one invalid file (too large)
        $validFile = UploadedFile::fake()->create('valid.pdf', 1024, 'application/pdf');
        $invalidFile = UploadedFile::fake()->create('invalid.pdf', 50 * 1024 * 1024, 'application/pdf'); // 50MB

        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
            'Content-Type' => 'multipart/form-data',
        ])->post("/api/v1/document-requests/{$this->documentRequest->id}/files/upload-multiple", [
            'files' => [$validFile, $invalidFile],
            'file_type' => 'transcript_of_records',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uploaded_files',
                    'total_uploaded',
                    'total_files',
                    'errors',
                ],
            ]);

        $this->assertEquals(1, $response->json('data.total_uploaded'));
        $this->assertEquals(2, $response->json('data.total_files'));
        $this->assertNotEmpty($response->json('data.errors'));
    }

    public function test_file_upload_service_integration()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $fileUploadService = app(FileUploadService::class);

        // Test file validation
        $validationErrors = $fileUploadService->validateFile($file, 'transcript_of_records');
        $this->assertEmpty($validationErrors);

        // Test file upload
        $documentFile = $fileUploadService->uploadFile($file, $this->documentRequest, 'transcript_of_records');

        $this->assertInstanceOf(DocumentFile::class, $documentFile);
        $this->assertEquals('transcript_of_records', $documentFile->file_type);
        $this->assertEquals('document.pdf', $documentFile->original_name);
    }

    public function test_file_upload_service_validation_errors()
    {
        $file = UploadedFile::fake()->create('document.txt', 1024, 'text/plain');

        $fileUploadService = app(FileUploadService::class);

        // Test file validation with invalid file type
        $validationErrors = $fileUploadService->validateFile($file, 'transcript_of_records');
        $this->assertNotEmpty($validationErrors);
    }
}

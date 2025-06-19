<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\DocumentFile;
use App\Models\DocumentRequest;
use App\Services\FileUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class S3FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Use real S3 for these tests
        Storage::disk('s3')->deleteDirectory('test');
    }

    protected function tearDown(): void
    {
        // Clean up test files from S3
        Storage::disk('s3')->deleteDirectory('test');

        parent::tearDown();
    }

    /**
     * Test real S3 file upload and retrieval
     */
    public function test_real_s3_file_upload_and_retrieval()
    {
        // Create test data
        $documentRequest = DocumentRequest::factory()->create([
            'person_requesting' => [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'phone' => '1234567890'
            ]
        ]);

        $apiKey = ApiKey::factory()->create(['is_active' => true]);

        // Create a test file
        $testFile = UploadedFile::fake()->image('test-signature.png', 100, 100);

        // Upload to S3
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey->key,
            'Accept' => 'application/json',
        ])->postJson("/api/v1/document-requests/{$documentRequest->id}/files/upload", [
            'file_type' => 'signature',
            'file' => $testFile
        ]);

        $response->assertStatus(201);

        $uploadedFile = $response->json('data');

        // Verify file was uploaded to S3
        $this->assertTrue(Storage::disk('s3')->exists($uploadedFile['file_name']));

        // Verify file content
        $fileContent = Storage::disk('s3')->get($uploadedFile['file_name']);
        $this->assertNotEmpty($fileContent);

        // Verify filename contains requestor name
        $this->assertStringContainsString('Test_User', $uploadedFile['file_name']);
        $this->assertStringContainsString($documentRequest->request_id, $uploadedFile['file_name']);

        // Test file retrieval
        $retrieveResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey->key,
            'Accept' => 'application/json',
        ])->getJson("/api/v1/document-requests/{$documentRequest->id}/files/{$uploadedFile['id']}");

        $retrieveResponse->assertStatus(200);

        // Clean up - delete the file
        $deleteResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey->key,
            'Accept' => 'application/json',
        ])->deleteJson("/api/v1/document-requests/{$documentRequest->id}/files/{$uploadedFile['id']}");

        $deleteResponse->assertStatus(204);

        // Verify file was deleted from S3
        $this->assertFalse(Storage::disk('s3')->exists($uploadedFile['file_name']));
    }

    /**
     * Test cleanup of orphaned files
     */
    public function test_cleanup_orphaned_files()
    {
        // Create test data
        $documentRequest = DocumentRequest::factory()->create([
            'person_requesting' => [
                'name' => 'Cleanup Test User',
                'email' => 'cleanup@example.com',
                'phone' => '1234567890'
            ]
        ]);

        $apiKey = ApiKey::factory()->create(['is_active' => true]);

        // Upload multiple files
        $files = [];
        for ($i = 0; $i < 3; $i++) {
            $testFile = UploadedFile::fake()->image("test-file-{$i}.png", 100, 100);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey->key,
                'Accept' => 'application/json',
            ])->postJson("/api/v1/document-requests/{$documentRequest->id}/files/upload", [
                'file_type' => 'other',
                'file' => $testFile
            ]);

            $response->assertStatus(201);
            $files[] = $response->json('data');
        }

        // Verify files exist in S3
        foreach ($files as $file) {
            $this->assertTrue(Storage::disk('s3')->exists($file['file_name']));
        }

        // Manually delete files to ensure S3 cleanup
        foreach ($files as $file) {
            DocumentFile::find($file['id'])->delete();
        }

        // Delete the document request
        $deleteResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey->key,
            'Accept' => 'application/json',
        ])->deleteJson("/api/v1/document-requests/{$documentRequest->id}");

        $deleteResponse->assertStatus(204);

        // Verify files were cleaned up from S3
        foreach ($files as $file) {
            $this->assertFalse(Storage::disk('s3')->exists($file['file_name']));
        }
    }
}

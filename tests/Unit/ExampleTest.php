<?php

namespace Tests\Unit;

use App\Models\ApiKey;
use App\Models\DocumentFile;
use App\Models\DocumentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_model_relationships()
    {
        $user = User::factory()->create();
        $documentRequest = DocumentRequest::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(DocumentRequest::class, $user->documentRequests->first());
    }

    public function test_api_key_model_relationships()
    {
        $apiKey = ApiKey::factory()->create();

        $this->assertNotNull($apiKey->id);
        $this->assertNotEmpty($apiKey->key);
    }

    public function test_document_request_model_relationships()
    {
        $user = User::factory()->create();
        $documentRequest = DocumentRequest::factory()->create(['user_id' => $user->id]);
        $documentFile = DocumentFile::factory()->create(['document_request_id' => $documentRequest->id]);

        $this->assertInstanceOf(User::class, $documentRequest->user);
        $this->assertInstanceOf(DocumentFile::class, $documentRequest->files->first());
    }

    public function test_document_file_model_relationships()
    {
        $documentRequest = DocumentRequest::factory()->create();
        $documentFile = DocumentFile::factory()->create(['document_request_id' => $documentRequest->id]);

        $this->assertInstanceOf(DocumentRequest::class, $documentFile->documentRequest);
    }

    public function test_document_request_status_scopes()
    {
        DocumentRequest::factory()->create(['status' => 'pending']);
        DocumentRequest::factory()->create(['status' => 'approved']);
        DocumentRequest::factory()->create(['status' => 'rejected']);

        $this->assertEquals(1, DocumentRequest::pending()->count());
        $this->assertEquals(1, DocumentRequest::approved()->count());
        $this->assertEquals(1, DocumentRequest::rejected()->count());
    }

    public function test_document_request_document_type_scopes()
    {
        DocumentRequest::factory()->create(['document_type' => 'transcript']);
        DocumentRequest::factory()->create(['document_type' => 'diploma']);
        DocumentRequest::factory()->create(['document_type' => 'certificate']);

        $this->assertEquals(1, DocumentRequest::byDocumentType('transcript')->count());
        $this->assertEquals(1, DocumentRequest::byDocumentType('diploma')->count());
        $this->assertEquals(1, DocumentRequest::byDocumentType('certificate')->count());
    }

    public function test_document_request_search_scope()
    {
        DocumentRequest::factory()->create(['requestor_name' => 'John Doe']);
        DocumentRequest::factory()->create(['requestor_name' => 'Jane Smith']);
        DocumentRequest::factory()->create(['requestor_name' => 'Bob Johnson']);

        $this->assertEquals(1, DocumentRequest::search('John')->count());
        $this->assertEquals(2, DocumentRequest::search('o')->count()); // John and Bob
    }

    public function test_api_key_is_active_scope()
    {
        ApiKey::factory()->create(['is_active' => true]);
        ApiKey::factory()->create(['is_active' => false]);

        $this->assertEquals(1, ApiKey::active()->count());
    }

    public function test_document_file_file_type_scopes()
    {
        DocumentFile::factory()->create(['file_type' => 'document']);
        DocumentFile::factory()->create(['file_type' => 'signature']);
        DocumentFile::factory()->create(['file_type' => 'other']);

        $this->assertEquals(1, DocumentFile::byFileType('document')->count());
        $this->assertEquals(1, DocumentFile::byFileType('signature')->count());
    }

    public function test_document_file_formatted_size_attribute()
    {
        $file = DocumentFile::factory()->create(['file_size' => 1024]);
        $this->assertEquals('1 KB', $file->formatted_size);

        $file = DocumentFile::factory()->create(['file_size' => 1024 * 1024]);
        $this->assertEquals('1 MB', $file->formatted_size);
    }

    public function test_document_request_request_id_generation()
    {
        $documentRequest = DocumentRequest::factory()->create();
        
        $this->assertNotEmpty($documentRequest->request_id);
        $this->assertStringStartsWith('REQ-', $documentRequest->request_id);
    }

    public function test_api_key_key_generation()
    {
        $apiKey = ApiKey::factory()->create();
        
        $this->assertNotEmpty($apiKey->key);
        $this->assertEquals(64, strlen($apiKey->key));
    }

    public function test_document_file_filename_generation()
    {
        $documentRequest = DocumentRequest::factory()->create([
            'requestor_name' => 'John Doe'
        ]);
        
        $file = DocumentFile::factory()->create([
            'document_request_id' => $documentRequest->id,
            'original_name' => 'test.pdf',
            'file_type' => 'document'
        ]);
        
        $this->assertStringContainsString('John_Doe', $file->file_name);
        $this->assertStringContainsString($documentRequest->request_id, $file->file_name);
    }

    public function test_document_request_metadata_casting()
    {
        $metadata = ['priority' => 'high', 'notes' => 'Urgent request'];
        $documentRequest = DocumentRequest::factory()->create(['metadata' => $metadata]);
        
        $this->assertEquals($metadata, $documentRequest->metadata);
        $this->assertIsArray($documentRequest->metadata);
    }

    public function test_document_file_metadata_casting()
    {
        $metadata = ['description' => 'Test file', 'tags' => ['important']];
        $file = DocumentFile::factory()->create(['metadata' => $metadata]);
        
        $this->assertEquals($metadata, $file->metadata);
        $this->assertIsArray($file->metadata);
    }

    public function test_user_factory()
    {
        $user = User::factory()->create();
        
        $this->assertNotEmpty($user->name);
        $this->assertNotEmpty($user->email);
        $this->assertStringContainsString('@', $user->email);
    }

    public function test_api_key_factory()
    {
        $apiKey = ApiKey::factory()->create();
        
        $this->assertNotEmpty($apiKey->key);
        $this->assertIsBool($apiKey->is_active);
        $this->assertNotNull($apiKey->user_id);
    }

    public function test_document_request_factory()
    {
        $documentRequest = DocumentRequest::factory()->create();
        
        $this->assertNotEmpty($documentRequest->requestor_name);
        $this->assertNotEmpty($documentRequest->requestor_email);
        $this->assertNotEmpty($documentRequest->document_type);
        $this->assertNotEmpty($documentRequest->purpose);
        $this->assertNotEmpty($documentRequest->status);
        $this->assertNotNull($documentRequest->user_id);
    }

    public function test_document_file_factory()
    {
        $file = DocumentFile::factory()->create();
        
        $this->assertNotEmpty($file->original_name);
        $this->assertNotEmpty($file->file_name);
        $this->assertNotEmpty($file->file_path);
        $this->assertNotEmpty($file->mime_type);
        $this->assertGreaterThan(0, $file->file_size);
        $this->assertNotEmpty($file->file_type);
        $this->assertNotNull($file->document_request_id);
    }
}

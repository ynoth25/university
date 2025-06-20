<?php

namespace Tests\Unit;

use App\Models\ApiKey;
use App\Models\DocumentFile;
use App\Models\DocumentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_model_basic_attributes()
    {
        $user = User::factory()->create();
        
        $this->assertNotEmpty($user->name);
        $this->assertNotEmpty($user->email);
        $this->assertStringContainsString('@', $user->email);
    }

    public function test_api_key_model_basic_attributes()
    {
        $apiKey = ApiKey::factory()->create();

        $this->assertNotNull($apiKey->id);
        $this->assertNotEmpty($apiKey->key);
        $this->assertIsBool($apiKey->is_active);
    }

    public function test_document_request_model_basic_attributes()
    {
        $documentRequest = DocumentRequest::factory()->create([
            'learning_reference_number' => 'LRN001',
            'name_of_student' => 'Test Student',
        ]);

        $this->assertNotEmpty($documentRequest->request_id);
        $this->assertStringStartsWith('DOC-', $documentRequest->request_id);
        $this->assertEquals('LRN001', $documentRequest->learning_reference_number);
        $this->assertEquals('Test Student', $documentRequest->name_of_student);
    }

    public function test_document_file_model_basic_attributes()
    {
        $documentRequest = DocumentRequest::factory()->create();
        $documentFile = DocumentFile::factory()->create(['document_request_id' => $documentRequest->id]);

        $this->assertInstanceOf(DocumentRequest::class, $documentFile->documentRequest);
        $this->assertEquals($documentRequest->id, $documentFile->document_request_id);
    }

    public function test_document_request_status_scopes()
    {
        DocumentRequest::factory()->create([
            'learning_reference_number' => 'LRN001',
            'name_of_student' => 'Student 1',
            'status' => 'pending',
        ]);
        DocumentRequest::factory()->create([
            'learning_reference_number' => 'LRN002',
            'name_of_student' => 'Student 2',
            'status' => 'completed',
        ]);
        DocumentRequest::factory()->create([
            'learning_reference_number' => 'LRN003',
            'name_of_student' => 'Student 3',
            'status' => 'rejected',
        ]);

        $this->assertEquals(1, DocumentRequest::pending()->count());
        $this->assertEquals(1, DocumentRequest::completed()->count());
        $this->assertEquals(1, DocumentRequest::rejected()->count());
    }

    public function test_document_request_relationships()
    {
        $documentRequest = DocumentRequest::factory()->create();
        $documentFile = DocumentFile::factory()->create(['document_request_id' => $documentRequest->id]);

        $this->assertInstanceOf(DocumentFile::class, $documentRequest->files->first());
        $this->assertEquals($documentRequest->id, $documentFile->documentRequest->id);
    }

    public function test_document_request_person_requesting_casting()
    {
        $personRequesting = [
            'name' => 'John Doe',
            'request_for' => 'TRANSCRIPT',
            'signature' => 'https://example.com/signature.jpg'
        ];
        
        $documentRequest = DocumentRequest::factory()->create([
            'person_requesting' => $personRequesting
        ]);
        
        $this->assertEquals($personRequesting, $documentRequest->person_requesting);
        $this->assertIsArray($documentRequest->person_requesting);
        $this->assertEquals('John Doe', $documentRequest->person_requesting_name);
        $this->assertEquals('TRANSCRIPT', $documentRequest->request_type);
    }

    public function test_document_request_signature_url()
    {
        $documentRequest = DocumentRequest::factory()->create();
        
        // No signature file exists
        $this->assertEquals('', $documentRequest->signature_url);
        
        // Create a signature file
        $signatureFile = DocumentFile::factory()->create([
            'document_request_id' => $documentRequest->id,
            'file_type' => 'signature',
            'file_path' => 'signatures/test.jpg'
        ]);
        
        $this->assertEquals($signatureFile->url, $documentRequest->signature_url);
    }

    public function test_document_request_file_methods()
    {
        $documentRequest = DocumentRequest::factory()->create();
        
        // Create different types of files
        $signatureFile = DocumentFile::factory()->create([
            'document_request_id' => $documentRequest->id,
            'file_type' => 'signature'
        ]);
        
        $documentFile = DocumentFile::factory()->create([
            'document_request_id' => $documentRequest->id,
            'file_type' => 'transcript_of_records'
        ]);
        
        $this->assertEquals($signatureFile->id, $documentRequest->signatureFile()->id);
        $this->assertEquals(1, $documentRequest->supportingDocuments()->count());
        $this->assertEquals($documentFile->id, $documentRequest->getDocumentByType('transcript_of_records')->id);
    }

    public function test_api_key_factory()
    {
        $apiKey = ApiKey::factory()->create();
        
        $this->assertNotEmpty($apiKey->key);
        $this->assertIsBool($apiKey->is_active);
        $this->assertTrue($apiKey->is_active); // Default should be true
    }

    public function test_document_request_factory()
    {
        $documentRequest = DocumentRequest::factory()->create();
        
        $this->assertNotEmpty($documentRequest->learning_reference_number);
        $this->assertNotEmpty($documentRequest->name_of_student);
        $this->assertNotEmpty($documentRequest->status);
        $this->assertNotEmpty($documentRequest->request_id);
    }

    public function test_document_file_factory()
    {
        $documentFile = DocumentFile::factory()->create();
        
        $this->assertNotEmpty($documentFile->file_name);
        $this->assertNotEmpty($documentFile->file_type);
        $this->assertIsInt($documentFile->file_size);
    }
}

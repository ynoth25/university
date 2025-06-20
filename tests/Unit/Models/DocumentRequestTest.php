<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\DocumentRequest;
use App\Models\DocumentFile;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DocumentRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_request_has_files_relationship()
    {
        $documentRequest = DocumentRequest::factory()->create();
        $file = DocumentFile::factory()->create([
            'document_request_id' => $documentRequest->id,
        ]);

        $this->assertTrue($documentRequest->files->contains($file));
        $this->assertEquals(1, $documentRequest->files->count());
    }

    public function test_pending_scope_filters_correctly()
    {
        DocumentRequest::factory()->create(['status' => 'pending']);
        DocumentRequest::factory()->create(['status' => 'processing']);
        DocumentRequest::factory()->create(['status' => 'completed']);

        $pendingRequests = DocumentRequest::pending()->get();

        $this->assertEquals(1, $pendingRequests->count());
        $this->assertEquals('pending', $pendingRequests->first()->status);
    }

    public function test_processing_scope_filters_correctly()
    {
        DocumentRequest::factory()->create(['status' => 'pending']);
        DocumentRequest::factory()->create(['status' => 'processing']);
        DocumentRequest::factory()->create(['status' => 'completed']);

        $processingRequests = DocumentRequest::processing()->get();

        $this->assertEquals(1, $processingRequests->count());
        $this->assertEquals('processing', $processingRequests->first()->status);
    }

    public function test_completed_scope_filters_correctly()
    {
        DocumentRequest::factory()->create(['status' => 'pending']);
        DocumentRequest::factory()->create(['status' => 'processing']);
        DocumentRequest::factory()->create(['status' => 'completed']);

        $completedRequests = DocumentRequest::completed()->get();

        $this->assertEquals(1, $completedRequests->count());
        $this->assertEquals('completed', $completedRequests->first()->status);
    }

    public function test_rejected_scope_filters_correctly()
    {
        DocumentRequest::factory()->create(['status' => 'pending']);
        DocumentRequest::factory()->create(['status' => 'rejected']);
        DocumentRequest::factory()->create(['status' => 'completed']);

        $rejectedRequests = DocumentRequest::rejected()->get();

        $this->assertEquals(1, $rejectedRequests->count());
        $this->assertEquals('rejected', $rejectedRequests->first()->status);
    }

    public function test_request_id_is_generated_on_creation()
    {
        $documentRequest = DocumentRequest::factory()->create();

        $this->assertNotNull($documentRequest->request_id);
        $this->assertStringStartsWith('DOC-', $documentRequest->request_id);
    }

    public function test_person_requesting_is_casted_to_array()
    {
        $personData = ['name' => 'John Doe', 'email' => 'john@example.com'];
        $documentRequest = DocumentRequest::factory()->create([
            'person_requesting' => $personData
        ]);

        $this->assertIsArray($documentRequest->person_requesting);
        $this->assertEquals($personData, $documentRequest->person_requesting);
    }

    public function test_status_is_fillable()
    {
        $documentRequest = DocumentRequest::factory()->create(['status' => 'completed']);

        $this->assertEquals('completed', $documentRequest->status);
    }

    public function test_processed_at_is_fillable()
    {
        $now = now();
        $documentRequest = DocumentRequest::factory()->create(['processed_at' => $now]);

        $this->assertEquals($now->toDateTimeString(), $documentRequest->processed_at->toDateTimeString());
    }

    public function test_signature_file_method()
    {
        $documentRequest = DocumentRequest::factory()->create();
        $signatureFile = DocumentFile::factory()->create([
            'document_request_id' => $documentRequest->id,
            'file_type' => 'signature'
        ]);

        $foundSignature = $documentRequest->signatureFile();

        $this->assertEquals($signatureFile->id, $foundSignature->id);
    }

    public function test_supporting_documents_method()
    {
        $documentRequest = DocumentRequest::factory()->create();
        DocumentFile::factory()->create([
            'document_request_id' => $documentRequest->id,
            'file_type' => 'signature'
        ]);
        DocumentFile::factory()->create([
            'document_request_id' => $documentRequest->id,
            'file_type' => 'transcript_of_records'
        ]);
        DocumentFile::factory()->create([
            'document_request_id' => $documentRequest->id,
            'file_type' => 'birth_certificate'
        ]);

        $supportingDocs = $documentRequest->supportingDocuments();

        $this->assertEquals(2, $supportingDocs->count());
        $this->assertNotContains('signature', $supportingDocs->pluck('file_type'));
    }

    public function test_get_document_by_type_method()
    {
        $documentRequest = DocumentRequest::factory()->create();
        $transcriptFile = DocumentFile::factory()->create([
            'document_request_id' => $documentRequest->id,
            'file_type' => 'transcript_of_records'
        ]);

        $foundFile = $documentRequest->getDocumentByType('transcript_of_records');

        $this->assertEquals($transcriptFile->id, $foundFile->id);
    }

    public function test_person_requesting_name_attribute()
    {
        $documentRequest = DocumentRequest::factory()->create([
            'person_requesting' => ['name' => 'John Doe', 'email' => 'john@example.com']
        ]);

        $this->assertEquals('John Doe', $documentRequest->person_requesting_name);
    }

    public function test_request_type_attribute()
    {
        $documentRequest = DocumentRequest::factory()->create([
            'person_requesting' => ['name' => 'John Doe', 'request_for' => 'TRANSCRIPT']
        ]);

        $this->assertEquals('TRANSCRIPT', $documentRequest->request_type);
    }

    public function test_signature_url_attribute()
    {
        $documentRequest = DocumentRequest::factory()->create();
        $signatureFile = DocumentFile::factory()->create([
            'document_request_id' => $documentRequest->id,
            'file_type' => 'signature',
            'file_path' => 'https://example.com/signature.pdf'
        ]);

        $this->assertEquals('https://example.com/signature.pdf', $documentRequest->signature_url);
    }

    public function test_signature_url_attribute_returns_empty_when_no_signature()
    {
        $documentRequest = DocumentRequest::factory()->create();

        $this->assertEquals('', $documentRequest->signature_url);
    }

    public function test_generate_request_id_static_method()
    {
        $requestId1 = DocumentRequest::generateRequestId();
        $requestId2 = DocumentRequest::generateRequestId();

        $this->assertStringStartsWith('DOC-', $requestId1);
        $this->assertStringStartsWith('DOC-', $requestId2);
        $this->assertNotEquals($requestId1, $requestId2);
    }

    public function test_fillable_fields_are_settable()
    {
        $data = [
            'learning_reference_number' => '123456789',
            'name_of_student' => 'John Doe',
            'last_schoolyear_attended' => '2023-2024',
            'gender' => 'male',
            'grade' => '12',
            'section' => 'A',
            'major' => 'STEM',
            'adviser' => 'Dr. Smith',
            'contact_number' => '09123456789',
            'person_requesting' => ['name' => 'John Doe'],
            'status' => 'pending',
            'remarks' => 'Test remarks'
        ];

        $documentRequest = DocumentRequest::factory()->create($data);

        foreach ($data as $field => $value) {
            if ($field === 'person_requesting') {
                $this->assertEquals($value, $documentRequest->$field);
            } else {
                $this->assertEquals($value, $documentRequest->$field);
            }
        }
    }
}

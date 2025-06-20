<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\DocumentFile;
use App\Models\DocumentRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class DocumentFileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    public function test_document_file_has_document_request_relationship()
    {
        $documentRequest = DocumentRequest::factory()->create();
        $file = DocumentFile::factory()->create([
            'document_request_id' => $documentRequest->id,
        ]);

        $this->assertEquals($documentRequest->id, $file->documentRequest->id);
    }

    public function test_file_size_is_casted_to_integer()
    {
        $file = DocumentFile::factory()->create(['file_size' => '1024']);

        $this->assertIsInt($file->file_size);
        $this->assertEquals(1024, $file->file_size);
    }

    public function test_metadata_is_casted_to_array()
    {
        $metadata = ['description' => 'Test file', 'tags' => ['important']];
        $file = DocumentFile::factory()->create(['metadata' => $metadata]);

        $this->assertIsArray($file->metadata);
        $this->assertEquals($metadata, $file->metadata);
    }

    public function test_url_attribute_returns_file_path()
    {
        $file = DocumentFile::factory()->create([
            'file_path' => 'https://example.com/file.pdf'
        ]);

        $this->assertEquals('https://example.com/file.pdf', $file->url);
    }

    public function test_formatted_size_attribute_returns_human_readable_size()
    {
        $file = DocumentFile::factory()->create(['file_size' => 1024]);

        $this->assertEquals('1024 B', $file->formatted_size);
    }

    public function test_formatted_size_for_large_files()
    {
        $file = DocumentFile::factory()->create(['file_size' => 1048576]); // 1MB

        $this->assertEquals('1024 KB', $file->formatted_size);
    }

    public function test_formatted_size_for_very_large_files()
    {
        $file = DocumentFile::factory()->create(['file_size' => 1073741824]); // 1GB

        $this->assertEquals('1024 MB', $file->formatted_size);
    }

    public function test_exists_method_checks_s3_storage()
    {
        $file = DocumentFile::factory()->create([
            'file_name' => 'test/file.pdf'
        ]);

        // File doesn't exist in fake storage
        $this->assertFalse($file->exists());

        // Create file in fake storage
        Storage::disk('s3')->put('test/file.pdf', 'test content');
        $this->assertTrue($file->exists());
    }

    public function test_delete_file_method_deletes_from_s3()
    {
        $file = DocumentFile::factory()->create([
            'file_name' => 'test/file.pdf'
        ]);

        // Create file in fake storage
        Storage::disk('s3')->put('test/file.pdf', 'test content');
        $this->assertTrue($file->exists());

        // Delete file
        $result = $file->deleteFile();
        $this->assertTrue($result);
        $this->assertFalse($file->exists());
    }

    public function test_delete_file_method_returns_false_when_file_not_exists()
    {
        $file = DocumentFile::factory()->create([
            'file_name' => 'test/file.pdf'
        ]);

        // File doesn't exist in storage
        $result = $file->deleteFile();
        $this->assertFalse($result);
    }

    public function test_temporary_url_method_generates_temporary_url()
    {
        $file = DocumentFile::factory()->create([
            'file_name' => 'test/file.pdf'
        ]);

        // Create file in fake storage
        Storage::disk('s3')->put('test/file.pdf', 'test content');

        $temporaryUrl = $file->getTemporaryUrl(60);
        $this->assertIsString($temporaryUrl);
        $this->assertNotEmpty($temporaryUrl);
    }

    public function test_of_type_scope_filters_correctly()
    {
        DocumentFile::factory()->create(['file_type' => 'transcript_of_records']);
        DocumentFile::factory()->create(['file_type' => 'signature']);
        DocumentFile::factory()->create(['file_type' => 'transcript_of_records']);

        $transcriptFiles = DocumentFile::ofType('transcript_of_records')->get();

        $this->assertEquals(2, $transcriptFiles->count());
        $this->assertEquals('transcript_of_records', $transcriptFiles->first()->file_type);
    }

    public function test_for_request_scope_filters_correctly()
    {
        $request1 = DocumentRequest::factory()->create();
        $request2 = DocumentRequest::factory()->create();

        DocumentFile::factory()->create(['document_request_id' => $request1->id]);
        DocumentFile::factory()->create(['document_request_id' => $request2->id]);
        DocumentFile::factory()->create(['document_request_id' => $request1->id]);

        $request1Files = DocumentFile::forRequest($request1->id)->get();

        $this->assertEquals(2, $request1Files->count());
        $this->assertEquals($request1->id, $request1Files->first()->document_request_id);
    }

    public function test_model_deletes_file_from_s3_on_deletion()
    {
        $file = DocumentFile::factory()->create([
            'file_name' => 'test/file.pdf'
        ]);

        // Create file in fake storage
        Storage::disk('s3')->put('test/file.pdf', 'test content');
        $this->assertTrue($file->exists());

        // Delete the model
        $file->delete();

        // File should be deleted from S3
        $this->assertFalse(Storage::disk('s3')->exists('test/file.pdf'));
    }

    public function test_model_handles_missing_file_on_deletion()
    {
        $file = DocumentFile::factory()->create([
            'file_name' => 'test/file.pdf'
        ]);

        // File doesn't exist in storage
        $this->assertFalse($file->exists());

        // Should not throw an error when deleting
        $this->assertTrue($file->delete());
    }

    public function test_fillable_fields_are_settable()
    {
        $documentRequest = DocumentRequest::factory()->create();
        
        $data = [
            'document_request_id' => $documentRequest->id,
            'file_type' => 'transcript_of_records',
            'original_name' => 'test.pdf',
            'file_name' => 'uploads/test.pdf',
            'file_path' => 'https://example.com/test.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'metadata' => ['description' => 'Test file']
        ];

        $file = DocumentFile::factory()->create($data);

        foreach ($data as $field => $value) {
            if ($field === 'metadata') {
                $this->assertEquals($value, $file->$field);
            } else {
                $this->assertEquals($value, $file->$field);
            }
        }
    }
} 
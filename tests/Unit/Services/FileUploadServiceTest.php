<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FileUploadService;
use App\Models\DocumentRequest;
use App\Models\DocumentFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    public function test_get_allowed_file_types_returns_correct_types()
    {
        $allowedTypes = FileUploadService::getAllowedFileTypes();

        $expectedTypes = [
            'signature',
            'affidavit_of_loss',
            'birth_certificate',
            'valid_id',
            'transcript_of_records',
            'other'
        ];

        $this->assertEquals($expectedTypes, $allowedTypes);
    }

    public function test_get_file_type_config_returns_correct_config()
    {
        $config = FileUploadService::getFileTypeConfig('transcript_of_records');

        $this->assertIsArray($config);
        $this->assertEquals(15 * 1024 * 1024, $config['max_size']); // 15MB
        $this->assertContains('application/pdf', $config['allowed_mimes']);
        $this->assertEquals('supporting_documents', $config['folder']);
    }

    public function test_get_file_type_config_returns_null_for_invalid_type()
    {
        $config = FileUploadService::getFileTypeConfig('invalid_type');

        $this->assertNull($config);
    }

    public function test_validate_file_returns_empty_array_for_valid_file()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');
        $service = new FileUploadService();

        $errors = $service->validateFile($file, 'transcript_of_records');

        $this->assertEmpty($errors);
    }

    public function test_validate_file_returns_error_for_invalid_file_type()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');
        $service = new FileUploadService();

        $errors = $service->validateFile($file, 'invalid_type');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid file type', $errors[0]);
    }

    public function test_validate_file_returns_error_for_file_too_large()
    {
        $file = UploadedFile::fake()->create('document.pdf', 20 * 1024 * 1024, 'application/pdf'); // 20MB
        $service = new FileUploadService();

        $errors = $service->validateFile($file, 'transcript_of_records');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('File size exceeds', $errors[0]);
    }

    public function test_validate_file_returns_error_for_invalid_mime_type()
    {
        $file = UploadedFile::fake()->create('document.txt', 1024, 'text/plain');
        $service = new FileUploadService();

        $errors = $service->validateFile($file, 'transcript_of_records');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('File type not allowed', $errors[0]);
    }

    public function test_upload_file_creates_document_file_record()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');
        $documentRequest = DocumentRequest::factory()->create();
        $service = new FileUploadService();

        $documentFile = $service->uploadFile($file, $documentRequest, 'transcript_of_records');

        $this->assertInstanceOf(DocumentFile::class, $documentFile);
        $this->assertEquals($documentRequest->id, $documentFile->document_request_id);
        $this->assertEquals('transcript_of_records', $documentFile->file_type);
        $this->assertEquals('document.pdf', $documentFile->original_name);
        $this->assertEquals('application/pdf', $documentFile->mime_type);
        $this->assertEquals(1048576, $documentFile->file_size); // UploadedFile::fake() creates 1MB files
    }

    public function test_upload_file_uploads_to_s3()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');
        $documentRequest = DocumentRequest::factory()->create();
        $service = new FileUploadService();

        $documentFile = $service->uploadFile($file, $documentRequest, 'transcript_of_records');

        $this->assertTrue(Storage::disk('s3')->exists($documentFile->file_name));
    }

    public function test_upload_file_generates_unique_filename()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');
        $documentRequest = DocumentRequest::factory()->create();
        $service = new FileUploadService();

        $documentFile = $service->uploadFile($file, $documentRequest, 'transcript_of_records');

        $this->assertStringContainsString($documentRequest->request_id, $documentFile->file_name);
        $this->assertStringContainsString('transcript_of_records', $documentFile->file_name);
        $this->assertStringEndsWith('.pdf', $documentFile->file_name);
    }

    public function test_upload_file_throws_exception_for_invalid_file_type()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');
        $documentRequest = DocumentRequest::factory()->create();
        $service = new FileUploadService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid file type: invalid_type');

        $service->uploadFile($file, $documentRequest, 'invalid_type');
    }

    public function test_upload_file_throws_exception_for_file_too_large()
    {
        $file = UploadedFile::fake()->create('document.pdf', 20 * 1024 * 1024, 'application/pdf');
        $documentRequest = DocumentRequest::factory()->create();
        $service = new FileUploadService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File size exceeds');

        $service->uploadFile($file, $documentRequest, 'transcript_of_records');
    }

    public function test_upload_file_throws_exception_for_invalid_mime_type()
    {
        $file = UploadedFile::fake()->create('document.txt', 1024, 'text/plain');
        $documentRequest = DocumentRequest::factory()->create();
        $service = new FileUploadService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File type not allowed');

        $service->uploadFile($file, $documentRequest, 'transcript_of_records');
    }

    public function test_upload_multiple_files_returns_array_of_document_files()
    {
        $files = [
            UploadedFile::fake()->create('document1.pdf', 1024, 'application/pdf'),
            UploadedFile::fake()->create('document2.pdf', 1024, 'application/pdf'),
        ];
        $documentRequest = DocumentRequest::factory()->create();
        $service = new FileUploadService();

        $documentFiles = $service->uploadMultipleFiles($files, $documentRequest, 'transcript_of_records');

        $this->assertIsArray($documentFiles);
        $this->assertCount(2, $documentFiles);
        $this->assertInstanceOf(DocumentFile::class, $documentFiles[0]);
        $this->assertInstanceOf(DocumentFile::class, $documentFiles[1]);
    }

    public function test_delete_file_deletes_from_s3_and_database()
    {
        $documentFile = DocumentFile::factory()->create([
            'file_name' => 'test/file.pdf'
        ]);

        // Create file in fake storage
        Storage::disk('s3')->put('test/file.pdf', 'test content');
        $this->assertTrue(Storage::disk('s3')->exists('test/file.pdf'));

        $service = new FileUploadService();
        $result = $service->deleteFile($documentFile);

        $this->assertTrue($result);
        $this->assertFalse(Storage::disk('s3')->exists('test/file.pdf'));
        $this->assertDatabaseMissing('document_files', ['id' => $documentFile->id]);
    }

    public function test_update_file_deletes_old_and_uploads_new()
    {
        $oldFile = DocumentFile::factory()->create([
            'file_name' => 'old/file.pdf'
        ]);
        Storage::disk('s3')->put('old/file.pdf', 'old content');

        $newFile = UploadedFile::fake()->create('new_document.pdf', 1024, 'application/pdf');
        $service = new FileUploadService();

        $updatedFile = $service->updateFile($newFile, $oldFile);

        $this->assertInstanceOf(DocumentFile::class, $updatedFile);
        $this->assertEquals('new_document.pdf', $updatedFile->original_name);
        $this->assertFalse(Storage::disk('s3')->exists('old/file.pdf'));
        $this->assertTrue(Storage::disk('s3')->exists($updatedFile->file_name));
    }
} 
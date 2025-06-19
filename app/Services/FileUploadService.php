<?php

namespace App\Services;

use App\Models\DocumentFile;
use App\Models\DocumentRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class FileUploadService
{
    /**
     * Allowed file types and their configurations
     */
    private const ALLOWED_FILE_TYPES = [
        'signature' => [
            'max_size' => 5 * 1024 * 1024, // 5MB
            'allowed_mimes' => ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'],
            'folder' => 'signatures'
        ],
        'affidavit_of_loss' => [
            'max_size' => 10 * 1024 * 1024, // 10MB
            'allowed_mimes' => ['application/pdf', 'image/jpeg', 'image/png'],
            'folder' => 'supporting_documents'
        ],
        'birth_certificate' => [
            'max_size' => 10 * 1024 * 1024, // 10MB
            'allowed_mimes' => ['application/pdf', 'image/jpeg', 'image/png'],
            'folder' => 'supporting_documents'
        ],
        'valid_id' => [
            'max_size' => 10 * 1024 * 1024, // 10MB
            'allowed_mimes' => ['application/pdf', 'image/jpeg', 'image/png'],
            'folder' => 'supporting_documents'
        ],
        'transcript_of_records' => [
            'max_size' => 15 * 1024 * 1024, // 15MB
            'allowed_mimes' => ['application/pdf', 'image/jpeg', 'image/png'],
            'folder' => 'supporting_documents'
        ],
        'other' => [
            'max_size' => 10 * 1024 * 1024, // 10MB
            'allowed_mimes' => ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'folder' => 'supporting_documents'
        ]
    ];

    /**
     * Upload a file to S3 and create a DocumentFile record
     */
    public function uploadFile(UploadedFile $file, DocumentRequest $documentRequest, string $fileType): DocumentFile
    {
        // Validate file type
        if (!array_key_exists($fileType, self::ALLOWED_FILE_TYPES)) {
            throw new Exception("Invalid file type: {$fileType}");
        }

        $config = self::ALLOWED_FILE_TYPES[$fileType];

        // Validate file size
        if ($file->getSize() > $config['max_size']) {
            throw new Exception("File size exceeds maximum allowed size of " . $this->formatBytes($config['max_size']));
        }

        // Validate MIME type
        if (!in_array($file->getMimeType(), $config['allowed_mimes'])) {
            throw new Exception("File type not allowed. Allowed types: " . implode(', ', $config['allowed_mimes']));
        }

        // Generate unique filename
        $fileName = $this->generateFileName($file, $fileType, $documentRequest->request_id);
        $filePath = $config['folder'] . '/' . $fileName;

        // Upload to S3
        try {
            // Use put() method instead of putFileAs() for better compatibility
            $fileContent = file_get_contents($file->getRealPath());
            $uploadSuccess = Storage::disk('s3')->put($filePath, $fileContent);

            if (!$uploadSuccess) {
                throw new Exception('Failed to upload file to S3 - put returned false');
            }
        } catch (Exception $e) {
            \Log::error('S3 Upload Error: ' . $e->getMessage());
            \Log::error('File: ' . $fileName);
            \Log::error('Folder: ' . $config['folder']);
            throw new Exception('Failed to upload file to S3: ' . $e->getMessage());
        }

        // Get the public URL
        $publicUrl = Storage::disk('s3')->url($filePath);

        // Create DocumentFile record
        return DocumentFile::create([
            'document_request_id' => $documentRequest->id,
            'file_type' => $fileType,
            'original_name' => $file->getClientOriginalName(),
            'file_name' => $filePath,
            'file_path' => $publicUrl,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'metadata' => [
                'uploaded_at' => now()->toISOString(),
                'upload_method' => 'api',
                'file_extension' => $file->getClientOriginalExtension(),
            ]
        ]);
    }

    /**
     * Upload multiple files
     */
    public function uploadMultipleFiles(array $files, DocumentRequest $documentRequest, string $fileType): array
    {
        $uploadedFiles = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploadedFiles[] = $this->uploadFile($file, $documentRequest, $fileType);
            }
        }

        return $uploadedFiles;
    }

    /**
     * Delete a file from S3 and database
     */
    public function deleteFile(DocumentFile $documentFile): bool
    {
        try {
            // Delete from S3
            if ($documentFile->deleteFile()) {
                // Delete from database
                return $documentFile->delete();
            }
            return false;
        } catch (Exception $e) {
            // Log error but don't throw
            \Log::error('Failed to delete file: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing file
     */
    public function updateFile(UploadedFile $file, DocumentFile $documentFile): DocumentFile
    {
        // Delete old file
        $this->deleteFile($documentFile);

        // Upload new file
        return $this->uploadFile($file, $documentFile->documentRequest, $documentFile->file_type);
    }

    /**
     * Generate a unique filename
     */
    private function generateFileName(UploadedFile $file, string $fileType, string $requestId): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $randomString = Str::random(8);

        // Get the requestor's name from the document request
        $documentRequest = DocumentRequest::where('request_id', $requestId)->first();
        $requestorName = $documentRequest ? $this->sanitizeFileName($documentRequest->person_requesting['name'] ?? 'unknown') : 'unknown';

        return "{$requestId}_{$requestorName}_{$fileType}_{$timestamp}_{$randomString}.{$extension}";
    }

    /**
     * Sanitize filename to remove special characters and spaces
     */
    private function sanitizeFileName(string $name): string
    {
        // Remove special characters and replace spaces with underscores
        $sanitized = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);
        $sanitized = preg_replace('/\s+/', '_', trim($sanitized));

        // Limit length to 50 characters
        return substr($sanitized, 0, 50);
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get allowed file types
     */
    public static function getAllowedFileTypes(): array
    {
        return array_keys(self::ALLOWED_FILE_TYPES);
    }

    /**
     * Get file type configuration
     */
    public static function getFileTypeConfig(string $fileType): ?array
    {
        return self::ALLOWED_FILE_TYPES[$fileType] ?? null;
    }

    /**
     * Validate file before upload
     */
    public function validateFile(UploadedFile $file, string $fileType): array
    {
        $errors = [];

        if (!array_key_exists($fileType, self::ALLOWED_FILE_TYPES)) {
            $errors[] = "Invalid file type: {$fileType}";
            return $errors;
        }

        $config = self::ALLOWED_FILE_TYPES[$fileType];

        if ($file->getSize() > $config['max_size']) {
            $errors[] = "File size exceeds maximum allowed size of " . $this->formatBytes($config['max_size']);
        }

        if (!in_array($file->getMimeType(), $config['allowed_mimes'])) {
            $errors[] = "File type not allowed. Allowed types: " . implode(', ', $config['allowed_mimes']);
        }

        return $errors;
    }
}

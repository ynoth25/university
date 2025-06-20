<?php

namespace App\Http\Controllers\Api;

use App\Models\DocumentRequest;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FileUploadController extends BaseController
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Upload a file for a document request
     */
    public function upload(Request $request, string $documentRequestId): JsonResponse
    {
        try {
            // Find the document request
            $documentRequest = DocumentRequest::find((int) $documentRequestId);
            if (! $documentRequest) {
                return $this->sendError('Document request not found', [], 404);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'file' => 'required|file',
                'file_type' => 'required|string|in:'.implode(',', FileUploadService::getAllowedFileTypes()),
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation failed', $validator->errors(), 422);
            }

            $file = $request->file('file');
            $fileType = $request->input('file_type');

            // Validate file using service
            $validationErrors = $this->fileUploadService->validateFile($file, $fileType);
            if (! empty($validationErrors)) {
                return $this->sendError('File validation failed', ['errors' => $validationErrors], 422);
            }

            // Upload file
            $documentFile = $this->fileUploadService->uploadFile($file, $documentRequest, $fileType);

            return $this->sendCreated([
                'id' => $documentFile->id,
                'file_type' => $documentFile->file_type,
                'original_name' => $documentFile->original_name,
                'file_name' => $documentFile->file_name,
                'file_path' => $documentFile->file_path,
                'mime_type' => $documentFile->mime_type,
                'file_size' => $documentFile->file_size,
                'formatted_size' => $documentFile->formatted_size,
                'uploaded_at' => $documentFile->created_at,
            ], 'File uploaded successfully');
        } catch (\Exception $e) {
            return $this->sendError('Error uploading file: '.$e->getMessage(), [], 500);
        }
    }

    /**
     * Upload multiple files for a document request
     */
    public function uploadMultiple(Request $request, string $documentRequestId): JsonResponse
    {
        try {
            // Find the document request
            $documentRequest = DocumentRequest::find((int) $documentRequestId);
            if (! $documentRequest) {
                return $this->sendError('Document request not found', [], 404);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'files' => 'required|array|min:1',
                'files.*' => 'required|file',
                'file_type' => 'required|string|in:'.implode(',', FileUploadService::getAllowedFileTypes()),
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation failed', $validator->errors(), 422);
            }

            $files = $request->file('files');
            $fileType = $request->input('file_type');

            // Validate each file
            $uploadedFiles = [];
            $errors = [];

            foreach ($files as $index => $file) {
                $validationErrors = $this->fileUploadService->validateFile($file, $fileType);
                if (! empty($validationErrors)) {
                    $errors[] = [
                        'file_index' => $index,
                        'file_name' => $file->getClientOriginalName(),
                        'errors' => $validationErrors,
                    ];
                } else {
                    try {
                        $documentFile = $this->fileUploadService->uploadFile($file, $documentRequest, $fileType);
                        $uploadedFiles[] = [
                            'id' => $documentFile->id,
                            'file_type' => $documentFile->file_type,
                            'original_name' => $documentFile->original_name,
                            'file_name' => $documentFile->file_name,
                            'file_path' => $documentFile->file_path,
                            'mime_type' => $documentFile->mime_type,
                            'file_size' => $documentFile->file_size,
                            'formatted_size' => $documentFile->formatted_size,
                            'uploaded_at' => $documentFile->created_at,
                        ];
                    } catch (\Exception $e) {
                        $errors[] = [
                            'file_index' => $index,
                            'file_name' => $file->getClientOriginalName(),
                            'errors' => [$e->getMessage()],
                        ];
                    }
                }
            }

            $response = [
                'uploaded_files' => $uploadedFiles,
                'total_uploaded' => count($uploadedFiles),
                'total_files' => count($files),
            ];

            if (! empty($errors)) {
                $response['errors'] = $errors;

                return $this->sendResponse($response, 'Some files uploaded successfully, but some failed');
            }

            return $this->sendCreated($response, 'All files uploaded successfully');
        } catch (\Exception $e) {
            return $this->sendError('Error uploading files: '.$e->getMessage(), [], 500);
        }
    }

    /**
     * Get all files for a document request
     */
    public function getFiles(string $documentRequestId): JsonResponse
    {
        try {
            // Find the document request
            $documentRequest = DocumentRequest::find((int) $documentRequestId);
            if (! $documentRequest) {
                return $this->sendError('Document request not found', [], 404);
            }

            $files = $documentRequest->files()->get()->map(function ($file) {
                return [
                    'id' => $file->id,
                    'file_type' => $file->file_type,
                    'original_name' => $file->original_name,
                    'file_name' => $file->file_name,
                    'file_path' => $file->file_path,
                    'mime_type' => $file->mime_type,
                    'file_size' => $file->file_size,
                    'formatted_size' => $file->formatted_size,
                    'uploaded_at' => $file->created_at,
                    'metadata' => $file->metadata,
                ];
            });

            return $this->sendResponse([
                'files' => $files,
                'total_files' => $files->count(),
                'file_types' => $files->groupBy('file_type')->map->count(),
            ], 'Files retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Error retrieving files: '.$e->getMessage(), [], 500);
        }
    }

    /**
     * Get files by type for a document request
     */
    public function getFilesByType(string $documentRequestId, string $fileType): JsonResponse
    {
        try {
            // Find the document request
            $documentRequest = DocumentRequest::find((int) $documentRequestId);
            if (! $documentRequest) {
                return $this->sendError('Document request not found', [], 404);
            }

            $files = $documentRequest->files()->ofType($fileType)->get()->map(function ($file) {
                return [
                    'id' => $file->id,
                    'file_type' => $file->file_type,
                    'original_name' => $file->original_name,
                    'file_name' => $file->file_name,
                    'file_path' => $file->file_path,
                    'mime_type' => $file->mime_type,
                    'file_size' => $file->file_size,
                    'formatted_size' => $file->formatted_size,
                    'uploaded_at' => $file->created_at,
                    'metadata' => $file->metadata,
                ];
            });

            return $this->sendResponse([
                'files' => $files,
                'file_type' => $fileType,
                'total_files' => $files->count(),
            ], 'Files retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Error retrieving files: '.$e->getMessage(), [], 500);
        }
    }

    /**
     * Delete a file
     */
    public function deleteFile(string $documentRequestId, string $fileId): JsonResponse
    {
        try {
            // Find the document request
            $documentRequest = DocumentRequest::find((int) $documentRequestId);
            if (! $documentRequest) {
                return $this->sendError('Document request not found', [], 404);
            }

            // Find the file
            $documentFile = $documentRequest->files()->find($fileId);
            if (! $documentFile) {
                return $this->sendError('File not found', [], 404);
            }

            // Delete file from S3 (if it exists)
            if ($documentFile->exists()) {
                $documentFile->deleteFile();
            }

            // Delete from database
            $documentFile->delete();

            return $this->sendDeleted('File deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError('Error deleting file: '.$e->getMessage(), [], 500);
        }
    }

    /**
     * Update file metadata
     */
    public function updateFileMetadata(Request $request, string $documentRequestId, string $fileId): JsonResponse
    {
        try {
            // Find the document request
            $documentRequest = DocumentRequest::find((int) $documentRequestId);
            if (! $documentRequest) {
                return $this->sendError('Document request not found', [], 404);
            }

            // Find the file
            $documentFile = $documentRequest->files()->find($fileId);
            if (! $documentFile) {
                return $this->sendError('File not found', [], 404);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'metadata' => 'required|array',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation failed', $validator->errors(), 422);
            }

            // Update metadata
            $documentFile->update([
                'metadata' => array_merge($documentFile->metadata ?? [], $request->input('metadata')),
            ]);

            return $this->sendUpdated([
                'id' => $documentFile->id,
                'file_type' => $documentFile->file_type,
                'original_name' => $documentFile->original_name,
                'metadata' => $documentFile->metadata,
            ], 'File metadata updated successfully');
        } catch (\Exception $e) {
            return $this->sendError('Error updating file metadata: '.$e->getMessage(), [], 500);
        }
    }

    /**
     * Update a file
     */
    public function updateFile(Request $request, string $documentRequestId, string $fileId): JsonResponse
    {
        try {
            // Find the document request
            $documentRequest = DocumentRequest::find((int) $documentRequestId);
            if (! $documentRequest) {
                return $this->sendError('Document request not found', [], 404);
            }

            // Find the file
            $documentFile = $documentRequest->files()->find($fileId);
            if (! $documentFile) {
                return $this->sendError('File not found', [], 404);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'file' => 'required|file',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation failed', $validator->errors(), 422);
            }

            $file = $request->file('file');

            // Validate file using service
            $validationErrors = $this->fileUploadService->validateFile($file, $documentFile->file_type);
            if (! empty($validationErrors)) {
                return $this->sendError('File validation failed', ['errors' => $validationErrors], 422);
            }

            // Update file
            $updatedFile = $this->fileUploadService->updateFile($file, $documentFile);

            return $this->sendUpdated([
                'id' => $updatedFile->id,
                'file_type' => $updatedFile->file_type,
                'original_name' => $updatedFile->original_name,
                'file_name' => $updatedFile->file_name,
                'file_path' => $updatedFile->file_path,
                'mime_type' => $updatedFile->mime_type,
                'file_size' => $updatedFile->file_size,
                'formatted_size' => $updatedFile->formatted_size,
                'updated_at' => $updatedFile->updated_at,
            ], 'File updated successfully');
        } catch (\Exception $e) {
            return $this->sendError('Error updating file: '.$e->getMessage(), [], 500);
        }
    }

    /**
     * Get file information
     */
    public function getFileInfo(string $documentRequestId, string $fileId): JsonResponse
    {
        try {
            // Find the document request
            $documentRequest = DocumentRequest::find((int) $documentRequestId);
            if (! $documentRequest) {
                return $this->sendError('Document request not found', [], 404);
            }

            // Find the file
            $documentFile = $documentRequest->files()->find($fileId);
            if (! $documentFile) {
                return $this->sendError('File not found', [], 404);
            }

            return $this->sendResponse([
                'id' => $documentFile->id,
                'file_type' => $documentFile->file_type,
                'original_name' => $documentFile->original_name,
                'file_name' => $documentFile->file_name,
                'file_path' => $documentFile->file_path,
                'mime_type' => $documentFile->mime_type,
                'file_size' => $documentFile->file_size,
                'formatted_size' => $documentFile->formatted_size,
                'uploaded_at' => $documentFile->created_at,
                'updated_at' => $documentFile->updated_at,
                'metadata' => $documentFile->metadata,
                'exists_in_s3' => $documentFile->exists(),
            ], 'File information retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Error retrieving file information: '.$e->getMessage(), [], 500);
        }
    }

    /**
     * Get allowed file types and their configurations
     */
    public function getAllowedFileTypes(): JsonResponse
    {
        try {
            $allowedTypes = FileUploadService::getAllowedFileTypes();
            $maxFileSize = 0;
            $allowedExtensions = [];

            foreach ($allowedTypes as $fileType) {
                $config = FileUploadService::getFileTypeConfig($fileType);
                $maxFileSize = max($maxFileSize, $config['max_size']);

                // Extract extensions from MIME types
                foreach ($config['allowed_mimes'] as $mimeType) {
                    $extension = $this->getExtensionFromMimeType($mimeType);
                    if ($extension) {
                        $allowedExtensions[] = $extension;
                    }
                }
            }

            // Remove duplicates
            $allowedExtensions = array_unique($allowedExtensions);

            return $this->sendResponse([
                'allowed_types' => $allowedTypes,
                'max_file_size' => $maxFileSize,
                'max_file_size_formatted' => $this->formatBytes($maxFileSize),
                'allowed_extensions' => $allowedExtensions,
            ], 'Allowed file types retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Error retrieving file types: '.$e->getMessage(), [], 500);
        }
    }

    /**
     * Get file extension from MIME type
     */
    private function getExtensionFromMimeType(string $mimeType): ?string
    {
        $mimeToExtension = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        ];

        return $mimeToExtension[$mimeType] ?? null;
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

        return round($bytes, 2).' '.$units[$i];
    }
}

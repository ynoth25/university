<?php

use App\Http\Controllers\Api\DocumentRequestController;
use App\Http\Controllers\Api\FileUploadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API Version 1 Routes
Route::prefix('v1')->group(function () {
    // Public routes (no authentication required)
    Route::get('/health', function () {
        return response()->json([
            'status' => 'success',
            'message' => 'API is running',
            'timestamp' => now()->toISOString(),
        ]);
    });

    // Document Request Routes (with API key authentication)
    Route::middleware('api.key')->group(function () {
        // Additional routes (must come before apiResource to avoid conflicts)
        Route::get('/document-requests/statistics', [DocumentRequestController::class, 'statistics']);
        Route::get('/document-requests/request/{requestId}', [DocumentRequestController::class, 'showByRequestId']);

        // CRUD operations
        Route::apiResource('document-requests', DocumentRequestController::class);

        // Status update route
        Route::patch('/document-requests/{id}/status', [DocumentRequestController::class, 'updateStatus']);

        // File Upload Routes
        Route::prefix('document-requests/{documentRequestId}/files')->group(function () {
            // Upload routes
            Route::post('/upload', [FileUploadController::class, 'upload']);
            Route::post('/upload-multiple', [FileUploadController::class, 'uploadMultiple']);

            // File management routes
            Route::get('/', [FileUploadController::class, 'getFiles']);
            Route::get('/type/{fileType}', [FileUploadController::class, 'getFilesByType']);
            Route::get('/{fileId}', [FileUploadController::class, 'getFileInfo']);
            Route::put('/{fileId}', [FileUploadController::class, 'updateFile']);
            Route::patch('/{fileId}/metadata', [FileUploadController::class, 'updateFileMetadata']);
            Route::delete('/{fileId}', [FileUploadController::class, 'deleteFile']);
        });

        // File type information
        Route::get('/file-types', [FileUploadController::class, 'getAllowedFileTypes']);
    });
});

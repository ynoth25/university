<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\DocumentRequestRequest;
use App\Http\Resources\DocumentRequestResource;
use App\Models\DocumentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentRequestController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = DocumentRequest::query();

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by request type
            if ($request->has('request_type')) {
                $query->whereJsonContains('person_requesting->request_for', $request->request_type);
            }

            // Search by student name
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name_of_student', 'like', "%{$search}%")
                      ->orWhere('learning_reference_number', 'like', "%{$search}%")
                      ->orWhere('request_id', 'like', "%{$search}%");
                });
            }

            // Sort by created_at desc by default
            $query->orderBy('created_at', 'desc');

            $documentRequests = $query->paginate($request->get('per_page', 15));

            return $this->sendResponse(
                DocumentRequestResource::collection($documentRequests),
                'Document requests retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->sendError('Error retrieving document requests', [], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DocumentRequestRequest $request): JsonResponse
    {
        try {
            $documentRequest = DocumentRequest::create($request->validated());

            return $this->sendCreated(
                new DocumentRequestResource($documentRequest),
                'Document request created successfully'
            );
        } catch (\Exception $e) {
            return $this->sendError('Error creating document request', [], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $documentRequest = DocumentRequest::find($id);

            if (!$documentRequest) {
                return $this->sendError('Document request not found', [], 404);
            }

            return $this->sendResponse(
                new DocumentRequestResource($documentRequest),
                'Document request retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->sendError('Error retrieving document request', [], 500);
        }
    }

    /**
     * Display the specified resource by request ID.
     */
    public function showByRequestId(string $requestId): JsonResponse
    {
        try {
            $documentRequest = DocumentRequest::where('request_id', $requestId)->first();

            if (!$documentRequest) {
                return $this->sendError('Document request not found', [], 404);
            }

            return $this->sendResponse(
                new DocumentRequestResource($documentRequest),
                'Document request retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->sendError('Error retrieving document request', [], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DocumentRequestRequest $request, string $id): JsonResponse
    {
        try {
            $documentRequest = DocumentRequest::find($id);

            if (!$documentRequest) {
                return $this->sendError('Document request not found', [], 404);
            }

            $documentRequest->update($request->validated());

            return $this->sendUpdated(
                new DocumentRequestResource($documentRequest),
                'Document request updated successfully'
            );
        } catch (\Exception $e) {
            return $this->sendError('Error updating document request', [], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $documentRequest = DocumentRequest::find($id);

            if (!$documentRequest) {
                return $this->sendError('Document request not found', [], 404);
            }

            $documentRequest->delete();

            return $this->sendDeleted('Document request deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError('Error deleting document request', [], 500);
        }
    }

    /**
     * Update the status of a document request.
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,processing,completed,rejected',
                'remarks' => 'nullable|string|max:1000',
            ]);

            $documentRequest = DocumentRequest::find($id);

            if (!$documentRequest) {
                return $this->sendError('Document request not found', [], 404);
            }

            $documentRequest->update([
                'status' => $request->status,
                'remarks' => $request->remarks,
                'processed_at' => $request->status === 'completed' ? now() : null,
            ]);

            return $this->sendUpdated(
                new DocumentRequestResource($documentRequest),
                'Document request status updated successfully'
            );
        } catch (\Exception $e) {
            return $this->sendError('Error updating document request status', [], 500);
        }
    }

    /**
     * Get statistics for document requests.
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total' => DocumentRequest::count(),
                'pending' => DocumentRequest::pending()->count(),
                'processing' => DocumentRequest::processing()->count(),
                'completed' => DocumentRequest::completed()->count(),
                'rejected' => DocumentRequest::rejected()->count(),
                'by_type' => [
                    'SF10' => DocumentRequest::whereJsonContains('person_requesting->request_for', 'SF10')->count(),
                    'ENROLLMENT_CERT' => DocumentRequest::whereJsonContains('person_requesting->request_for', 'ENROLLMENT_CERT')->count(),
                    'DIPLOMA' => DocumentRequest::whereJsonContains('person_requesting->request_for', 'DIPLOMA')->count(),
                    'CAV' => DocumentRequest::whereJsonContains('person_requesting->request_for', 'CAV')->count(),
                    'ENG. INST.' => DocumentRequest::whereJsonContains('person_requesting->request_for', 'ENG. INST.')->count(),
                    'CERT OF GRAD' => DocumentRequest::whereJsonContains('person_requesting->request_for', 'CERT OF GRAD')->count(),
                    'OTHERS' => DocumentRequest::whereJsonContains('person_requesting->request_for', 'OTHERS')->count(),
                ],
            ];

            return $this->sendResponse($stats, 'Statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Error retrieving statistics', [], 500);
        }
    }
}

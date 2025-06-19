<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\MessageBag;

class BaseController extends Controller
{
    /**
     * Success response method.
     *
     * @param mixed $result
     * @param string $message
     * @return JsonResponse
     */
    public function sendResponse($result, string $message = ''): JsonResponse
    {
        $response = [
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ];

        return response()->json($response, 200);
    }

    /**
     * Error response method.
     *
     * @param string $error
     * @param array|MessageBag $errorMessages
     * @param int $code
     * @return JsonResponse
     */
    public function sendError(string $error, $errorMessages = [], int $code = 404): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            // Convert MessageBag to array if needed
            if ($errorMessages instanceof \Illuminate\Support\MessageBag) {
                $response['data'] = $errorMessages->toArray();
            } else {
                $response['data'] = $errorMessages;
            }
        }

        return response()->json($response, $code);
    }

    /**
     * Success response method for created resources.
     *
     * @param mixed $result
     * @param string $message
     * @return JsonResponse
     */
    public function sendCreated($result, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->sendResponse($result, $message)->setStatusCode(201);
    }

    /**
     * Success response method for updated resources.
     *
     * @param mixed $result
     * @param string $message
     * @return JsonResponse
     */
    public function sendUpdated($result, string $message = 'Resource updated successfully'): JsonResponse
    {
        return $this->sendResponse($result, $message)->setStatusCode(200);
    }

    /**
     * Success response method for deleted resources.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function sendDeleted(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
        ], 204);
    }
}

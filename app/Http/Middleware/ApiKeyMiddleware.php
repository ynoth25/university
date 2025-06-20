<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key') ?? $request->header('Authorization');

        // Remove 'Bearer ' prefix if present
        if ($apiKey && str_starts_with($apiKey, 'Bearer ')) {
            $apiKey = substr($apiKey, 7);
        }

        if (! $apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key is required',
            ], 401);
        }

        $key = ApiKey::findByKey($apiKey);

        if (! $key || ! $key->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired API key',
            ], 401);
        }

        // Mark the key as used
        $key->markAsUsed();

        // Add the API key to the request for potential use in controllers
        $request->attributes->set('api_key', $key);

        return $next($request);
    }
}

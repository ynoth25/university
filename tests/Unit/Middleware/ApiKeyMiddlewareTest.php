<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\ApiKeyMiddleware;
use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class ApiKeyMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_middleware_passes_with_valid_api_key()
    {
        $apiKey = ApiKey::factory()->create(['is_active' => true]);
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-API-Key', $apiKey->key);

        $middleware = new ApiKeyMiddleware;
        $response = $middleware->handle($request, function ($request) {
            return new Response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_returns_401_without_api_key()
    {
        $request = Request::create('/api/test', 'GET');

        $middleware = new ApiKeyMiddleware;
        $response = $middleware->handle($request, function ($request) {
            return new Response('OK');
        });

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('API key is required', $response->getContent());
    }

    public function test_middleware_returns_401_with_invalid_api_key()
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-API-Key', 'invalid-key');

        $middleware = new ApiKeyMiddleware;
        $response = $middleware->handle($request, function ($request) {
            return new Response('OK');
        });

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Invalid or expired API key', $response->getContent());
    }

    public function test_middleware_returns_401_with_inactive_api_key()
    {
        $apiKey = ApiKey::factory()->create(['is_active' => false]);
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-API-Key', $apiKey->key);

        $middleware = new ApiKeyMiddleware;
        $response = $middleware->handle($request, function ($request) {
            return new Response('OK');
        });

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Invalid or expired API key', $response->getContent());
    }

    public function test_middleware_returns_401_with_expired_api_key()
    {
        $apiKey = ApiKey::factory()->create([
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-API-Key', $apiKey->key);

        $middleware = new ApiKeyMiddleware;
        $response = $middleware->handle($request, function ($request) {
            return new Response('OK');
        });

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Invalid or expired API key', $response->getContent());
    }

    public function test_middleware_updates_api_key_usage_stats()
    {
        $apiKey = ApiKey::factory()->create([
            'is_active' => true,
            'last_used_at' => now()->subDay(),
        ]);
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-API-Key', $apiKey->key);

        $middleware = new ApiKeyMiddleware;
        $response = $middleware->handle($request, function ($request) {
            return new Response('OK');
        });

        $apiKey->refresh();
        $this->assertGreaterThan(
            now()->subMinute()->timestamp,
            $apiKey->last_used_at->timestamp
        );
    }

    public function test_middleware_handles_case_insensitive_header()
    {
        $apiKey = ApiKey::factory()->create(['is_active' => true]);
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('x-api-key', $apiKey->key);

        $middleware = new ApiKeyMiddleware;
        $response = $middleware->handle($request, function ($request) {
            return new Response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }
}

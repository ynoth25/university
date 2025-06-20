<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\BaseController;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class ApiBaseTest extends TestCase
{
    use RefreshDatabase;

    protected BaseController $baseController;
    protected ApiKey $apiKey;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->baseController = new BaseController();
        
        $this->user = User::factory()->create();
        $this->apiKey = ApiKey::factory()->create([
            'key' => 'test-api-key-12345',
            'is_active' => true,
        ]);
    }

    public function test_base_controller_send_response()
    {
        $data = ['test' => 'data'];
        $message = 'Test message';
        
        $response = $this->baseController->sendResponse($data, $message);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals($message, $responseData['message']);
        $this->assertEquals($data, $responseData['data']);
    }

    public function test_base_controller_send_error()
    {
        $message = 'Error message';
        $errors = ['field' => 'error'];
        $code = 422;
        
        $response = $this->baseController->sendError($message, $errors, $code);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals($code, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals($message, $responseData['message']);
        $this->assertEquals($errors, $responseData['errors']);
    }

    public function test_base_controller_send_created()
    {
        $data = ['id' => 1, 'name' => 'test'];
        $message = 'Created successfully';
        
        $response = $this->baseController->sendCreated($data, $message);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals($message, $responseData['message']);
        $this->assertEquals($data, $responseData['data']);
    }

    public function test_base_controller_send_no_content()
    {
        $response = $this->baseController->sendNoContent();
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    public function test_api_key_middleware_valid_key()
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
        ])->get('/api/document-requests');

        $response->assertStatus(200);
    }

    public function test_api_key_middleware_missing_key()
    {
        $response = $this->get('/api/document-requests');

        $response->assertStatus(401);
    }

    public function test_api_key_middleware_invalid_key()
    {
        $response = $this->withHeaders([
            'X-API-Key' => 'invalid-key',
        ])->get('/api/document-requests');

        $response->assertStatus(401);
    }

    public function test_api_key_middleware_inactive_key()
    {
        $inactiveKey = ApiKey::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->withHeaders([
            'X-API-Key' => $inactiveKey->key,
        ])->get('/api/document-requests');

        $response->assertStatus(401);
    }

    public function test_api_routes_require_api_key()
    {
        $routes = [
            '/api/document-requests',
            '/api/document-requests/1',
            '/api/document-requests/1/files',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertStatus(401);
        }
    }

    public function test_api_routes_with_valid_key()
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
        ])->get('/api/document-requests');

        $response->assertStatus(200);
    }

    public function test_api_response_structure()
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
        ])->get('/api/document-requests');

        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'document_requests',
                'total',
                'per_page',
                'current_page',
                'last_page',
            ]
        ]);
    }

    public function test_api_error_response_structure()
    {
        $response = $this->get('/api/document-requests');

        $response->assertJsonStructure([
            'success',
            'message',
            'errors',
        ]);
        
        $this->assertFalse($response->json('success'));
    }

    public function test_api_pagination()
    {
        // Create multiple document requests
        for ($i = 0; $i < 15; $i++) {
            \App\Models\DocumentRequest::factory()->create([
                'learning_reference_number' => "LRN{$i}",
                'name_of_student' => "Student {$i}",
                'status' => 'pending',
            ]);
        }

        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
        ])->get('/api/document-requests?page=1&per_page=10');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertEquals(10, count($data['document_requests']));
        $this->assertEquals(15, $data['total']);
        $this->assertEquals(1, $data['current_page']);
        $this->assertEquals(2, $data['last_page']);
    }

    public function test_api_filtering()
    {
        \App\Models\DocumentRequest::factory()->create([
            'learning_reference_number' => 'LRN001',
            'name_of_student' => 'Student One',
            'status' => 'pending',
        ]);
        \App\Models\DocumentRequest::factory()->create([
            'learning_reference_number' => 'LRN002',
            'name_of_student' => 'Student Two',
            'status' => 'completed',
        ]);

        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
        ])->get('/api/document-requests?status=pending');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertEquals(1, $data['total']);
        $this->assertEquals('pending', $data['document_requests'][0]['status']);
    }

    public function test_api_searching()
    {
        \App\Models\DocumentRequest::factory()->create([
            'learning_reference_number' => 'LRN001',
            'name_of_student' => 'John Doe',
        ]);
        \App\Models\DocumentRequest::factory()->create([
            'learning_reference_number' => 'LRN002',
            'name_of_student' => 'Jane Smith',
        ]);

        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
        ])->get('/api/document-requests?search=John');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertEquals(1, $data['total']);
        $this->assertEquals('John Doe', $data['document_requests'][0]['name_of_student']);
    }

    public function test_api_sorting()
    {
        \App\Models\DocumentRequest::factory()->create([
            'learning_reference_number' => 'LRN001',
            'name_of_student' => 'Alice',
            'created_at' => now()->subDays(1),
        ]);
        \App\Models\DocumentRequest::factory()->create([
            'learning_reference_number' => 'LRN002',
            'name_of_student' => 'Bob',
            'created_at' => now(),
        ]);

        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
        ])->get('/api/document-requests?sort=name_of_student&order=desc');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertEquals('Bob', $data['document_requests'][0]['name_of_student']);
    }

    public function test_api_validation_errors()
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
            'Content-Type' => 'application/json',
        ])->postJson('/api/document-requests', []);

        $response->assertStatus(422);
        
        $data = $response->json();
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('errors', $data);
    }

    public function test_api_not_found_response()
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
        ])->get('/api/document-requests/99999');

        $response->assertStatus(404);
        
        $data = $response->json();
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('not found', $data['message']);
    }

    public function test_api_method_not_allowed()
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
        ])->patch('/api/document-requests/1');

        $response->assertStatus(405);
    }

    public function test_api_accept_header()
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
            'Accept' => 'application/json',
        ])->get('/api/document-requests');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
    }
}

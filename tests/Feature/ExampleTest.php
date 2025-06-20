<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\BaseController;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class ExampleTest extends TestCase
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
        $this->assertEquals($errors, $responseData['data']);
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

    public function test_base_controller_send_updated()
    {
        $data = ['id' => 1, 'name' => 'updated'];
        $message = 'Updated successfully';
        
        $response = $this->baseController->sendUpdated($data, $message);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals($message, $responseData['message']);
        $this->assertEquals($data, $responseData['data']);
    }

    public function test_base_controller_send_deleted()
    {
        $message = 'Deleted successfully';
        
        $response = $this->baseController->sendDeleted($message);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(204, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals($message, $responseData['message']);
    }

    public function test_api_key_middleware_valid_key()
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
        ])->get('/api/v1/document-requests');

        $response->assertStatus(200);
    }

    public function test_api_key_middleware_missing_key()
    {
        $response = $this->get('/api/v1/document-requests');

        $response->assertStatus(401);
    }

    public function test_api_key_middleware_invalid_key()
    {
        $response = $this->withHeaders([
            'X-API-Key' => 'invalid-key',
        ])->get('/api/v1/document-requests');

        $response->assertStatus(401);
    }

    public function test_api_key_middleware_inactive_key()
    {
        $inactiveKey = ApiKey::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->withHeaders([
            'X-API-Key' => $inactiveKey->key,
        ])->get('/api/v1/document-requests');

        $response->assertStatus(401);
    }

    public function test_api_routes_require_api_key()
    {
        $routes = [
            '/api/v1/document-requests',
            '/api/v1/document-requests/1',
            '/api/v1/document-requests/1/files',
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
        ])->get('/api/v1/document-requests');

        $response->assertStatus(200);
    }

    public function test_api_response_structure()
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
        ])->get('/api/v1/document-requests');

        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'request_id',
                    'name_of_student',
                    'status',
                    'created_at'
                ]
            ]
        ]);
    }

    public function test_api_error_response_structure()
    {
        $response = $this->get('/api/v1/document-requests');

        $response->assertJsonStructure([
            'success',
            'message',
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
        ])->get('/api/v1/document-requests?page=1&per_page=10');

        $response->assertStatus(200);
        
        $this->assertCount(10, $response['data']);
    }

    public function test_api_filtering()
    {
        \App\Models\DocumentRequest::factory()->create([
            'learning_reference_number' => 'LRN001',
            'name_of_student' => 'Student 1',
            'status' => 'pending',
        ]);
        \App\Models\DocumentRequest::factory()->create([
            'learning_reference_number' => 'LRN002',
            'name_of_student' => 'Student 2',
            'status' => 'completed',
        ]);

        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
        ])->get('/api/v1/document-requests?status=pending');

        $response->assertStatus(200);
        
        $this->assertCount(1, $response['data']);
        $this->assertEquals('pending', $response['data'][0]['status']);
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
        ])->get('/api/v1/document-requests?search=John');

        $response->assertStatus(200);
        
        $this->assertCount(1, $response['data']);
        $this->assertEquals('John Doe', $response['data'][0]['name_of_student']);
    }

    public function test_api_validation_errors()
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
            'Content-Type' => 'application/json',
        ])->postJson('/api/v1/document-requests', []);

        $response->assertStatus(422);
        
        $data = $response->json();
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('data', $data);
    }

    public function test_api_not_found_response()
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
        ])->get('/api/v1/document-requests/999999');

        $response->assertStatus(404);
    }

    public function test_api_method_not_allowed()
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
        ])->patch('/api/v1/document-requests/1');

        $response->assertStatus(405);
    }

    public function test_api_accept_header()
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey->key,
            'Accept' => 'application/json',
        ])->get('/api/v1/document-requests');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
    }
}

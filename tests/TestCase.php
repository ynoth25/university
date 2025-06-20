<?php

namespace Tests;

use App\Models\ApiKey;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected ApiKey $testApiKey;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Helper method to get or create a test API key
     */
    protected function getTestApiKey(): ApiKey
    {
        if (!isset($this->testApiKey)) {
            $this->testApiKey = ApiKey::createKey('Test API Key');
        }
        return $this->testApiKey;
    }

    /**
     * Helper method to make authenticated API requests
     */
    protected function authenticatedRequest(
        string $method,
        string $uri,
        array $data = []
    ): \Illuminate\Testing\TestResponse {
        return $this->withHeaders([
            'X-API-Key' => $this->getTestApiKey()->key,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->json($method, $uri, $data);
    }

    /**
     * Helper method to create a document request for testing
     */
    protected function createDocumentRequest(array $attributes = []): \App\Models\DocumentRequest
    {
        return \App\Models\DocumentRequest::factory()->create($attributes);
    }

    /**
     * Helper method to get valid document request data
     */
    protected function getValidDocumentRequestData(): array
    {
        return [
            'learning_reference_number' => '123456789',
            'name_of_student' => 'John Doe',
            'last_schoolyear_attended' => '2023-2024',
            'gender' => 'male',
            'grade' => '12',
            'section' => 'A',
            'major' => 'STEM',
            'adviser' => 'Mrs. Smith',
            'contact_number' => '09123456789',
            'person_requesting' => [
                'name' => 'John Doe',
                'request_for' => 'SF10',
                'signature' => 'https://example.com/signature.jpg'
            ]
        ];
    }
}

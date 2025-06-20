<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_key_is_generated_on_creation()
    {
        $apiKey = ApiKey::factory()->create();

        $this->assertNotNull($apiKey->key);
        $this->assertStringStartsWith('test_', $apiKey->key); // Factory generates test_ prefixed keys
        $this->assertEquals(37, strlen($apiKey->key)); // test_ + 32 random chars
    }

    public function test_key_is_unique()
    {
        $apiKey1 = ApiKey::factory()->create();
        $apiKey2 = ApiKey::factory()->create();

        $this->assertNotEquals($apiKey1->key, $apiKey2->key);
    }

    public function test_find_by_key_method_returns_correct_key()
    {
        $apiKey = ApiKey::factory()->create();

        $foundKey = ApiKey::findByKey($apiKey->key);

        $this->assertEquals($apiKey->id, $foundKey->id);
    }

    public function test_find_by_key_method_returns_null_for_invalid_key()
    {
        $foundKey = ApiKey::findByKey('invalid-key');

        $this->assertNull($foundKey);
    }

    public function test_is_active_attribute_is_boolean()
    {
        $apiKey = ApiKey::factory()->create(['is_active' => true]);

        $this->assertIsBool($apiKey->is_active);
        $this->assertTrue($apiKey->is_active);
    }

    public function test_last_used_at_is_fillable()
    {
        $now = now();
        $apiKey = ApiKey::factory()->create(['last_used_at' => $now]);

        $this->assertEquals($now->toDateTimeString(), $apiKey->last_used_at->toDateTimeString());
    }

    public function test_is_active_defaults_to_true()
    {
        $apiKey = ApiKey::factory()->create();

        $this->assertTrue($apiKey->is_active);
    }

    public function test_name_is_fillable()
    {
        $apiKey = ApiKey::factory()->create(['name' => 'Test API Key']);

        $this->assertEquals('Test API Key', $apiKey->name);
    }

    public function test_expires_at_is_fillable()
    {
        $expiresAt = now()->addDays(30);
        $apiKey = ApiKey::factory()->create(['expires_at' => $expiresAt]);

        $this->assertEquals($expiresAt->toDateTimeString(), $apiKey->expires_at->toDateTimeString());
    }

    public function test_is_valid_method_returns_true_for_valid_key()
    {
        $apiKey = ApiKey::factory()->create([
            'is_active' => true,
            'expires_at' => now()->addDay()
        ]);

        $this->assertTrue($apiKey->isValid());
    }

    public function test_is_valid_method_returns_false_for_inactive_key()
    {
        $apiKey = ApiKey::factory()->create(['is_active' => false]);

        $this->assertFalse($apiKey->isValid());
    }

    public function test_is_valid_method_returns_false_for_expired_key()
    {
        $apiKey = ApiKey::factory()->create([
            'is_active' => true,
            'expires_at' => now()->subDay()
        ]);

        $this->assertFalse($apiKey->isValid());
    }

    public function test_is_valid_method_returns_true_for_key_without_expiry()
    {
        $apiKey = ApiKey::factory()->create([
            'is_active' => true,
            'expires_at' => null
        ]);

        $this->assertTrue($apiKey->isValid());
    }

    public function test_mark_as_used_method_updates_last_used_at()
    {
        $apiKey = ApiKey::factory()->create(['last_used_at' => now()->subDay()]);

        $apiKey->markAsUsed();

        $this->assertGreaterThan(
            now()->subMinute()->timestamp,
            $apiKey->fresh()->last_used_at->timestamp
        );
    }

    public function test_generate_key_static_method()
    {
        $key1 = ApiKey::generateKey();
        $key2 = ApiKey::generateKey();

        $this->assertStringStartsWith('sk-', $key1);
        $this->assertStringStartsWith('sk-', $key2);
        $this->assertNotEquals($key1, $key2);
        $this->assertEquals(35, strlen($key1));
        $this->assertEquals(35, strlen($key2));
    }
}

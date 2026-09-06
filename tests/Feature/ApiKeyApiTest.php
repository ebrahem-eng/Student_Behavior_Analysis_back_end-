<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ApiKeyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_generate_api_key()
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin);

        $payload = [
            'name' => 'LMS External Integration',
            'abilities' => ['read:grades'],
            'rate_limit' => 120,
        ];

        $response = $this->postJson('/api/admin/api-keys', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'LMS External Integration')
            ->assertJsonStructure(['data' => ['plain_key']]);

        $this->assertDatabaseHas('api_keys', ['name' => 'LMS External Integration']);
    }

    public function test_can_toggle_and_delete_api_key()
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin);

        $keyData = ApiKey::generateKey();
        $apiKey = ApiKey::create([
            'user_id' => $admin->id,
            'name' => 'Test Key',
            'key_prefix' => $keyData['prefix'],
            'key_hash' => $keyData['hash'],
            'abilities' => ['*'],
            'is_active' => true,
        ]);

        $toggleResponse = $this->patchJson("/api/admin/api-keys/{$apiKey->id}/toggle");
        $toggleResponse->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $deleteResponse = $this->deleteJson("/api/admin/api-keys/{$apiKey->id}");
        $deleteResponse->assertStatus(204);

        $this->assertDatabaseMissing('api_keys', ['id' => $apiKey->id]);
    }
}

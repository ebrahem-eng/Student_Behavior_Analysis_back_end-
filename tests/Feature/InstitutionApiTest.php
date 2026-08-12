<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class InstitutionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_institutions()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Institution::create([
            'name' => 'Damascus University',
            'type' => 'university',
            'address' => 'Damascus',
        ]);

        $response = $this->getJson('/api/admin/institutions');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_can_create_institution()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Al-Hikma Academy',
            'type' => 'school',
            'address' => 'Aleppo',
        ];

        $response = $this->postJson('/api/admin/institutions', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Al-Hikma Academy');

        $this->assertDatabaseHas('institutions', ['name' => 'Al-Hikma Academy']);
    }
}

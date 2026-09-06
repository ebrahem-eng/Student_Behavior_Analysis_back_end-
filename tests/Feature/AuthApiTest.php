<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_token()
    {
        $user = User::factory()->create([
            'email' => 'testuser@sba.local',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'testuser@sba.local',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'access_token',
                'token_type',
                'user',
            ]);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'testuser@sba.local',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'testuser@sba.local',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_access_me_and_logout()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $meResponse = $this->getJson('/api/auth/me');
        $meResponse->assertStatus(200)
            ->assertJsonPath('data.id', $user->id);

        $logoutResponse = $this->postJson('/api/auth/logout');
        $logoutResponse->assertStatus(200)
            ->assertJsonPath('message', 'Successfully logged out');
    }

    public function test_authenticated_user_can_update_profile()
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@sba.local',
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/auth/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@sba.local',
            'phone' => '+963999888777',
            'avatar_url' => 'https://example.com/avatar.jpg',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.name', 'Updated Name')
            ->assertJsonPath('user.email', 'updated@sba.local')
            ->assertJsonPath('user.avatar_url', 'https://example.com/avatar.jpg');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@sba.local',
        ]);
    }

    public function test_authenticated_user_can_update_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/auth/password', [
            'current_password' => 'oldpassword123',
            'password' => 'NewPassword!123',
            'password_confirmation' => 'NewPassword!123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Password updated successfully');
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Alert;
use App\Models\ConsentLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class AlertsAndConsentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_and_mark_alert_as_read()
    {
        $student = User::factory()->create();
        $recipient = User::factory()->create();
        Sanctum::actingAs($recipient);

        $alert = Alert::create([
            'student_id' => $student->id,
            'recipient_id' => $recipient->id,
            'level' => 'high',
            'message' => 'Student has high risk score.',
            'is_read' => false,
        ]);

        $response = $this->getJson('/api/alerts');
        $response->assertStatus(200);

        $readResponse = $this->patchJson("/api/alerts/{$alert->id}/read");
        $readResponse->assertStatus(200)
            ->assertJsonPath('data.is_read', true);
    }

    public function test_can_grant_and_check_consent()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $grantResponse = $this->postJson('/api/consent', [
            'consent_type' => 'behavior_monitoring',
            'is_granted' => true,
        ]);

        $grantResponse->assertStatus(201)
            ->assertJsonPath('data.consent_type', 'behavior_monitoring');

        $checkResponse = $this->getJson('/api/consent/check/behavior_monitoring');
        $checkResponse->assertStatus(200)
            ->assertJsonPath('is_granted', true);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Message;
use Spatie\Permission\Models\Role;

class StudentAdvisorCommunicationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Advisor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Parent', 'guard_name' => 'web']);
    }

    public function test_student_can_send_message_to_advisor()
    {
        $advisor = User::factory()->create();
        $advisor->assignRole('Advisor');

        $student = User::factory()->create();
        $student->assignRole('Student');

        $response = $this->actingAs($student, 'sanctum')->postJson('/api/messages', [
            'recipient_id' => $advisor->id,
            'student_id' => $student->id,
            'message' => 'Hello advisor, I would like to inquire about tutoring sessions.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.message', 'Hello advisor, I would like to inquire about tutoring sessions.')
            ->assertJsonPath('data.sender_id', $student->id)
            ->assertJsonPath('data.recipient_id', $advisor->id)
            ->assertJsonPath('data.student_id', $student->id);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $student->id,
            'recipient_id' => $advisor->id,
            'student_id' => $student->id,
            'message' => 'Hello advisor, I would like to inquire about tutoring sessions.',
        ]);
    }

    public function test_student_can_fetch_conversation_with_advisor()
    {
        $advisor = User::factory()->create();
        $advisor->assignRole('Advisor');

        $student = User::factory()->create();
        $student->assignRole('Student');

        Message::create([
            'sender_id' => $student->id,
            'recipient_id' => $advisor->id,
            'student_id' => $student->id,
            'message' => 'Student question',
        ]);

        Message::create([
            'sender_id' => $advisor->id,
            'recipient_id' => $student->id,
            'student_id' => $student->id,
            'message' => 'Advisor response',
        ]);

        $response = $this->actingAs($student, 'sanctum')->getJson("/api/messages?student_id={$student->id}&recipient_id={$advisor->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_advisor_can_fetch_and_reply_to_student()
    {
        $advisor = User::factory()->create();
        $advisor->assignRole('Advisor');

        $student = User::factory()->create();
        $student->assignRole('Student');

        // Student sends message
        $msg = Message::create([
            'sender_id' => $student->id,
            'recipient_id' => $advisor->id,
            'student_id' => $student->id,
            'message' => 'Help needed with algorithms.',
        ]);

        // Advisor marks as read
        $readRes = $this->actingAs($advisor, 'sanctum')->patchJson("/api/messages/{$msg->id}/read");
        $readRes->assertStatus(200)->assertJsonPath('data.is_read', true);

        // Advisor replies
        $replyRes = $this->actingAs($advisor, 'sanctum')->postJson('/api/messages', [
            'recipient_id' => $student->id,
            'student_id' => $student->id,
            'message' => 'Sure! Tutoring session is scheduled tomorrow at 10 AM.',
        ]);

        $replyRes->assertStatus(201)
            ->assertJsonPath('data.sender_id', $advisor->id)
            ->assertJsonPath('data.recipient_id', $student->id);
    }
}

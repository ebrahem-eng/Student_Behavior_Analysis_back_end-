<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Institution;
use App\Models\Course;
use App\Models\Term;
use App\Models\Section;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class AcademicDataApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $institution;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $this->institution = Institution::create([
            'name' => 'Test Tech Institute',
            'type' => 'university',
            'address' => 'Main Campus',
        ]);
    }

    public function test_can_create_course()
    {
        $payload = [
            'institution_id' => $this->institution->id,
            'name' => 'Database Systems',
            'code' => 'CS301',
            'credits' => 3,
        ];

        $response = $this->postJson('/api/academic/courses', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Database Systems');
    }

    public function test_can_record_attendance()
    {
        $course = Course::create([
            'institution_id' => $this->institution->id,
            'name' => 'Algorithms',
            'code' => 'CS202',
            'credits' => 4,
        ]);

        $term = Term::create([
            'institution_id' => $this->institution->id,
            'name' => 'Fall 2026',
            'start_date' => '2026-09-01',
            'end_date' => '2027-01-15',
        ]);

        $section = Section::create([
            'course_id' => $course->id,
            'term_id' => $term->id,
            'name' => 'Section A',
        ]);

        $payload = [
            'user_id' => $this->user->id,
            'section_id' => $section->id,
            'date' => '2026-10-15',
            'status' => 'present',
            'notes' => 'Attended on time',
        ];

        $response = $this->postJson('/api/academic/attendances', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'present');
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\Institution;
use App\Models\User;
use App\Models\Term;
use App\Models\Course;
use App\Models\Section;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\BehaviorLog;
use App\Models\RiskThreshold;
use App\Models\Alert;
use App\Models\Recommendation;
use App\Models\ConsentLog;
use App\Models\ApiKey;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Reset cached roles and create Spatie RBAC Roles
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = ['Admin', 'Teacher', 'Advisor', 'Student', 'Parent'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 2. Create Institutions
        $uni = Institution::create([
            'name' => 'Al-Hikma International University',
            'type' => 'university',
            'address' => 'Damascus Highway, Building 4',
        ]);

        $school = Institution::create([
            'name' => 'Al-Amal Secondary School',
            'type' => 'school',
            'address' => 'Aleppo Education District',
        ]);

        // 3. Create Users for all roles
        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@sba.local',
            'password' => 'password',
            'institution_id' => $uni->id,
            'phone' => '+963991234567',
            'national_id' => '0102030405',
        ]);
        $admin->assignRole('Admin');

        $teacher = User::create([
            'name' => 'Dr. Omar Farooq',
            'email' => 'teacher@sba.local',
            'password' => 'password',
            'institution_id' => $uni->id,
            'phone' => '+963992345678',
            'national_id' => '0203040506',
        ]);
        $teacher->assignRole('Teacher');

        // Standalone / Unassigned Teacher (No institution or assigned sections)
        $unassignedTeacher = User::create([
            'name' => 'Standalone Teacher (No School)',
            'email' => 'newteacher@sba.local',
            'password' => 'password',
            'institution_id' => null,
            'phone' => null,
            'national_id' => null,
        ]);
        $unassignedTeacher->assignRole('Teacher');

        $advisor = User::create([
            'name' => 'Sarah Al-Mansoor',
            'email' => 'advisor@sba.local',
            'password' => 'password',
            'institution_id' => $uni->id,
            'phone' => '+963993456789',
            'national_id' => '0304050607',
        ]);
        $advisor->assignRole('Advisor');

        $student1 = User::create([
            'name' => 'Ahmad Al-Khatib',
            'email' => 'student@sba.local',
            'password' => 'password',
            'institution_id' => $uni->id,
            'phone' => '+963994567890',
            'national_id' => '0405060708',
        ]);
        $student1->assignRole('Student');

        $student2 = User::create([
            'name' => 'Lina Haddad',
            'email' => 'student2@sba.local',
            'password' => 'password',
            'institution_id' => $uni->id,
            'phone' => '+963995678901',
            'national_id' => '0506070809',
        ]);
        $student2->assignRole('Student');

        $parent = User::create([
            'name' => 'Khaled Al-Khatib',
            'email' => 'parent@sba.local',
            'password' => 'password',
            'institution_id' => $uni->id,
            'phone' => '+963996789012',
            'national_id' => '0607080910',
        ]);
        $parent->assignRole('Parent');

        // 4. Academic Structure (Terms, Courses, Sections)
        $fallTerm = Term::create([
            'institution_id' => $uni->id,
            'name' => 'Fall 2026',
            'start_date' => '2026-09-01',
            'end_date' => '2027-01-15',
        ]);

        $springTerm = Term::create([
            'institution_id' => $uni->id,
            'name' => 'Spring 2027',
            'start_date' => '2027-02-01',
            'end_date' => '2027-06-15',
        ]);

        $course1 = Course::create([
            'institution_id' => $uni->id,
            'name' => 'Data Structures & Algorithms',
            'code' => 'CS201',
            'credits' => 4,
        ]);

        $course2 = Course::create([
            'institution_id' => $uni->id,
            'name' => 'Database Management Systems',
            'code' => 'CS301',
            'credits' => 3,
        ]);

        $course3 = Course::create([
            'institution_id' => $uni->id,
            'name' => 'Machine Learning & AI',
            'code' => 'CS401',
            'credits' => 3,
        ]);

        $sec1 = Section::create([
            'course_id' => $course1->id,
            'term_id' => $fallTerm->id,
            'teacher_id' => $teacher->id,
            'capacity' => 30,
        ]);

        $sec2 = Section::create([
            'course_id' => $course2->id,
            'term_id' => $fallTerm->id,
            'teacher_id' => $teacher->id,
            'capacity' => 35,
        ]);

        $sec3 = Section::create([
            'course_id' => $course3->id,
            'term_id' => $fallTerm->id,
            'teacher_id' => $teacher->id,
            'capacity' => 25,
        ]);

        // 5. Enrollments
        $enr1 = Enrollment::create([
            'user_id' => $student1->id,
            'section_id' => $sec1->id,
            'status' => 'active',
        ]);

        $enr2 = Enrollment::create([
            'user_id' => $student1->id,
            'section_id' => $sec2->id,
            'status' => 'active',
        ]);

        $enr3 = Enrollment::create([
            'user_id' => $student2->id,
            'section_id' => $sec1->id,
            'status' => 'active',
        ]);

        $enr4 = Enrollment::create([
            'user_id' => $student2->id,
            'section_id' => $sec3->id,
            'status' => 'active',
        ]);

        // 6. Grades
        Grade::create([
            'enrollment_id' => $enr1->id,
            'exam_name' => 'Quiz 1',
            'score' => 55.00,
            'weight' => 0.15,
        ]);

        Grade::create([
            'enrollment_id' => $enr1->id,
            'exam_name' => 'Midterm Exam',
            'score' => 52.50,
            'weight' => 0.35,
        ]);

        Grade::create([
            'enrollment_id' => $enr3->id,
            'exam_name' => 'Quiz 1',
            'score' => 92.00,
            'weight' => 0.15,
        ]);

        Grade::create([
            'enrollment_id' => $enr3->id,
            'exam_name' => 'Midterm Exam',
            'score' => 88.00,
            'weight' => 0.35,
        ]);

        // 7. Attendance
        Attendance::create([
            'user_id' => $student1->id,
            'section_id' => $sec1->id,
            'date' => '2026-10-01',
            'status' => 'present',
            'notes' => 'Attended on time',
        ]);

        Attendance::create([
            'user_id' => $student1->id,
            'section_id' => $sec1->id,
            'date' => '2026-10-08',
            'status' => 'absent',
            'notes' => 'Unexcused absence',
        ]);

        Attendance::create([
            'user_id' => $student1->id,
            'section_id' => $sec1->id,
            'date' => '2026-10-15',
            'status' => 'late',
            'notes' => 'Arrived 20 mins late',
        ]);

        Attendance::create([
            'user_id' => $student2->id,
            'section_id' => $sec1->id,
            'date' => '2026-10-01',
            'status' => 'present',
            'notes' => 'Active participation',
        ]);

        // 8. Behavior Logs
        BehaviorLog::create([
            'user_id' => $student1->id,
            'reporter_id' => $teacher->id,
            'type' => 'warning',
            'description' => 'Student was distracted and missed homework submissions for two consecutive weeks.',
            'date' => '2026-10-10',
        ]);

        BehaviorLog::create([
            'user_id' => $student2->id,
            'reporter_id' => $teacher->id,
            'type' => 'positive',
            'description' => 'Exemplary project presentation and teamwork support.',
            'date' => '2026-10-12',
        ]);

        // 9. Risk Thresholds
        RiskThreshold::create([
            'institution_id' => $uni->id,
            'level' => 'low',
            'min_score' => 0.00,
            'max_score' => 39.99,
            'requires_action' => false,
        ]);

        RiskThreshold::create([
            'institution_id' => $uni->id,
            'level' => 'medium',
            'min_score' => 40.00,
            'max_score' => 74.99,
            'requires_action' => false,
        ]);

        RiskThreshold::create([
            'institution_id' => $uni->id,
            'level' => 'high',
            'min_score' => 75.00,
            'max_score' => 100.00,
            'requires_action' => true,
        ]);

        // 10. Alerts
        Alert::create([
            'student_id' => $student1->id,
            'recipient_id' => $advisor->id,
            'level' => 'high',
            'message' => 'Ahmad Al-Khatib has a high predicted dropout risk (Score: 82.4%). Factors: low attendance, declining midterm scores.',
            'is_read' => false,
        ]);

        Alert::create([
            'student_id' => $student1->id,
            'recipient_id' => $admin->id,
            'level' => 'high',
            'message' => 'High risk detected for student Ahmad Al-Khatib (CS201).',
            'is_read' => false,
        ]);

        // 11. Recommendations & Interventions
        Recommendation::create([
            'student_id' => $student1->id,
            'ai_suggested_action' => 'Assign 1-on-1 tutoring sessions in Data Structures and schedule an academic counselling meeting with the student and advisor.',
            'advisor_id' => $advisor->id,
            'teacher_id' => $teacher->id,
            'status' => 'approved',
            'outcome_notes' => null,
        ]);

        // 12. Consent Logs
        ConsentLog::create([
            'user_id' => $student1->id,
            'consent_type' => 'ai_data_processing',
            'is_granted' => true,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X)',
            'granted_at' => now()->subDays(10),
        ]);

        ConsentLog::create([
            'user_id' => $student2->id,
            'consent_type' => 'ai_data_processing',
            'is_granted' => true,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X)',
            'granted_at' => now()->subDays(12),
        ]);

        // 13. API Keys
        $keyData = ApiKey::generateKey();
        ApiKey::create([
            'user_id' => $admin->id,
            'institution_id' => $uni->id,
            'name' => 'LMS Integration Key (Moodle/Canvas)',
            'key_prefix' => $keyData['prefix'],
            'key_hash' => $keyData['hash'],
            'abilities' => ['read:grades', 'read:attendance', 'write:attendance'],
            'rate_limit' => 120,
            'is_active' => true,
        ]);
    }
}

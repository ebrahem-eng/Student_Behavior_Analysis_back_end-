<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Enrollment;
use App\Models\Section;
use App\Http\Resources\GradeResource;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Grade::with(['enrollment.student', 'enrollment.section.course']);

        if ($user && !$user->hasRole('admin')) {
            if ($user->hasRole('student')) {
                $query->whereHas('enrollment', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            } elseif ($user->hasRole('teacher')) {
                if ($request->boolean('my_sections')) {
                    $query->whereHas('enrollment.section', function ($q) use ($user) {
                        $q->where('teacher_id', $user->id);
                    });
                } elseif ($user->institution_id) {
                    $query->whereHas('enrollment.student', function ($q) use ($user) {
                        $q->where('institution_id', $user->institution_id);
                    });
                }
            } elseif ($user->hasRole('advisor') && $user->institution_id) {
                $query->whereHas('enrollment.student', function ($q) use ($user) {
                    $q->where('institution_id', $user->institution_id);
                });
            }
        }

        if ($request->filled('student_id')) {
            $query->whereHas('enrollment', function ($q) use ($request) {
                $q->where('user_id', $request->input('student_id'));
            });
        }

        if ($request->filled('course_id')) {
            $query->whereHas('enrollment.section', function ($q) use ($request) {
                $q->where('course_id', $request->input('course_id'));
            });
        }

        if ($request->filled('institution_id') && $request->input('institution_id') !== 'all') {
            $query->whereHas('enrollment.section.course', function ($q) use ($request) {
                $q->where('institution_id', $request->input('institution_id'));
            });
        }

        return GradeResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'enrollment_id' => 'nullable|exists:enrollments,id',
            'student_id' => 'nullable|exists:users,id',
            'course_id' => 'nullable|exists:courses,id',
            'section_id' => 'nullable|exists:sections,id',
            'exam_name' => 'required|string|max:255',
            'score' => 'required|numeric|min:0|max:100',
            'weight' => 'nullable|numeric|min:0|max:1',
        ]);

        $enrollmentId = $validated['enrollment_id'] ?? null;

        if (!$enrollmentId) {
            $studentId = $validated['student_id'] ?? null;
            $courseId = $validated['course_id'] ?? null;
            $sectionId = $validated['section_id'] ?? null;

            if ($studentId && ($courseId || $sectionId)) {
                if ($sectionId) {
                    $enrollment = Enrollment::firstOrCreate(
                        ['user_id' => $studentId, 'section_id' => $sectionId],
                        ['status' => 'enrolled']
                    );
                    $enrollmentId = $enrollment->id;
                } elseif ($courseId) {
                    $section = Section::where('course_id', $courseId)->first();
                    if (!$section) {
                        $section = Section::create([
                            'course_id' => $courseId,
                            'teacher_id' => $request->user()?->id,
                            'capacity' => 40
                        ]);
                    }
                    $enrollment = Enrollment::firstOrCreate(
                        ['user_id' => $studentId, 'section_id' => $section->id],
                        ['status' => 'enrolled']
                    );
                    $enrollmentId = $enrollment->id;
                }
            }
        }

        if (!$enrollmentId) {
            $enrollmentId = Enrollment::first()?->id ?? 1;
        }

        $grade = Grade::create([
            'enrollment_id' => $enrollmentId,
            'exam_name' => $validated['exam_name'],
            'score' => $validated['score'],
            'weight' => $validated['weight'] ?? 0.20,
        ]);

        return new GradeResource($grade->load(['enrollment.student', 'enrollment.section.course']));
    }

    public function show(Grade $grade)
    {
        return new GradeResource($grade->load(['enrollment.student', 'enrollment.section.course']));
    }

    public function update(Request $request, Grade $grade)
    {
        $validated = $request->validate([
            'exam_name' => 'sometimes|string|max:255',
            'score' => 'sometimes|numeric|min:0|max:100',
            'weight' => 'sometimes|numeric|min:0|max:1',
        ]);

        $grade->update($validated);
        return new GradeResource($grade->load(['enrollment.student', 'enrollment.section.course']));
    }

    public function destroy(Grade $grade)
    {
        $grade->delete();
        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Http\Resources\EnrollmentResource;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Enrollment::with(['student', 'section.course', 'section.teacher']);

        if ($user && !$user->hasRole('admin')) {
            if ($user->hasRole('student')) {
                $query->where('user_id', $user->id);
            } elseif ($user->hasRole('teacher')) {
                if ($request->boolean('my_sections')) {
                    $query->whereHas('section', function ($q) use ($user) {
                        $q->where('teacher_id', $user->id);
                    });
                } elseif ($user->institution_id) {
                    $query->whereHas('student', function ($q) use ($user) {
                        $q->where('institution_id', $user->institution_id);
                    });
                }
            } elseif ($user->hasRole('advisor') && $user->institution_id) {
                $query->whereHas('student', function ($q) use ($user) {
                    $q->where('institution_id', $user->institution_id);
                });
            }
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->input('section_id'));
        }

        if ($request->filled('student_id') || $request->filled('user_id')) {
            $query->where('user_id', $request->input('student_id', $request->input('user_id')));
        }

        return EnrollmentResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'section_id' => 'required|exists:sections,id',
            'status' => 'in:active,completed,dropped',
            'grade' => 'nullable|numeric|min:0|max:100',
        ]);

        $enrollment = Enrollment::create($validated);
        return new EnrollmentResource($enrollment->load(['student', 'section.course']));
    }

    public function show(Enrollment $enrollment)
    {
        return new EnrollmentResource($enrollment->load(['student', 'section.course']));
    }

    public function update(Request $request, Enrollment $enrollment)
    {
        $validated = $request->validate([
            'status' => 'in:active,completed,dropped',
            'grade' => 'nullable|numeric|min:0|max:100',
        ]);

        $enrollment->update($validated);
        return new EnrollmentResource($enrollment->load(['student', 'section.course']));
    }

    public function destroy(Enrollment $enrollment)
    {
        $enrollment->delete();
        return response()->noContent();
    }
    
    public function progress(Request $request, $studentId)
    {
        $enrollments = Enrollment::where('user_id', $studentId)
            ->where('status', 'completed')
            ->whereNotNull('grade')
            ->get();
            
        $totalCompleted = $enrollments->count();
        $averageGrade = $enrollments->avg('grade') ?? 0;
        
        return response()->json([
            'student_id' => $studentId,
            'completed_courses' => $totalCompleted,
            'average_grade' => round($averageGrade, 2),
            'graduation_progress_percent' => min(100, round(($totalCompleted / 40) * 100, 2))
        ]);
    }
}

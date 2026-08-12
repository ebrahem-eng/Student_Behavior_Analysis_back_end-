<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Http\Resources\EnrollmentResource;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function index()
    {
        return EnrollmentResource::collection(Enrollment::all());
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
        return new EnrollmentResource($enrollment);
    }

    public function show(Enrollment $enrollment)
    {
        return new EnrollmentResource($enrollment);
    }

    public function update(Request $request, Enrollment $enrollment)
    {
        $validated = $request->validate([
            'status' => 'in:active,completed,dropped',
            'grade' => 'nullable|numeric|min:0|max:100',
        ]);

        $enrollment->update($validated);
        return new EnrollmentResource($enrollment);
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
            
        // Simplified progress calculation
        $totalCompleted = $enrollments->count();
        $averageGrade = $enrollments->avg('grade') ?? 0;
        
        return response()->json([
            'student_id' => $studentId,
            'completed_courses' => $totalCompleted,
            'average_grade' => round($averageGrade, 2),
            // Assuming 40 courses for graduation in this basic calculation
            'graduation_progress_percent' => min(100, round(($totalCompleted / 40) * 100, 2))
        ]);
    }
}

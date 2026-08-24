<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Grade;
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
                // If teacher, scope to their taught sections or institution
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

        return GradeResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
            'exam_name' => 'required|string|max:255',
            'score' => 'required|numeric|min:0|max:100',
            'weight' => 'required|numeric|min:0|max:1',
        ]);

        $grade = Grade::create($validated);
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

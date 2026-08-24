<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Http\Resources\CourseResource;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Course::with(['institution', 'sections.teacher']);

        // Explicit filter by institution_id if provided
        if ($request->filled('institution_id')) {
            $query->where('institution_id', $request->input('institution_id'));
        } elseif ($user && $user->institution_id && !$user->hasRole('admin')) {
            // Scope by authenticated user's institution
            $query->where('institution_id', $user->institution_id);
        }

        // If teacher requested specifically their taught courses/sections
        if ($request->boolean('my_courses') && $user) {
            $query->whereHas('sections', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            });
        }

        return CourseResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'institution_id' => 'required|exists:institutions,id',
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'credits' => 'required|integer|min:0',
        ]);

        $course = Course::create($validated);
        return new CourseResource($course);
    }

    public function show(Course $course)
    {
        return new CourseResource($course->load(['institution', 'sections.teacher']));
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'institution_id' => 'sometimes|exists:institutions,id',
            'code' => 'sometimes|string|max:255',
            'name' => 'sometimes|string|max:255',
            'credits' => 'sometimes|integer|min:0',
        ]);

        $course->update($validated);
        return new CourseResource($course);
    }

    public function destroy(Course $course)
    {
        $course->delete();
        return response()->noContent();
    }
}

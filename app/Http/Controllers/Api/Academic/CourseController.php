<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Http\Resources\CourseResource;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        return CourseResource::collection(Course::all());
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
        return new CourseResource($course);
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'institution_id' => 'exists:institutions,id',
            'code' => 'string|max:255',
            'name' => 'string|max:255',
            'credits' => 'integer|min:0',
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

<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Http\Resources\GradeResource;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index()
    {
        return GradeResource::collection(Grade::all());
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
        return new GradeResource($grade);
    }

    public function show(Grade $grade)
    {
        return new GradeResource($grade);
    }

    public function update(Request $request, Grade $grade)
    {
        $validated = $request->validate([
            'exam_name' => 'string|max:255',
            'score' => 'numeric|min:0|max:100',
            'weight' => 'numeric|min:0|max:1',
        ]);

        $grade->update($validated);
        return new GradeResource($grade);
    }

    public function destroy(Grade $grade)
    {
        $grade->delete();
        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Http\Resources\CollegeResource;
use Illuminate\Http\Request;

class CollegeController extends Controller
{
    public function index(Request $request)
    {
        $query = College::with(['institution'])->withCount('users');

        if ($request->filled('institution_id')) {
            $query->where('institution_id', $request->input('institution_id'));
        }

        if ($request->filled('unit_type')) {
            $unitType = $request->input('unit_type');
            if ($unitType === 'stage') {
                $query->whereHas('institution', function ($q) {
                    $q->where('type', 'school');
                });
            } elseif ($unitType === 'college') {
                $query->whereHas('institution', function ($q) {
                    $q->where('type', 'university');
                });
            }
        }

        return CollegeResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'institution_id' => 'required|exists:institutions,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'dean_name' => 'nullable|string|max:255',
            'supervisor_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        if (empty($validated['dean_name']) && !empty($validated['supervisor_name'])) {
            $validated['dean_name'] = $validated['supervisor_name'];
        }

        $college = College::create([
            'institution_id' => $validated['institution_id'],
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'dean_name' => $validated['dean_name'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return new CollegeResource($college->load('institution'));
    }

    public function show(College $college)
    {
        return new CollegeResource($college->load('institution')->loadCount('users'));
    }

    public function update(Request $request, College $college)
    {
        $validated = $request->validate([
            'institution_id' => 'sometimes|exists:institutions,id',
            'name' => 'sometimes|string|max:255',
            'code' => 'nullable|string|max:50',
            'dean_name' => 'nullable|string|max:255',
            'supervisor_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        if (empty($validated['dean_name']) && !empty($validated['supervisor_name'])) {
            $validated['dean_name'] = $validated['supervisor_name'];
        }

        $college->update($validated);

        return new CollegeResource($college->load('institution'));
    }

    public function destroy(College $college)
    {
        $college->delete();
        return response()->noContent();
    }
}

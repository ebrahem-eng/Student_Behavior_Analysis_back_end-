<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Http\Resources\CollegeResource;
use App\Support\AdminScope;
use Illuminate\Http\Request;

class CollegeController extends Controller
{
    public function index(Request $request)
    {
        $query = College::with(['institution'])->withCount('users');
        $managedIds = AdminScope::managedIds($request->user());

        if (is_array($managedIds)) {
            if ($request->filled('institution_id')) {
                AdminScope::assert($request->user(), $request->input('institution_id'));
            }
            $query->whereIn('institution_id', $managedIds !== [] ? $managedIds : [0]);
        }

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

        AdminScope::assert($request->user(), $validated['institution_id']);

        $college = College::create([
            'institution_id' => $validated['institution_id'],
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'dean_name' => $validated['dean_name'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return new CollegeResource($college->load('institution'));
    }

    public function show(Request $request, College $college)
    {
        AdminScope::assert($request->user(), $college->institution_id);

        return new CollegeResource($college->load('institution')->loadCount('users'));
    }

    public function update(Request $request, College $college)
    {
        AdminScope::assert($request->user(), $college->institution_id);
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

        if (array_key_exists('institution_id', $validated)) {
            AdminScope::assert($request->user(), $validated['institution_id']);
        }

        $college->update($validated);

        return new CollegeResource($college->load('institution'));
    }

    public function destroy(Request $request, College $college)
    {
        AdminScope::assert($request->user(), $college->institution_id);

        $college->delete();
        return response()->noContent();
    }
}

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
        return CollegeResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'institution_id' => 'required|exists:institutions,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'dean_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $college = College::create($validated);

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
            'description' => 'nullable|string|max:1000',
        ]);

        $college->update($validated);

        return new CollegeResource($college->load('institution'));
    }

    public function destroy(College $college)
    {
        $college->delete();
        return response()->noContent();
    }
}

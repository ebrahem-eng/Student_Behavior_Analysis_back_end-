<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Http\Resources\InstitutionResource;
use Illuminate\Http\Request;

class InstitutionController extends Controller
{
    public function index()
    {
        return InstitutionResource::collection(Institution::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:school,university',
            'address' => 'nullable|string'
        ]);

        $institution = Institution::create($validated);

        return new InstitutionResource($institution);
    }

    public function show(Institution $institution)
    {
        return new InstitutionResource($institution);
    }

    public function update(Request $request, Institution $institution)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'type' => 'in:school,university',
            'address' => 'nullable|string'
        ]);

        $institution->update($validated);

        return new InstitutionResource($institution);
    }

    public function destroy(Institution $institution)
    {
        $institution->delete();
        return response()->noContent();
    }
}

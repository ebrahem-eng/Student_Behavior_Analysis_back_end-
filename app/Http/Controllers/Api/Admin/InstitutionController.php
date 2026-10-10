<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Http\Resources\InstitutionResource;
use App\Support\AdminScope;
use Illuminate\Http\Request;

class InstitutionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Institution::with(['colleges']);
        $managedIds = AdminScope::managedIds($user);

        if (is_array($managedIds)) {
            $query->whereIn('id', $managedIds !== [] ? $managedIds : [0]);
        } elseif ($request->boolean('my_affiliations') && $user) {
            $query->where(function ($q) use ($user) {
                if ($user->institution_id) {
                    $q->where('id', $user->institution_id);
                }
                $q->orWhereHas('courses.sections', function ($sq) use ($user) {
                    $sq->where('teacher_id', $user->id);
                });
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        return InstitutionResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:school,university',
            'address' => 'nullable|string'
        ]);

        $institution = Institution::create($validated);
        AdminScope::attachCreatedInstitution($request->user(), $institution->id);

        return new InstitutionResource($institution->load('colleges'));
    }

    public function show(Request $request, Institution $institution)
    {
        AdminScope::assert($request->user(), $institution->id);

        return new InstitutionResource($institution->load('colleges'));
    }

    public function update(Request $request, Institution $institution)
    {
        AdminScope::assert($request->user(), $institution->id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:school,university',
            'address' => 'nullable|string'
        ]);

        $institution->update($validated);

        return new InstitutionResource($institution->load('colleges'));
    }

    public function destroy(Request $request, Institution $institution)
    {
        AdminScope::assert($request->user(), $institution->id);

        $institution->delete();
        return response()->noContent();
    }
}

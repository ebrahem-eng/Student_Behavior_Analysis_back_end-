<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $authUser = $request->user();
        $query = User::with(['roles', 'institution', 'college']);

        if ($request->filled('role')) {
            $roleName = $request->input('role');
            $query->whereHas('roles', function ($q) use ($roleName) {
                $q->where('name', 'like', $roleName);
            });
        }

        // Institutional scoping for non-admin users
        if ($request->filled('institution_id')) {
            $query->where('institution_id', $request->input('institution_id'));
        } elseif ($authUser && $authUser->institution_id && !$authUser->hasRole('admin')) {
            $query->where('institution_id', $authUser->institution_id);
        }

        if ($request->filled('college_id')) {
            $query->where('college_id', $request->input('college_id'));
        } elseif ($request->filled('stage_id')) {
            $query->where('college_id', $request->input('stage_id'));
        }

        if ($request->filled('institution_type')) {
            $type = $request->input('institution_type');
            $query->whereHas('institution', function ($q) use ($type) {
                $q->where('type', $type);
            });
        }

        return UserResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $collegeId = $request->input('college_id') ?: $request->input('stage_id');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', Rules\Password::defaults()],
            'role' => 'required|string|exists:roles,name',
            'institution_id' => 'nullable|exists:institutions,id',
            'college_id' => 'nullable|exists:colleges,id',
            'stage_id' => 'nullable|exists:colleges,id',
            'phone' => 'nullable|string|max:50',
            'national_id' => 'nullable|string|max:50',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'institution_id' => $validated['institution_id'] ?? null,
            'college_id' => $collegeId ?? null,
            'phone' => $validated['phone'] ?? null,
            'national_id' => $validated['national_id'] ?? null,
        ]);

        $user->assignRole($validated['role']);

        return new UserResource($user->load(['roles', 'institution', 'college']));
    }

    public function show(User $user)
    {
        return new UserResource($user->load(['roles', 'institution', 'college']));
    }

    public function update(Request $request, User $user)
    {
        $collegeId = $request->has('college_id') ? $request->input('college_id') : ($request->has('stage_id') ? $request->input('stage_id') : $user->college_id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,'.$user->id,
            'role' => 'sometimes|string|exists:roles,name',
            'institution_id' => 'nullable|exists:institutions,id',
            'college_id' => 'nullable|exists:colleges,id',
            'stage_id' => 'nullable|exists:colleges,id',
            'phone' => 'nullable|string|max:50',
            'national_id' => 'nullable|string|max:50',
        ]);

        $updateData = $request->only(['name', 'email', 'institution_id', 'phone', 'national_id']);
        if ($request->has('college_id') || $request->has('stage_id')) {
            $updateData['college_id'] = $collegeId;
        }

        $user->update($updateData);

        if ($request->has('role')) {
            $user->syncRoles([$validated['role']]);
        }

        return new UserResource($user->load(['roles', 'institution', 'college']));
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Support\AdminScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $authUser = $request->user();
        $query = User::with(['roles', 'institution', 'college', 'managedInstitutions']);

        if ($request->filled('role')) {
            $roleName = $request->input('role');
            $query->whereHas('roles', function ($q) use ($roleName) {
                $q->whereRaw('LOWER(name) = ?', [strtolower($roleName)]);
            });
        }

        if ($request->filled('course_id')) {
            $courseId = $request->input('course_id');
            $query->whereHas('enrollments.section', function ($q) use ($courseId) {
                $q->where('course_id', $courseId);
            });
        }

        $managedIds = AdminScope::managedIds($authUser);

        if (is_array($managedIds)) {
            $scope = $managedIds !== [] ? $managedIds : [0];

            if ($request->filled('institution_id') && $request->input('institution_id') !== 'all') {
                AdminScope::assert($authUser, $request->input('institution_id'));
                $query->where('institution_id', $request->input('institution_id'));
            } else {
                $query->where(function ($q) use ($scope, $authUser) {
                    $q->whereIn('institution_id', $scope)
                        ->orWhere('id', $authUser->id)
                        ->orWhere(function ($adminQuery) use ($scope) {
                            $adminQuery->whereHas('managedInstitutions', function ($mq) use ($scope) {
                                $mq->whereIn('institutions.id', $scope);
                            })->whereDoesntHave('managedInstitutions', function ($mq) use ($scope) {
                                $mq->whereNotIn('institutions.id', $scope);
                            });
                        });
                });
            }
        } elseif ($request->filled('institution_id') && $request->input('institution_id') !== 'all') {
            $query->where('institution_id', $request->input('institution_id'));
        } elseif ($authUser && $authUser->institution_id && !$request->boolean('all_institutions')) {
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
        $this->normalizeRole($request);
        $collegeId = $request->input('college_id') ?: $request->input('stage_id');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', Rules\Password::defaults()],
            'role' => 'required|string|exists:roles,name',
            'institution_id' => 'nullable|exists:institutions,id',
            'institution_ids' => 'nullable|array',
            'institution_ids.*' => 'integer|exists:institutions,id',
            'college_id' => 'nullable|exists:colleges,id',
            'stage_id' => 'nullable|exists:colleges,id',
            'phone' => 'nullable|string|max:50',
            'national_id' => 'nullable|string|max:50',
        ]);

        $actor = $request->user();
        $isAdminRole = strcasecmp($validated['role'], 'admin') === 0;
        $assignedIds = $this->assignedInstitutionIds($request, $validated);

        if ($isAdminRole && $assignedIds === []) {
            throw ValidationException::withMessages([
                'institution_ids' => ['An administrator must be assigned at least one institution.'],
            ]);
        }

        if ($actor && $actor->isAdmin()) {
            if ($isAdminRole) {
                $this->assertAssignmentWithinScope($actor, $assignedIds);
            } elseif (!empty($validated['institution_id'])) {
                AdminScope::assert($actor, $validated['institution_id']);
            }

            $this->assertCollegeInScope($actor, $collegeId, $validated['institution_id'] ?? ($assignedIds[0] ?? null));
        }

        $institutionId = $validated['institution_id'] ?? ($assignedIds[0] ?? null);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'institution_id' => $institutionId,
            'college_id' => $collegeId ?? null,
            'phone' => $validated['phone'] ?? null,
            'national_id' => $validated['national_id'] ?? null,
        ]);

        $user->assignRole($validated['role']);

        if ($isAdminRole) {
            $user->managedInstitutions()->sync($assignedIds);
            if ((int) $user->institution_id !== (int) $assignedIds[0]) {
                $user->update(['institution_id' => $assignedIds[0]]);
            }
        }

        return new UserResource($user->load(['roles', 'institution', 'college', 'managedInstitutions']));
    }

    public function show(Request $request, User $user)
    {
        $this->ensureUserInScope($request->user(), $user);

        return new UserResource($user->load(['roles', 'institution', 'college', 'managedInstitutions']));
    }

    public function update(Request $request, User $user)
    {
        $this->ensureUserInScope($request->user(), $user);
        $this->normalizeRole($request);

        $collegeId = $request->has('college_id') ? $request->input('college_id') : ($request->has('stage_id') ? $request->input('stage_id') : $user->college_id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,'.$user->id,
            'role' => 'sometimes|string|exists:roles,name',
            'institution_id' => 'nullable|exists:institutions,id',
            'institution_ids' => 'nullable|array',
            'institution_ids.*' => 'integer|exists:institutions,id',
            'college_id' => 'nullable|exists:colleges,id',
            'stage_id' => 'nullable|exists:colleges,id',
            'phone' => 'nullable|string|max:50',
            'national_id' => 'nullable|string|max:50',
        ]);

        $actor = $request->user();
        $roleName = $validated['role'] ?? ($user->roles()->first()->name ?? '');
        $isAdminRole = strcasecmp((string) $roleName, 'admin') === 0;

        if ($actor && $actor->isAdmin()) {
            if (array_key_exists('institution_id', $validated) && !empty($validated['institution_id'])) {
                AdminScope::assert($actor, $validated['institution_id']);
            }
            if ($isAdminRole && ($request->has('institution_ids') || $request->has('role') || $request->has('institution_id'))) {
                $assignedIds = $this->assignedInstitutionIds($request, array_merge(
                    ['institution_id' => $user->institution_id],
                    $validated
                ));
                if ($request->has('institution_ids') || $request->has('institution_id') || $request->has('role')) {
                    $this->assertAssignmentWithinScope($actor, $assignedIds);
                }
            }
            $this->assertCollegeInScope($actor, $collegeId, $validated['institution_id'] ?? $user->institution_id);
        }

        $updateData = $request->only(['name', 'email', 'institution_id', 'phone', 'national_id']);
        if ($request->has('college_id') || $request->has('stage_id')) {
            $updateData['college_id'] = $collegeId;
        }

        $user->update($updateData);

        if ($request->has('role')) {
            $user->syncRoles([$validated['role']]);
        }

        if ($isAdminRole && ($request->has('institution_ids') || $request->has('institution_id') || $request->has('role'))) {
            $assignedIds = $this->assignedInstitutionIds($request, array_merge(
                ['institution_id' => $user->institution_id],
                $validated
            ));
            if ($assignedIds === []) {
                throw ValidationException::withMessages([
                    'institution_ids' => ['An administrator must be assigned at least one institution.'],
                ]);
            }
            $user->managedInstitutions()->sync($assignedIds);
            if (!in_array((int) $user->institution_id, $assignedIds, true)) {
                $user->update(['institution_id' => $assignedIds[0]]);
            }
        } elseif ($request->has('role') && !$isAdminRole) {
            $user->managedInstitutions()->detach();
        }

        return new UserResource($user->load(['roles', 'institution', 'college', 'managedInstitutions']));
    }

    public function destroy(Request $request, User $user)
    {
        $this->ensureUserInScope($request->user(), $user);

        $user->delete();
        return response()->noContent();
    }

    private function normalizeRole(Request $request): void
    {
        if (!$request->filled('role')) {
            return;
        }

        $canonical = Role::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($request->input('role'))])
            ->value('name');

        if ($canonical) {
            $request->merge(['role' => $canonical]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int>
     */
    private function assignedInstitutionIds(Request $request, array $validated): array
    {
        $ids = $request->input('institution_ids', []);
        if (!is_array($ids)) {
            $ids = [];
        }

        $ids = array_values(array_unique(array_map('intval', array_filter($ids, fn ($id) => $id !== null && $id !== ''))));

        if ($ids === [] && !empty($validated['institution_id'])) {
            $ids = [(int) $validated['institution_id']];
        }

        return $ids;
    }

    /**
     * @param  array<int>  $institutionIds
     */
    private function assertAssignmentWithinScope(User $actor, array $institutionIds): void
    {
        if ($institutionIds === []) {
            throw ValidationException::withMessages([
                'institution_ids' => ['An administrator must be assigned at least one institution.'],
            ]);
        }

        $allowed = $actor->managedInstitutionIds();
        foreach ($institutionIds as $institutionId) {
            if (!in_array($institutionId, $allowed, true)) {
                abort(403, 'You can only assign institutions that are within your own scope.');
            }
        }
    }

    private function assertCollegeInScope(?User $actor, mixed $collegeId, mixed $institutionId): void
    {
        if (!$actor || !$actor->isAdmin() || empty($collegeId)) {
            return;
        }

        $college = College::find($collegeId);
        if (!$college) {
            return;
        }

        AdminScope::assert($actor, $college->institution_id);

        if (!empty($institutionId) && (int) $college->institution_id !== (int) $institutionId) {
            throw ValidationException::withMessages([
                'college_id' => ['The selected college does not belong to the chosen institution.'],
            ]);
        }
    }

    private function ensureUserInScope(?User $actor, User $target): void
    {
        if (!$actor || !$actor->isAdmin() || (int) $actor->id === (int) $target->id) {
            return;
        }

        $allowed = $actor->managedInstitutionIds();
        if ($target->isAdmin()) {
            $targetIds = $target->managedInstitutionIds();
            if ($targetIds === [] || array_diff($targetIds, $allowed) !== []) {
                abort(403, 'You can only manage accounts inside your assigned institutions.');
            }
            return;
        }

        if (!$target->institution_id || !in_array((int) $target->institution_id, $allowed, true)) {
            abort(403, 'You can only manage accounts inside your assigned institutions.');
        }
    }
}

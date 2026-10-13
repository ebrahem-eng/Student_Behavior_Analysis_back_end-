<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Http\Resources\ApiKeyResource;
use App\Support\AdminScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiKeyController extends Controller
{
    /**
     * List all API keys.
     */
    public function index(Request $request)
    {
        $query = ApiKey::with(['user', 'institution'])->latest();
        $managedIds = AdminScope::managedIds($request->user());

        if (is_array($managedIds)) {
            $query->whereIn('institution_id', $managedIds !== [] ? $managedIds : [0]);
        }

        return ApiKeyResource::collection($query->get());
    }

    /**
     * Generate a new API key.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'institution_id' => 'nullable|exists:institutions,id',
            'abilities' => 'nullable|array',
            'rate_limit' => 'nullable|integer|min:1|max:1000',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $institutionId = $validated['institution_id'] ?? Auth::user()->institution_id;
        if (Auth::user()?->isAdmin() && !$institutionId) {
            $institutionId = Auth::user()->managedInstitutionIds()[0] ?? null;
        }
        AdminScope::assert(Auth::user(), $institutionId);

        $keyData = ApiKey::generateKey();

        $apiKey = ApiKey::create([
            'user_id' => Auth::id(),
            'institution_id' => $institutionId,
            'name' => $validated['name'],
            'key_prefix' => $keyData['prefix'],
            'key_hash' => $keyData['hash'],
            'abilities' => $validated['abilities'] ?? ['*'],
            'rate_limit' => $validated['rate_limit'] ?? 60,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => true,
        ]);

        // Attach plain key for one-time display upon creation
        $apiKey->plain_key = $keyData['plain'];

        return (new ApiKeyResource($apiKey))
            ->additional(['message' => 'Make sure to copy your API key now as you will not be able to see it again.']);
    }

    /**
     * Show API key metadata.
     */
    public function show(Request $request, ApiKey $apiKey)
    {
        AdminScope::assert($request->user(), $apiKey->institution_id);

        return new ApiKeyResource($apiKey);
    }

    /**
     * Toggle active status of an API key.
     */
    public function toggle(Request $request, ApiKey $apiKey)
    {
        AdminScope::assert($request->user(), $apiKey->institution_id);

        $apiKey->update(['is_active' => !$apiKey->is_active]);
        return new ApiKeyResource($apiKey);
    }

    /**
     * Revoke / delete an API key.
     */
    public function destroy(Request $request, ApiKey $apiKey)
    {
        AdminScope::assert($request->user(), $apiKey->institution_id);

        $apiKey->delete();
        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Http\Resources\ApiKeyResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiKeyController extends Controller
{
    /**
     * List all API keys.
     */
    public function index(Request $request)
    {
        $keys = ApiKey::with(['user', 'institution'])->latest()->get();
        return ApiKeyResource::collection($keys);
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

        $keyData = ApiKey::generateKey();

        $apiKey = ApiKey::create([
            'user_id' => Auth::id(),
            'institution_id' => $validated['institution_id'] ?? Auth::user()->institution_id,
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
    public function show(ApiKey $apiKey)
    {
        return new ApiKeyResource($apiKey);
    }

    /**
     * Toggle active status of an API key.
     */
    public function toggle(Request $request, ApiKey $apiKey)
    {
        $apiKey->update(['is_active' => !$apiKey->is_active]);
        return new ApiKeyResource($apiKey);
    }

    /**
     * Revoke / delete an API key.
     */
    public function destroy(ApiKey $apiKey)
    {
        $apiKey->delete();
        return response()->noContent();
    }
}

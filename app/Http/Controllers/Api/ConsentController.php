<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConsentLog;
use App\Http\Resources\ConsentLogResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConsentController extends Controller
{
    /**
     * List current user's consent history.
     */
    public function index(Request $request)
    {
        $consents = ConsentLog::where('user_id', Auth::id())->latest()->get();
        return ConsentLogResource::collection($consents);
    }

    /**
     * Grant or update consent.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'consent_type' => 'required|string|max:100',
            'is_granted' => 'required|boolean',
        ]);

        $consent = ConsentLog::create([
            'user_id' => Auth::id(),
            'consent_type' => $validated['consent_type'],
            'is_granted' => $validated['is_granted'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'granted_at' => $validated['is_granted'] ? now() : null,
            'revoked_at' => !$validated['is_granted'] ? now() : null,
        ]);

        return new ConsentLogResource($consent);
    }

    /**
     * Revoke a specific consent.
     */
    public function revoke(Request $request)
    {
        $validated = $request->validate([
            'consent_type' => 'required|string|max:100',
        ]);

        $consent = ConsentLog::create([
            'user_id' => Auth::id(),
            'consent_type' => $validated['consent_type'],
            'is_granted' => false,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'granted_at' => null,
            'revoked_at' => now(),
        ]);

        return new ConsentLogResource($consent);
    }

    /**
     * Check if user currently has active consent for a specific type.
     */
    public function check(Request $request, string $type)
    {
        $latest = ConsentLog::where('user_id', Auth::id())
            ->where('consent_type', $type)
            ->latest()
            ->first();

        return response()->json([
            'consent_type' => $type,
            'is_granted' => $latest ? (bool)$latest->is_granted : false,
            'last_updated' => $latest ? $latest->updated_at : null,
        ]);
    }
}

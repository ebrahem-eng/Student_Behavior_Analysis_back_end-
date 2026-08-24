<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\BehaviorLog;
use App\Http\Resources\BehaviorLogResource;
use Illuminate\Http\Request;

class BehaviorLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = BehaviorLog::with(['student', 'reporter']);

        if ($user && !$user->hasRole('admin')) {
            if ($user->hasRole('student')) {
                $query->where('user_id', $user->id);
            } elseif ($user->hasRole('teacher')) {
                if ($request->boolean('my_reports')) {
                    $query->where('reporter_id', $user->id);
                } elseif ($user->institution_id) {
                    $query->whereHas('student', function ($q) use ($user) {
                        $q->where('institution_id', $user->institution_id);
                    });
                }
            } elseif ($user->hasRole('advisor') && $user->institution_id) {
                $query->whereHas('student', function ($q) use ($user) {
                    $q->where('institution_id', $user->institution_id);
                });
            }
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        return BehaviorLogResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'reporter_id' => 'nullable|exists:users,id',
            'type' => 'required|in:positive,negative,warning',
            'description' => 'required|string',
            'date' => 'required|date',
        ]);

        if (empty($validated['reporter_id'])) {
            $validated['reporter_id'] = $request->user()?->id;
        }

        $log = BehaviorLog::create($validated);
        return new BehaviorLogResource($log->load(['student', 'reporter']));
    }

    public function show(BehaviorLog $behaviorLog)
    {
        return new BehaviorLogResource($behaviorLog->load(['student', 'reporter']));
    }

    public function update(Request $request, BehaviorLog $behaviorLog)
    {
        $validated = $request->validate([
            'type' => 'sometimes|in:positive,negative,warning',
            'description' => 'sometimes|string',
            'date' => 'sometimes|date',
        ]);

        $behaviorLog->update($validated);
        return new BehaviorLogResource($behaviorLog->load(['student', 'reporter']));
    }

    public function destroy(BehaviorLog $behaviorLog)
    {
        $behaviorLog->delete();
        return response()->noContent();
    }
}

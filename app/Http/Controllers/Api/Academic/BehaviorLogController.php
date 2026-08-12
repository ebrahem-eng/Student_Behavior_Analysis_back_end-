<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\BehaviorLog;
use App\Http\Resources\BehaviorLogResource;
use Illuminate\Http\Request;

class BehaviorLogController extends Controller
{
    public function index()
    {
        return BehaviorLogResource::collection(BehaviorLog::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'reporter_id' => 'required|exists:users,id',
            'type' => 'required|in:positive,negative,warning',
            'description' => 'required|string',
            'date' => 'required|date',
        ]);

        $log = BehaviorLog::create($validated);
        return new BehaviorLogResource($log);
    }

    public function show(BehaviorLog $behaviorLog)
    {
        return new BehaviorLogResource($behaviorLog);
    }

    public function update(Request $request, BehaviorLog $behaviorLog)
    {
        $validated = $request->validate([
            'type' => 'in:positive,negative,warning',
            'description' => 'string',
            'date' => 'date',
        ]);

        $behaviorLog->update($validated);
        return new BehaviorLogResource($behaviorLog);
    }

    public function destroy(BehaviorLog $behaviorLog)
    {
        $behaviorLog->delete();
        return response()->noContent();
    }
}

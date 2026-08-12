<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Recommendation;
use App\Http\Resources\RecommendationResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecommendationController extends Controller
{
    public function index()
    {
        return RecommendationResource::collection(Recommendation::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'ai_suggested_action' => 'required|string',
            'advisor_id' => 'nullable|exists:users,id',
            'teacher_id' => 'nullable|exists:users,id',
            'status' => 'in:proposed,approved,rejected,implemented',
        ]);

        $recommendation = Recommendation::create($validated);
        return new RecommendationResource($recommendation);
    }

    public function show(Recommendation $recommendation)
    {
        return new RecommendationResource($recommendation);
    }

    public function update(Request $request, Recommendation $recommendation)
    {
        $validated = $request->validate([
            'status' => 'in:proposed,approved,rejected,implemented',
            'advisor_id' => 'exists:users,id',
            'teacher_id' => 'exists:users,id',
            'outcome_notes' => 'nullable|string',
        ]);

        $recommendation->update($validated);
        return new RecommendationResource($recommendation);
    }

    public function destroy(Recommendation $recommendation)
    {
        $recommendation->delete();
        return response()->noContent();
    }
    
    public function approve(Request $request, Recommendation $recommendation)
    {
        $recommendation->update([
            'status' => 'approved',
            'advisor_id' => Auth::id()
        ]);
        return new RecommendationResource($recommendation);
    }
    
    public function logImplementation(Request $request, Recommendation $recommendation)
    {
        $validated = $request->validate([
            'outcome_notes' => 'required|string',
        ]);
        
        $recommendation->update([
            'status' => 'implemented',
            'outcome_notes' => $validated['outcome_notes'],
            'teacher_id' => Auth::id()
        ]);
        
        return new RecommendationResource($recommendation);
    }
}

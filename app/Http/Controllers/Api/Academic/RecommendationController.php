<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Recommendation;
use App\Http\Resources\RecommendationResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecommendationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Recommendation::with(['student', 'advisor', 'teacher'])->latest();

        if ($user && !$user->hasRole('admin')) {
            if ($user->hasRole('student')) {
                $query->where('student_id', $user->id);
            } elseif ($user->hasRole('teacher')) {
                if ($user->institution_id) {
                    $query->where(function ($q) use ($user) {
                        $q->where('teacher_id', $user->id)
                          ->orWhereHas('student', function ($sq) use ($user) {
                              $sq->where('institution_id', $user->institution_id);
                          });
                    });
                } else {
                    $query->where('teacher_id', $user->id);
                }
            } elseif ($user->hasRole('advisor') && $user->institution_id) {
                $query->whereHas('student', function ($q) use ($user) {
                    $q->where('institution_id', $user->institution_id);
                });
            }
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return RecommendationResource::collection($query->get());
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

        if (empty($validated['teacher_id']) && $request->user()?->hasRole('teacher')) {
            $validated['teacher_id'] = $request->user()->id;
        }

        $recommendation = Recommendation::create($validated);
        return new RecommendationResource($recommendation->load(['student', 'advisor', 'teacher']));
    }

    public function show(Recommendation $recommendation)
    {
        return new RecommendationResource($recommendation->load(['student', 'advisor', 'teacher']));
    }

    public function update(Request $request, Recommendation $recommendation)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:proposed,approved,rejected,implemented',
            'advisor_id' => 'nullable|exists:users,id',
            'teacher_id' => 'nullable|exists:users,id',
            'ai_suggested_action' => 'sometimes|string',
            'outcome_notes' => 'nullable|string',
        ]);

        $recommendation->update($validated);
        return new RecommendationResource($recommendation->load(['student', 'advisor', 'teacher']));
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
        return new RecommendationResource($recommendation->load(['student', 'advisor', 'teacher']));
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
        
        return new RecommendationResource($recommendation->load(['student', 'advisor', 'teacher']));
    }
}

<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Services\Prediction\PredictionServiceClient;
use Illuminate\Http\Request;

class ProjectionController extends Controller
{
    protected $client;

    public function __construct(PredictionServiceClient $client)
    {
        $this->client = $client;
    }

    public function projectPerformance(Request $request, int $studentId)
    {
        $validated = $request->validate([
            'attendance_rate' => 'required|numeric',
            'average_grade' => 'required|numeric',
            'behavior_score' => 'required|numeric',
            'participation_rate' => 'required|numeric',
            'months_ahead' => 'integer|min:1|max:12',
        ]);
        
        $monthsAhead = $request->input('months_ahead', 6);
        $features = [
            'attendance_rate' => $validated['attendance_rate'],
            'average_grade' => $validated['average_grade'],
            'behavior_score' => $validated['behavior_score'],
            'participation_rate' => $validated['participation_rate'],
        ];

        $projection = $this->client->projectPerformance($studentId, $features, $monthsAhead);

        if (!$projection) {
            return response()->json(['error' => 'Failed to retrieve projection from ML service.'], 500);
        }

        return response()->json($projection);
    }
    
    public function metrics()
    {
        $metrics = $this->client->getMetrics();
        
        if (!$metrics) {
            return response()->json(['error' => 'Failed to retrieve metrics from ML service.'], 500);
        }

        return response()->json($metrics);
    }
}

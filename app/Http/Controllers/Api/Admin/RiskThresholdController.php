<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\RiskThreshold;
use App\Http\Resources\RiskThresholdResource;
use Illuminate\Http\Request;

class RiskThresholdController extends Controller
{
    public function index()
    {
        return RiskThresholdResource::collection(RiskThreshold::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'institution_id' => 'required|exists:institutions,id',
            'level' => 'required|in:low,medium,high',
            'min_score' => 'required|numeric|min:0|max:100',
            'max_score' => 'required|numeric|min:0|max:100',
            'requires_action' => 'boolean',
        ]);

        $threshold = RiskThreshold::create($validated);
        return new RiskThresholdResource($threshold);
    }

    public function show(RiskThreshold $riskThreshold)
    {
        return new RiskThresholdResource($riskThreshold);
    }

    public function update(Request $request, RiskThreshold $riskThreshold)
    {
        $validated = $request->validate([
            'level' => 'in:low,medium,high',
            'min_score' => 'numeric|min:0|max:100',
            'max_score' => 'numeric|min:0|max:100',
            'requires_action' => 'boolean',
        ]);

        $riskThreshold->update($validated);
        return new RiskThresholdResource($riskThreshold);
    }

    public function destroy(RiskThreshold $riskThreshold)
    {
        $riskThreshold->delete();
        return response()->noContent();
    }
}

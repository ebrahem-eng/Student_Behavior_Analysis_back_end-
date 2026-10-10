<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\RiskThreshold;
use App\Http\Resources\RiskThresholdResource;
use App\Support\AdminScope;
use Illuminate\Http\Request;

class RiskThresholdController extends Controller
{
    public function index(Request $request)
    {
        $query = RiskThreshold::query();
        $managedIds = AdminScope::managedIds($request->user());

        if (is_array($managedIds)) {
            $query->whereIn('institution_id', $managedIds !== [] ? $managedIds : [0]);
        }

        return RiskThresholdResource::collection($query->get());
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

        AdminScope::assert($request->user(), $validated['institution_id']);

        $threshold = RiskThreshold::create($validated);
        return new RiskThresholdResource($threshold);
    }

    public function show(Request $request, RiskThreshold $riskThreshold)
    {
        AdminScope::assert($request->user(), $riskThreshold->institution_id);

        return new RiskThresholdResource($riskThreshold);
    }

    public function update(Request $request, RiskThreshold $riskThreshold)
    {
        AdminScope::assert($request->user(), $riskThreshold->institution_id);
        $validated = $request->validate([
            'level' => 'in:low,medium,high',
            'min_score' => 'numeric|min:0|max:100',
            'max_score' => 'numeric|min:0|max:100',
            'requires_action' => 'boolean',
        ]);

        $riskThreshold->update($validated);
        return new RiskThresholdResource($riskThreshold);
    }

    public function destroy(Request $request, RiskThreshold $riskThreshold)
    {
        AdminScope::assert($request->user(), $riskThreshold->institution_id);

        $riskThreshold->delete();
        return response()->noContent();
    }
}

<?php

namespace App\Services\Alert;

use App\Models\RiskThreshold;
use App\Models\Alert;
use App\Models\User;

class RiskEngineService
{
    /**
     * Process a risk score from the ML model and generate alerts based on thresholds.
     */
    public function processRiskScore(User $student, float $score, array $factors = [])
    {
        $threshold = RiskThreshold::where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->first();

        if (!$threshold) {
            return null; // No alert needed
        }

        $message = "Student {$student->name} is at {$threshold->level} risk (Score: {$score}).";
        if (!empty($factors)) {
            $message .= " Key factors: " . implode(', ', $factors);
        }

        // Alert the first Admin for simplicity. In production, this would query specific advisors/parents based on relationships.
        $admin = User::role('Admin')->first();
        
        if ($admin) {
            return Alert::create([
                'student_id' => $student->id,
                'recipient_id' => $admin->id,
                'level' => $threshold->level,
                'message' => $message,
            ]);
        }
        
        return null;
    }
}

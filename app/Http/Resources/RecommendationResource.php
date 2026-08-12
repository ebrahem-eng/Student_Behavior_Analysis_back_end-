<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecommendationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'ai_suggested_action' => $this->ai_suggested_action,
            'advisor_id' => $this->advisor_id,
            'teacher_id' => $this->teacher_id,
            'status' => $this->status,
            'outcome_notes' => $this->outcome_notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

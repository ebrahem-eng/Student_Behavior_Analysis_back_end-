<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollegeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $instType = $this->institution?->type ?? ($this->relationLoaded('institution') && $this->institution ? $this->institution->type : 'university');
        $isSchool = $instType === 'school';

        return [
            'id' => $this->id,
            'institution_id' => $this->institution_id,
            'institution_type' => $instType,
            'unit_type' => $isSchool ? 'stage' : 'college',
            'unit_label_ar' => $isSchool ? 'المرحلة الدراسية' : 'الكلية الجامعية',
            'unit_label_en' => $isSchool ? 'Educational Stage' : 'College / Faculty',
            'head_title_ar' => $isSchool ? 'مشرف المرحلة / مدير القسم' : 'عميد الكلية',
            'head_title_en' => $isSchool ? 'Stage Supervisor / Principal' : 'Dean of Faculty',
            'name' => $this->name,
            'code' => $this->code,
            'dean_name' => $this->dean_name,
            'supervisor_name' => $this->dean_name,
            'description' => $this->description,
            'users_count' => $this->whenCounted('users') ?? $this->users()->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

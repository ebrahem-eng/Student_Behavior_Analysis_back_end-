<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstitutionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isSchool = $this->type === 'school';

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'is_school' => $isSchool,
            'is_university' => !$isSchool,
            'sub_units_type' => $isSchool ? 'stages' : 'colleges',
            'sub_units_label_ar' => $isSchool ? 'المراحل والصفوف الدراسية' : 'الكليات والأقسام الأكاديمية',
            'sub_units_label_en' => $isSchool ? 'Educational Stages & Grade Tracks' : 'Academic Colleges & Faculties',
            'address' => $this->address,
            'colleges' => CollegeResource::collection($this->whenLoaded('colleges')),
            'stages' => CollegeResource::collection($this->whenLoaded('colleges')),
            'colleges_count' => $this->colleges()->count(),
            'stages_count' => $this->colleges()->count(),
            'users_count' => $this->users()->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

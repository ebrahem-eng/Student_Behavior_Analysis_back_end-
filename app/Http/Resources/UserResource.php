<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $instType = $this->institution?->type ?? 'university';
        $isSchool = $instType === 'school';

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'institution_id' => $this->institution_id,
            'institution' => new InstitutionResource($this->whenLoaded('institution')),
            'college_id' => $this->college_id,
            'college' => new CollegeResource($this->whenLoaded('college')),
            'stage_id' => $this->college_id,
            'stage' => new CollegeResource($this->whenLoaded('college')),
            'unit_type' => $isSchool ? 'stage' : 'college',
            'sub_unit_name' => $this->college?->name,
            'sub_unit_code' => $this->college?->code,
            'is_school_member' => $isSchool,
            'is_university_member' => !$isSchool,
            'phone' => $this->phone,
            'national_id' => $this->national_id,
            'avatar_url' => $this->avatar_url,
            'roles' => $this->relationLoaded('roles') 
                ? $this->roles->pluck('name') 
                : $this->getRoleNames(),
            'role' => $this->relationLoaded('roles') && $this->roles->isNotEmpty()
                ? strtolower($this->roles->first()->name)
                : (strtolower($this->getRoleNames()->first() ?? 'admin')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

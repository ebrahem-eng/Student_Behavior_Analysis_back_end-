<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsentLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'consent_type' => $this->consent_type,
            'is_granted' => $this->is_granted,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'granted_at' => $this->granted_at,
            'revoked_at' => $this->revoked_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

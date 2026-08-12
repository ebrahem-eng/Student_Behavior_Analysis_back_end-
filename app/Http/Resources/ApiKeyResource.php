<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiKeyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'institution_id' => $this->institution_id,
            'name' => $this->name,
            'key_prefix' => $this->key_prefix,
            'plain_key' => $this->when(isset($this->plain_key), $this->plain_key),
            'abilities' => $this->abilities,
            'rate_limit' => $this->rate_limit,
            'last_used_at' => $this->last_used_at,
            'expires_at' => $this->expires_at,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

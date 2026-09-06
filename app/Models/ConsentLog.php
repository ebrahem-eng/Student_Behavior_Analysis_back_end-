<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConsentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'consent_type',
        'is_granted',
        'ip_address',
        'user_agent',
        'granted_at',
        'revoked_at',
    ];

    protected $casts = [
        'is_granted' => 'boolean',
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

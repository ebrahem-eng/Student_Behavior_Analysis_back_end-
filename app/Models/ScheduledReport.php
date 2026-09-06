<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScheduledReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'report_type',
        'frequency',
        'recipients',
        'filters',
        'is_active',
        'last_run_at',
    ];

    protected $casts = [
        'recipients' => 'array',
        'filters' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

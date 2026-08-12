<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'institution_id',
        'name',
        'key_prefix',
        'key_hash',
        'abilities',
        'rate_limit',
        'last_used_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'key_prefix', 'abilities', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public static function generateKey(): array
    {
        $plainSecret = 'sba_' . Str::random(40);
        $prefix = substr($plainSecret, 0, 8);
        $hash = hash('sha256', $plainSecret);

        return [
            'plain' => $plainSecret,
            'prefix' => $prefix,
            'hash' => $hash,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }
}

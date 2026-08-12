<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class BehaviorLog extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['user_id', 'reporter_id', 'type', 'description', 'date'];

    protected $casts = [
        'date' => 'date',
        'description' => 'encrypted',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id', 'reporter_id', 'type', 'date'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}

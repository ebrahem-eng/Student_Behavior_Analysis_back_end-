<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'institution_id',
        'phone',
        'national_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'phone' => 'encrypted',
            'national_id' => 'encrypted',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'institution_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function behaviorLogs()
    {
        return $this->hasMany(BehaviorLog::class, 'user_id');
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class, 'student_id');
    }
}

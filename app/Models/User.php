<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'institution_id',
        'college_id',
        'phone',
        'national_id',
        'avatar_url',
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
            ->logOnly(['name', 'email', 'institution_id', 'college_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function managedInstitutions()
    {
        return $this->belongsToMany(Institution::class, 'institution_admins')->withTimestamps();
    }

    public function isAdmin(): bool
    {
        $this->loadMissing('roles');

        return $this->roles->contains(fn ($role) => strcasecmp($role->name, 'admin') === 0);
    }

    /**
     * Institutions this admin may manage.
     * Pivot rows are the source of truth. A primary institution_id is used
     * only until the first explicit assignment is stored.
     *
     * @return array<int>
     */
    public function managedInstitutionIds(): array
    {
        if (!$this->isAdmin()) {
            return $this->institution_id ? [(int) $this->institution_id] : [];
        }

        $ids = $this->managedInstitutions()
            ->pluck('institutions.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($ids === [] && $this->institution_id) {
            return [(int) $this->institution_id];
        }

        return array_values(array_unique($ids));
    }

    public function managesInstitution(int|string|null $institutionId): bool
    {
        if ($institutionId === null || $institutionId === '') {
            return false;
        }

        return in_array((int) $institutionId, $this->managedInstitutionIds(), true);
    }

    public function college()
    {
        return $this->belongsTo(College::class);
    }

    public function stage()
    {
        return $this->belongsTo(College::class, 'college_id');
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

    public function isSchoolMember(): bool
    {
        return $this->institution?->type === 'school';
    }

    public function isUniversityMember(): bool
    {
        return $this->institution?->type === 'university';
    }
}

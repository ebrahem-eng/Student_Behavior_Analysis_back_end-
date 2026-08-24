<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class College extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'institution_id',
        'name',
        'code',
        'dean_name',
        'description',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['institution_id', 'name', 'code', 'dean_name'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Model
{
    use HasFactory;

    protected $fillable = ['institution_id', 'code', 'name', 'credits'];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function sections()
    {
        return $this->hasMany(Section::class);
    }
}

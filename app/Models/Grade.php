<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = ['enrollment_id', 'exam_name', 'score', 'weight'];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }
}

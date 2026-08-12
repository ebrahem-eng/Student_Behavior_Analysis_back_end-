<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Recommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'ai_suggested_action',
        'advisor_id',
        'teacher_id',
        'status',
        'outcome_notes'
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function advisor()
    {
        return $this->belongsTo(User::class, 'advisor_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}

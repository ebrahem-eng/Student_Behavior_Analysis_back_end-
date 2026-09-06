<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = ['student_id', 'recipient_id', 'level', 'message', 'is_read'];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}

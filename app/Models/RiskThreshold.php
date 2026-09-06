<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RiskThreshold extends Model
{
    use HasFactory;

    protected $fillable = ['institution_id', 'level', 'min_score', 'max_score', 'requires_action'];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }
}

<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Alert;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats()
    {
        return response()->json([
            'total_students' => User::role('Student')->count(),
            'total_teachers' => User::role('Teacher')->count(),
            'active_high_risk_alerts' => Alert::where('level', 'high')->where('is_read', false)->count(),
            'total_active_enrollments' => Enrollment::where('status', 'active')->count()
        ]);
    }
}

<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Alert;
use App\Models\Enrollment;
use App\Support\AdminScope;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $managedIds = AdminScope::managedIds($request->user());
        $students = User::role('Student');
        $teachers = User::role('Teacher');
        $alerts = Alert::where('level', 'high')->where('is_read', false);
        $enrollments = Enrollment::where('status', 'active');

        if (is_array($managedIds)) {
            $scope = $managedIds !== [] ? $managedIds : [0];
            $students->whereIn('institution_id', $scope);
            $teachers->whereIn('institution_id', $scope);
            $alerts->whereHas('student', fn ($q) => $q->whereIn('institution_id', $scope));
            $enrollments->whereHas('student', fn ($q) => $q->whereIn('institution_id', $scope));
        }

        return response()->json([
            'total_students' => $students->count(),
            'total_teachers' => $teachers->count(),
            'active_high_risk_alerts' => $alerts->count(),
            'total_active_enrollments' => $enrollments->count(),
        ]);
    }
}

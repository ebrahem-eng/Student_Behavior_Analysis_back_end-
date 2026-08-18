<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\InstitutionController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Academic\CourseController;
use App\Http\Controllers\Api\Academic\EnrollmentController;
use App\Http\Controllers\Api\Academic\GradeController;
use App\Http\Controllers\Api\Academic\AttendanceController;
use App\Http\Controllers\Api\Academic\BehaviorLogController;
use App\Http\Controllers\Api\Academic\RecommendationController;
use App\Http\Controllers\Api\Academic\ProjectionController;
use App\Http\Controllers\Api\Admin\RiskThresholdController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\ActivityLogController;
use App\Http\Controllers\Api\Admin\ApiKeyController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ConsentController;
use App\Http\Controllers\Api\Academic\ReportController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Resources\UserResource;

// Public Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return new UserResource($request->user()->load(['roles', 'institution']));
    });

    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::put('password', [AuthController::class, 'updatePassword']);
        Route::post('logout', [AuthController::class, 'logout']);
    });

    Route::get('alerts', [AlertController::class, 'index']);
    Route::patch('alerts/{alert}/read', [AlertController::class, 'markAsRead']);
    Route::post('alerts/read-all', [AlertController::class, 'markAllAsRead']);

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // Consent Management
    Route::get('consent', [ConsentController::class, 'index']);
    Route::post('consent', [ConsentController::class, 'store']);
    Route::post('consent/revoke', [ConsentController::class, 'revoke']);
    Route::get('consent/check/{type}', [ConsentController::class, 'check']);
});

Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::apiResource('institutions', InstitutionController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('risk-thresholds', RiskThresholdController::class);
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('ml/metrics', [ProjectionController::class, 'metrics']);
    Route::get('audit-logs', [ActivityLogController::class, 'index']);
    Route::get('audit-logs/{activityLog}', [ActivityLogController::class, 'show']);
    Route::apiResource('api-keys', ApiKeyController::class);
    Route::patch('api-keys/{apiKey}/toggle', [ApiKeyController::class, 'toggle']);
});

Route::middleware(['auth:sanctum'])->prefix('academic')->group(function () {
    Route::apiResource('courses', CourseController::class);
    Route::apiResource('enrollments', EnrollmentController::class);
    Route::get('students/{student}/progress', [EnrollmentController::class, 'progress']);
    Route::post('students/{student}/project', [ProjectionController::class, 'projectPerformance']);

    Route::apiResource('grades', GradeController::class);
    Route::apiResource('attendances', AttendanceController::class);
    Route::apiResource('behavior-logs', BehaviorLogController::class);
    
    Route::apiResource('recommendations', RecommendationController::class);
    Route::patch('recommendations/{recommendation}/approve', [RecommendationController::class, 'approve']);
    Route::patch('recommendations/{recommendation}/implement', [RecommendationController::class, 'logImplementation']);
});

Route::middleware(['auth:sanctum'])->prefix('reports')->group(function () {
    Route::get('grades', [ReportController::class, 'exportGrades']);
    Route::get('attendance', [ReportController::class, 'exportAttendance']);
    Route::get('students/{student}/risk-profile', [ReportController::class, 'exportStudentRiskProfile']);
    Route::get('scheduled', [ReportController::class, 'listScheduled']);
    Route::post('schedule', [ReportController::class, 'schedule']);
    Route::delete('scheduled/{scheduledReport}', [ReportController::class, 'deleteScheduled']);
});

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

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::apiResource('institutions', InstitutionController::class);
    Route::apiResource('users', UserController::class);
});

Route::middleware(['auth:sanctum'])->prefix('academic')->group(function () {
    Route::apiResource('courses', CourseController::class);
    Route::apiResource('enrollments', EnrollmentController::class);
    Route::get('students/{student}/progress', [EnrollmentController::class, 'progress']);

    Route::apiResource('grades', GradeController::class);
    Route::apiResource('attendances', AttendanceController::class);
    Route::apiResource('behavior-logs', BehaviorLogController::class);
});

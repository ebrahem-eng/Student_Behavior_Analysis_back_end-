<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\InstitutionController;
use App\Http\Controllers\Api\Admin\UserController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::apiResource('institutions', InstitutionController::class);
    Route::apiResource('users', UserController::class);
});

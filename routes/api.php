<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->group(function () {

    Route::middleware('throttle:auth')->group(function () {
        Route::post('/register', RegisterController::class)->name('auth.register');
        Route::post('/login', LoginController::class)->name('auth.login');
    });

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::post('/logout', LogoutController::class)->name('auth.logout');

        Route::apiResource('tasks', TaskController::class);
    });

});

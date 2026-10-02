<?php

use App\Http\Controllers\Api\V1\AlertController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\VehicleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 (mobile)
|--------------------------------------------------------------------------
| Meme API REST que la version web, authenticatee par token (Sanctum).
| Elle permet de preparer une application React Native / Expo sans
| modifier le backend.
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot');

    Route::get('vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::get('vehicles/facets', [VehicleController::class, 'facets'])->name('vehicles.facets');
    Route::get('vehicles/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('favorites', [FavoriteController::class, 'index'])->name('favorites.index');
        Route::post('favorites/{vehicle}', [FavoriteController::class, 'store'])->name('favorites.store');
        Route::delete('favorites/{vehicle}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');

        Route::apiResource('alerts', AlertController::class)->except(['show']);
        Route::patch('alerts/{alert}/toggle', [AlertController::class, 'toggle'])->name('alerts.toggle');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
        Route::patch('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    });
});

Route::middleware('auth:sanctum')->get('/v1/user', fn (Request $request) => $request->user());

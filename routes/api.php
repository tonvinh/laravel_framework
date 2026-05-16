<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1;

/*
|--------------------------------------------------------------------------
| Auth — public, không cần token
|--------------------------------------------------------------------------
*/
Route::prefix('v1/auth')->group(function (): void {
    Route::post('login',    [V1\AuthController::class, 'login']);
    Route::post('register', [V1\AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', [V1\AuthController::class, 'logout']);
        Route::get('me',      [V1\AuthController::class, 'me']);
    });
});

/*
|--------------------------------------------------------------------------
| API v1 — yêu cầu auth:sanctum
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('demos', V1\DemoController::class);
});

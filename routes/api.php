<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController; // 追加：AuthControllerを呼び出す
use App\Http\Controllers\Api\GenerationController; //追加：GenerationControllerを呼び出す 
use App\Http\Middleware\DeviceApiTokenMiddleware; //追加：DeviceApiTokenMiddlewareを呼び出す    

//認証系のルート
Route::post('/register', [AuthController::class, 'register']); // 追加：app/Http/Controllers/Api/AuthController.phpからregisterメソッドを呼び出す
Route::post('/login', [AuthController::class, 'login']); // 追加：app/Http/Controllers/Api/AuthController.phpからloginメソッドを呼び出す
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']); // 追加：app/Http/Controllers/Api/AuthController.phpからlogoutメソッドを呼び出す

//ユーザー情報取得
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// デバイストークン認証を使用
// Route::middleware('device.token')->post('/generation', [\App\Http\Controllers\Api\GenerationController::class, 'store']);

Route::middleware('device.ensure')->group(function () {
    Route::post('/generation', [GenerationController::class, 'store']);
});

// テスト用に一時的に認証なしでアクセス可能なルート
// Route::post('/generation', [GenerationController::class, 'store']);

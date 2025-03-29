<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GenerationController; //追加：GenerationControllerを使うため
use App\Http\Controllers\DeviceController; //追加：DeviceControllerを使うため
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\PublicGenerationController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// PowerChartのデータを取得するためのAjaxエンドポイントを追加
Route::get('/chart-data', [DashboardController::class, 'getChartData'])
    ->middleware(['auth', 'verified'])
    ->name('chart.data');

Route::middleware('auth')->group(function () {
    // みんなの発電ページのルート（認証必要）
    Route::get('/public-generation', [PublicGenerationController::class, 'index'])->name('public.generation');
    Route::get('/public-generation/data', [PublicGenerationController::class, 'getData'])->name('public.generation.data');
    Route::get('/public-generation/towns', [PublicGenerationController::class, 'getTowns'])->name('regions.towns');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('/generation', GenerationController::class); //追加：GenerationControllerを使うため
    Route::resource('devices', DeviceController::class); //追加：デバイストークン認証用（DeviceControllerを使うため）
    Route::resource('region', RegionController::class);   //追加：RegionControllerを使うため
    // プロフィール編集用の市町村取得API（認証必要）
    Route::get('/api/profile/towns', [ProfileController::class, 'getTowns'])->name('profile.towns');
});

require __DIR__ . '/auth.php';

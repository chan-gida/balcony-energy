<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GenerationController; //追加：GenerationControllerを使うため
use App\Http\Controllers\DeviceController; //追加：DeviceControllerを使うため
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard')
    ->middleware(['auth', 'verified']);

// PowerChartのデータを取得するためのAjaxエンドポイントを追加
Route::get('/chart-data', [DashboardController::class, 'getChartData'])
    ->middleware(['auth', 'verified'])
    ->name('chart.data');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('/generation', GenerationController::class); //追加：GenerationControllerを使うため
    Route::resource('devices', DeviceController::class); //追加：デバイストークン認証用（DeviceControllerを使うため）
    // PowerChartのLivewireルートを削除
    // Route::get('/power-chart', PowerChart::class)->name('power-chart');
});


require __DIR__ . '/auth.php';

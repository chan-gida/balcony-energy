<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GenerationController; //追加：GenerationControllerを使うため
use App\Http\Controllers\DeviceController; //追加：DeviceControllerを使うため
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('/generation', GenerationController::class); //追加：GenerationControllerを使うため
    Route::resource('devices', DeviceController::class); //追加：DeviceControllerを使うため
});

// // デバイストークン認証を使用
// Route::middleware('device.token')->post('/generation', [GenerationController::class, 'store']);

require __DIR__ . '/auth.php';

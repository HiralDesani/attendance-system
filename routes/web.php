<?php

use Illuminate\Support\Facades\Route;

// Redirect root to admin login
Route::get('/', function () {
    return redirect()->route('admin.login');
});

// User authentication routes
Route::prefix('user')->name('user.')->group(function () {
    Route::get('/login', [App\Http\Controllers\User\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\User\Auth\LoginController::class, 'login']);
    Route::post('/logout', [App\Http\Controllers\User\Auth\LoginController::class, 'logout'])->name('logout');
});

// Regular authentication routes (register, password reset, etc.)
Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home')->middleware('auth');

// User attendance routes
Route::middleware('auth')->prefix('user')->name('user.')->group(function () {
    Route::get('/attendance', [App\Http\Controllers\User\AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/check-in', [App\Http\Controllers\User\AttendanceController::class, 'checkIn'])->name('attendance.check-in');
    Route::post('/attendance/checkout', [App\Http\Controllers\User\AttendanceController::class, 'checkout'])->name('attendance.checkout');
    Route::get('/attendance/{id}/logs', [App\Http\Controllers\User\AttendanceController::class, 'viewLogs'])->name('attendance.view-logs');
    Route::get('/attendance/status', [App\Http\Controllers\User\AttendanceController::class, 'getStatus'])->name('attendance.status');
});

// Admin authentication routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [App\Http\Controllers\Admin\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\Admin\Auth\LoginController::class, 'login']);
    Route::post('/logout', [App\Http\Controllers\Admin\Auth\LoginController::class, 'logout'])->name('logout');
    
    // Admin protected routes
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::resource('users', App\Http\Controllers\Admin\UserController::class);
    });
});

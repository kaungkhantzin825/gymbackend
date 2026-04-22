<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminDashboardController;

Route::get('/', function () {
    return view('welcome');
});

// Push Notification Dashboard (separate from AdminLTE)
Route::get('/admin/notifications', [AdminDashboardController::class, 'index']);
Route::post('/admin/send-notification', [AdminDashboardController::class, 'sendNotification']);

// Admin Auth
Route::get('/admin/login', [AdminController::class, 'showLogin'])->name('login');
Route::post('/admin/login', [AdminController::class, 'login']);

Route::middleware('auth:web')->group(function () {
    Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/videos', [AdminController::class, 'videos'])->name('admin.videos');
    Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
    Route::get('/admin/support', [AdminController::class, 'support'])->name('admin.support');
    Route::get('/admin/support/{id}', [AdminController::class, 'getSupportMessage']);
    Route::post('/admin/support/{id}/reply', [AdminController::class, 'replySupportMessage']);
    Route::get('/admin/settings', [AdminController::class, 'settings'])->name('admin.settings');
    Route::post('/admin/settings/update', [AdminController::class, 'updateSettings']);
});

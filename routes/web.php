<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

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

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::get('/hod/leave-requests', [AuthController::class, 'hodLeaveRequests'])->name('hod.leave.requests');
    Route::post('/hod/leave-requests/{leaveRequest}/action', [AuthController::class, 'hodLeaveRequestAction'])->name('hod.leave.requests.action');
    Route::get('/attendance-history', [AuthController::class, 'attendanceHistory'])->name('attendance.history');
    Route::get('/apply-leave', [AuthController::class, 'showApplyLeave'])->name('leave.create');
    Route::post('/apply-leave', [AuthController::class, 'applyLeave'])->name('leave.store');
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::post('/profile/upload-picture', [AuthController::class, 'uploadProfilePicture'])->name('profile.uploadPicture');
    Route::post('/clock-in', [AuthController::class, 'clockIn'])->name('clock.in');
    Route::post('/clock-out', [AuthController::class, 'clockOut'])->name('clock.out');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

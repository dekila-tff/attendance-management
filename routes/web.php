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
Route::get('/admin/login', [AuthController::class, 'showAdminLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'adminLogin'])->name('admin.login.attempt');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::post('/admin/users/create', [AuthController::class, 'adminStoreUser'])->name('admin.users.store');
    Route::post('/admin/users/{user}/update', [AuthController::class, 'adminUpdateUser'])->name('admin.users.update');
    Route::post('/admin/users/{user}/toggle-status', [AuthController::class, 'adminToggleUserStatus'])->name('admin.users.toggleStatus');
    Route::post('/admin/departments/create', [AuthController::class, 'adminStoreDepartment'])->name('admin.departments.store');
    Route::post('/admin/departments/{department}/update', [AuthController::class, 'adminUpdateDepartment'])->name('admin.departments.update');
    Route::post('/admin/departments/{department}/assign-hod', [AuthController::class, 'adminAssignDepartmentHod'])->name('admin.departments.assignHod');
    Route::post('/admin/departments/{department}/toggle-status', [AuthController::class, 'adminToggleDepartmentStatus'])->name('admin.departments.toggleStatus');
    Route::post('/admin/roles/save', [AuthController::class, 'adminSaveRole'])->name('admin.roles.save');
    Route::post('/admin/roles/assign-permissions', [AuthController::class, 'adminAssignRolePermissions'])->name('admin.roles.assignPermissions');
    Route::post('/admin/permissions/save', [AuthController::class, 'adminSavePermission'])->name('admin.permissions.save');
    Route::post('/admin/leave-types/create', [AuthController::class, 'adminStoreLeaveType'])->name('admin.leaveTypes.store');
    Route::post('/admin/leave-types/{leaveType}/update', [AuthController::class, 'adminUpdateLeaveType'])->name('admin.leaveTypes.update');
    Route::post('/admin/leave-types/{leaveType}/toggle-status', [AuthController::class, 'adminToggleLeaveTypeStatus'])->name('admin.leaveTypes.toggleStatus');
    Route::post('/admin/leave-balances/set', [AuthController::class, 'adminSetLeaveBalance'])->name('admin.leaveBalances.set');
    Route::post('/admin/leave-balances/adjust', [AuthController::class, 'adminAdjustLeaveBalance'])->name('admin.leaveBalances.adjust');
    Route::post('/admin/leave-balances/reset-yearly', [AuthController::class, 'adminResetLeaveBalancesYearly'])->name('admin.leaveBalances.resetYearly');
    Route::get('/hod/leave-requests', [AuthController::class, 'hodLeaveRequests'])->name('hod.leave.requests');
    Route::get('/ms/leave-requests', [AuthController::class, 'msLeaveRequests'])->name('ms.leave.requests');
    Route::post('/ms/leave-requests/{leaveRequest}/action', [AuthController::class, 'msLeaveRequestAction'])->name('ms.leave.requests.action');
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

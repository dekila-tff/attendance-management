<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Department;
use App\Models\Role;
use App\Models\Permission;
use App\Models\UserLeaveBalance;
use App\Models\Attendance;
use App\Models\DepartmentShift;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Tour;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use App\Notifications\LeaveRejectedBellNotification;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function showRegister()
    {
        $selfRegisterRoles = Role::query()
            ->whereIn('id', [1, 2, 3])
            ->whereRaw("LOWER(COALESCE(status, 'active')) = ?", ['active'])
            ->orderBy('id')
            ->get(['id', 'name']);

        if ($selfRegisterRoles->isEmpty()) {
            $selfRegisterRoles = collect([
                (object) ['id' => 1, 'name' => 'Medical Superintendent'],
                (object) ['id' => 2, 'name' => 'HoD'],
                (object) ['id' => 3, 'name' => 'Employee'],
            ]);
        }

        return view('register', [
            'selfRegisterRoles' => $selfRegisterRoles,
        ]);
    }

    public function showAdminLogin()
    {
        return view('admin_login');
    }

    public function testMail(Request $request)
    {
        if (!app()->environment('local')) {
            abort(403, 'Test mail endpoint is only available in local environment.');
        }

        $validated = $request->validate([
            'to' => 'nullable|email',
        ]);

        $to = (string) ($validated['to'] ?? config('mail.from.address') ?? env('MAIL_USERNAME', ''));

        if ($to === '') {
            return response('No recipient email found. Pass ?to=you@example.com in URL.', 422);
        }

        Mail::raw(
            "This is a test email from Attendance Management System.\n\nIf you received this, SMTP is working.",
            function ($message) use ($to) {
                $message
                    ->to($to)
                    ->subject('SMTP Test Email');
            }
        );

        return response('Test mail sent successfully to ' . $to . '.', 200);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // Authenticate employee users by EID via the username input.
        $credentials = [
            'eid' => $request->username,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['username' => 'The provided credentials do not match our records.'])->withInput($request->only('username'));
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'eid' => 'required|string|max:255|unique:users,eid',
            'designation' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'role_id' => ['required', Rule::in([1, 2, 3])],
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => trim((string) $validated['name']),
            'email' => strtolower((string) $validated['email']),
            'eid' => trim((string) $validated['eid']),
            'designation' => $validated['designation'] ? trim((string) $validated['designation']) : null,
            'department' => $validated['department'] ? trim((string) $validated['department']) : null,
            'role_id' => (int) $validated['role_id'],
            'status' => 'Active',
            'password' => $validated['password'],
            'email_verified_at' => Carbon::now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', 'Account created successfully!');
    }

    public function showDeviceVerification(Request $request)
    {
        $pendingUserId = (int) $request->session()->get('pending_verification_user_id', 0);

        if ($pendingUserId <= 0) {
            return redirect()->route('register')->withErrors([
                'email' => 'Please register or log in first to start verification.',
            ]);
        }

        return view('verify_device');
    }

    public function verifyDevice(Request $request)
    {
        $validated = $request->validate([
            'verification_code' => 'required|digits:6',
            'device_id' => 'required|string|max:120',
        ]);

        $pendingUserId = (int) $request->session()->get('pending_verification_user_id', 0);

        if ($pendingUserId <= 0) {
            return redirect()->route('register')->withErrors([
                'email' => 'Verification session expired. Please register or log in again.',
            ]);
        }

        $user = User::find($pendingUserId);

        if (!$user) {
            $request->session()->forget(['pending_verification_user_id', 'pending_verification_device_id']);

            return redirect()->route('register')->withErrors([
                'email' => 'User account not found. Please register again.',
            ]);
        }

        if (!$user->verification_code || !$user->verification_code_expires_at) {
            return back()->withErrors([
                'verification_code' => 'No valid verification code found. Please log in again to request a new one.',
            ]);
        }

        if (Carbon::now()->gt($user->verification_code_expires_at)) {
            return back()->withErrors([
                'verification_code' => 'Verification code has expired. Please log in again to request a new one.',
            ]);
        }

        if (!Hash::check((string) $validated['verification_code'], (string) $user->verification_code)) {
            return back()->withErrors([
                'verification_code' => 'Invalid verification code.',
            ])->withInput($request->except('verification_code'));
        }

        $user->update([
            'device_id' => trim((string) $validated['device_id']),
            'email_verified_at' => Carbon::now(),
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget(['pending_verification_user_id', 'pending_verification_device_id']);

        return redirect()->route('dashboard')
            ->with('success', 'Email verified and device bound successfully.')
            ->with('device_bound', true);
    }

    public function adminLogin(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $credentials = [
            'username' => trim((string) $request->username),
            'password' => $request->password,
        ];

        if (!Auth::guard('admin')->attempt($credentials)) {
            return back()->withErrors([
                'username' => 'The provided credentials do not match our records.',
            ])->withInput($request->only('username'));
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logoutUser(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect(route('login'));
    }

    public function logoutAdmin(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(route('admin.login'));
    }

    public function dashboard(Request $request)
    {
        if ($request->routeIs('admin.dashboard')) {
            $user = Auth::guard('admin')->user();

            if (!$user) {
                abort(403, 'Admin authentication required.');
            }

            $activeSection = (string) $request->query('section', 'roles-permissions');
            $showCreateUserForm = (bool) $request->boolean('create_user');
            $attendanceFilters = [
                'from_date' => (string) $request->query('from_date', ''),
                'to_date' => (string) $request->query('to_date', ''),
                'employee' => trim((string) $request->query('employee', '')),
            ];
            $userFilters = [
                'search' => trim((string) $request->query('user_search', '')),
            ];

            $adminAttendances = null;
            $managedUsers = null;
            $editingUser = null;
            $managedDepartments = null;
            $departmentHodCandidates = collect();
            $editingDepartment = null;
            $assigningDepartment = null;
            $showAddDepartmentForm = (bool) $request->boolean('add_department');
            $managedRoles = collect();
            $managedPermissions = collect();
            $roleAssignableUsers = collect();
            $selectedRoleIdForPermissions = (int) $request->query('assign_role_id', 0);
            $assignedPermissionIds = [];
            $managedLeaveTypes = null;
            $showAddLeaveTypeForm = (bool) $request->boolean('add_leave_type');
            $editingLeaveType = null;
            $managedLeaveRecords = null;
            $leaveBalanceEmployees = collect();
            $leaveBalanceTypes = collect();
            $leaveBalanceRows = collect();
            $leaveBalanceYear = (int) Carbon::now()->year;
            $bonusIpdEmployees = collect();

            if ($activeSection === 'roles-permissions') {
                $managedRoles = Role::query()->orderByDesc('roles_id')->get();
                $managedPermissions = Permission::query()->orderByDesc('permissions_id')->get();
                $userColumns = ['id', 'name', 'eid', 'role_id'];
                if (Schema::hasColumn('users', 'email')) {
                    $userColumns[] = 'email';
                }
                $roleAssignableUsers = User::query()
                    ->orderBy('name')
                    ->get($userColumns);

                if ($selectedRoleIdForPermissions > 0) {
                    $selectedRole = Role::query()->with('permissions:permissions_id,name')->find($selectedRoleIdForPermissions);
                    $assignedPermissionIds = $selectedRole
                        ? $selectedRole->permissions->pluck('id')->map(fn ($id) => (int) $id)->all()
                        : [];
                }
            }

            if ($activeSection === 'department-hod-management') {
                $this->syncDepartmentsFromUsers();

                $managedDepartments = Department::query()
                    ->with('hod:id,name')
                    ->orderBy('name')
                    ->paginate(15, ['*'], 'departments_page')
                    ->withQueryString();

                $departmentHodCandidates = User::query()
                    ->where('role_id', 3)
                    ->whereRaw("LOWER(COALESCE(status, 'active')) = ?", ['active'])
                    ->orderBy('name')
                    ->get(['id', 'name', 'eid', 'department']);

                $editDepartmentId = (int) $request->query('edit_department', 0);
                if ($editDepartmentId > 0) {
                    $editingDepartment = Department::find($editDepartmentId);
                }

                $assignDepartmentId = (int) $request->query('assign_department', 0);
                if ($assignDepartmentId > 0) {
                    $assigningDepartment = Department::find($assignDepartmentId);
                }
            }

            if ($activeSection === 'user-management') {
                $usersQuery = User::query()
                    ->with('role')
                    ->orderBy('id');

                if ($userFilters['search'] !== '') {
                    $term = $userFilters['search'];
                    $hasEmailColumn = Schema::hasColumn('users', 'email');

                    $usersQuery->where(function ($query) use ($term, $hasEmailColumn) {
                        $query->where('name', 'like', "%{$term}%")
                            ->orWhere('eid', 'like', "%{$term}%")
                            ->orWhere('department', 'like', "%{$term}%")
                            ->orWhere('designation', 'like', "%{$term}%");

                        if ($hasEmailColumn) {
                            $query->orWhere('email', 'like', "%{$term}%");
                        }
                    });
                }

                $managedUsers = $usersQuery->paginate(15, ['*'], 'users_page')->withQueryString();

                $editUserId = (int) $request->query('edit_user', 0);
                if ($editUserId > 0) {
                    $editingUser = User::find($editUserId);
                }
            }

            if ($activeSection === 'leave-types') {
                $managedLeaveTypes = LeaveType::query()
                    ->orderBy('id')
                    ->paginate(20, ['*'], 'leave_types_page')
                    ->withQueryString();

                $editLeaveTypeId = (int) $request->query('edit_leave_type', 0);
                if ($editLeaveTypeId > 0) {
                    $editingLeaveType = LeaveType::find($editLeaveTypeId);
                }
            }

            if ($activeSection === 'leave-balance') {
                $leaveBalanceEmployees = User::query()
                    ->whereRaw("LOWER(COALESCE(status, 'active')) = ?", ['active'])
                    ->orderBy('name')
                    ->get(['id', 'name', 'eid', 'department']);

                $leaveBalanceTypes = LeaveType::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'code', 'entitlement_days', 'is_active']);

                $employeeIds = $leaveBalanceEmployees->pluck('id')->all();
                $leaveTypeIds = $leaveBalanceTypes->pluck('id')->all();

                if (!empty($employeeIds) && !empty($leaveTypeIds)) {
                    $customBalances = UserLeaveBalance::query()
                        ->whereIn('user_id', $employeeIds)
                        ->whereIn('leave_type_id', $leaveTypeIds)
                        ->get(['user_id', 'leave_type_id', 'max_per_year', 'adjustment'])
                        ->keyBy(fn ($row) => (int) $row->user_id . ':' . (int) $row->leave_type_id);

                    $usedDaysByPair = LeaveRequest::query()
                        ->where('ms_status', 'Approved')
                        ->whereYear('start_date', $leaveBalanceYear)
                        ->whereIn('user_id', $employeeIds)
                        ->whereIn('leave_type_id', $leaveTypeIds)
                        ->selectRaw('user_id, leave_type_id, COALESCE(SUM(total_days), 0) as used_days')
                        ->groupBy('user_id', 'leave_type_id')
                        ->get()
                        ->keyBy(fn ($row) => (int) $row->user_id . ':' . (int) $row->leave_type_id);

                    $rows = [];

                    foreach ($leaveBalanceEmployees as $employee) {
                        foreach ($leaveBalanceTypes as $leaveType) {
                            $pairKey = (int) $employee->id . ':' . (int) $leaveType->id;
                            $customBalance = $customBalances->get($pairKey);
                            $maxPerYear = $customBalance
                                ? (float) $customBalance->max_per_year
                                : (float) $leaveType->entitlement_days;
                            $adjustment = $customBalance ? (float) $customBalance->adjustment : 0;
                            $used = (float) optional($usedDaysByPair->get($pairKey))->used_days;
                            $remaining = max(0, $maxPerYear + $adjustment - $used);

                            $rows[] = [
                                'employee_name' => (string) $employee->name,
                                'leave_type_name' => (string) $leaveType->name,
                                'max_per_year' => $maxPerYear,
                                'used_days' => $used,
                                'remaining_days' => $remaining,
                                'year' => $leaveBalanceYear,
                            ];
                        }
                    }

                    $leaveBalanceRows = collect($rows)
                        ->sortBy(fn ($row) => strtolower($row['employee_name'] . '|' . $row['leave_type_name']))
                        ->values();
                }
            }

            if ($activeSection === 'attendance-logs') {
                $attendanceQuery = Attendance::query()
                    ->with(['user:users_id,name,eid,department'])
                    ->orderByDesc('date')
                    ->orderByDesc('clock_in');

                if ($attendanceFilters['from_date'] !== '') {
                    $attendanceQuery->whereDate('date', '>=', $attendanceFilters['from_date']);
                }

                if ($attendanceFilters['to_date'] !== '') {
                    $attendanceQuery->whereDate('date', '<=', $attendanceFilters['to_date']);
                }

                if ($attendanceFilters['employee'] !== '') {
                    $employee = $attendanceFilters['employee'];

                    $attendanceQuery->whereHas('user', function ($query) use ($employee) {
                        $query->where('name', 'like', "%{$employee}%")
                            ->orWhere('eid', 'like', "%{$employee}%")
                            ->orWhere('email', 'like', "%{$employee}%");
                    });
                }

                $adminAttendances = $attendanceQuery->paginate(20)->withQueryString();
            }

            if ($activeSection === 'leave-records') {
                $managedLeaveRecords = LeaveRequest::query()
                    ->with(['user:users_id,name,eid,department', 'leaveType:id,name'])
                    ->where('ms_status', 'Approved')
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->paginate(20, ['*'], 'leave_records_page')
                    ->withQueryString();
            }

            if ($activeSection === 'reports' && (string) $request->query('report') === 'bonus') {
                $bonusIpdEmployees = User::query()
                    ->whereRaw("LOWER(COALESCE(department, '')) = ?", ['ipd'])
                    ->whereHas('attendances', function ($query) {
                        $query->whereNotNull('clock_in')
                            ->where(function ($nightQuery) {
                                $nightQuery->where('shift_is_overnight', true)
                                    ->orWhere('shift_name', 'like', '%night%');
                            });
                    })
                    ->orderBy('name')
                    ->get(['id', 'name', 'eid', 'department']);
            }

            return view('dashboard_admin', [
                'user' => $user,
                'isMs' => $this->isMs($user),
                'activeSection' => $activeSection,
                'attendanceFilters' => $attendanceFilters,
                'userFilters' => $userFilters,
                'adminAttendances' => $adminAttendances,
                'managedUsers' => $managedUsers,
                'editingUser' => $editingUser,
                'showCreateUserForm' => $showCreateUserForm,
                'managedDepartments' => $managedDepartments,
                'departmentHodCandidates' => $departmentHodCandidates,
                'editingDepartment' => $editingDepartment,
                'assigningDepartment' => $assigningDepartment,
                'showAddDepartmentForm' => $showAddDepartmentForm,
                'managedRoles' => $managedRoles,
                'managedPermissions' => $managedPermissions,
                'roleAssignableUsers' => $roleAssignableUsers,
                'selectedRoleIdForPermissions' => $selectedRoleIdForPermissions,
                'assignedPermissionIds' => $assignedPermissionIds,
                'managedLeaveTypes' => $managedLeaveTypes,
                'showAddLeaveTypeForm' => $showAddLeaveTypeForm,
                'editingLeaveType' => $editingLeaveType,
                'managedLeaveRecords' => $managedLeaveRecords,
                'leaveBalanceEmployees' => $leaveBalanceEmployees,
                'leaveBalanceTypes' => $leaveBalanceTypes,
                'leaveBalanceRows' => $leaveBalanceRows,
                'leaveBalanceYear' => $leaveBalanceYear,
                'bonusIpdEmployees' => $bonusIpdEmployees,
            ]);
        }

        $user = Auth::guard('web')->user();

        if (!$user) {
            abort(403, 'Authentication required.');
        }
        
        // Get today's attendance record
        $today = Carbon::today();
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            $attendance = Attendance::where('user_id', $user->id)
                ->whereNull('clock_out')
                ->orderByDesc('date')
                ->first();
        }

        $shift = $this->resolveShiftForAttendance($user, $attendance);
        $clockOutAt = $shift['clock_out_after_at'];
        $clockOutLocked = $attendance
            ? Carbon::now()->lt($clockOutAt)
            : Carbon::now()->lt($shift['clock_out_after_at']);
        
        $isHod = $this->isHod($user);
        $isMs = $this->isMs($user);
        $leaveApproveCount = 0;
        $tourRecords = collect();

        if (!$isMs && Schema::hasTable('tour')) {
            $currentUserId = $user->users_id ?? $user->id ?? null;

            if ($currentUserId !== null) {
                $tourRecords = Tour::query()
                    ->with('department')
                    ->where('users_id', $currentUserId)
                    ->orderByDesc('start_date')
                    ->orderByDesc('tour_id')
                    ->limit(6)
                    ->get();
            }
        }

        if ($isHod) {
            $leaveApproveCount = LeaveRequest::query()
                ->where('submit_to', 'HoD')
                ->where('hod_status', 'Pending')
                ->whereHas('user', function ($query) use ($user) {
                    $query->where('department', $user->department)
                        ->where('users_id', '!=', $user->id);
                })
                ->count();
        }

        return view('dashboard_employee', [
            'user' => $user,
            'attendance' => $attendance,
            'clockOutLocked' => $clockOutLocked,
            'clockOutUnlockTime' => $clockOutAt->format('g:i A'),
            'shiftName' => $shift['name'],
            'isHod' => $isHod,
            'isMs' => $isMs,
            'leaveApproveCount' => $leaveApproveCount,
            'tourRecords' => $tourRecords,
        ]);
    }

    public function adminUpdateUser(Request $request, User $user)
    {
        $admin = Auth::user();

        if (!$this->isAdmin($admin)) {
            abort(403, 'Only admin can update users.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'eid' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'eid')->ignore($user->id, 'users_id'),
            ],
            'designation' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'role_id' => ['required', Rule::in([1, 2, 3])],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        if ($user->id === $admin->id && $validated['status'] === 'Inactive') {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update([
            'name' => $validated['name'],
            'eid' => $validated['eid'] !== '' ? $validated['eid'] : null,
            'designation' => $validated['designation'],
            'department' => $validated['department'],
            'role_id' => (int) $validated['role_id'],
            'status' => ucfirst(strtolower((string) $validated['status'])),
        ]);

        return redirect()
            ->route('admin.dashboard', ['section' => 'user-management'])
            ->with('success', 'User updated successfully.');
    }

    public function adminStoreUser(Request $request)
    {
        $admin = Auth::user();

        if (!$this->isAdmin($admin)) {
            abort(403, 'Only admin can create users.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'eid' => 'nullable|string|max:255|unique:users,eid',
            'designation' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'role_id' => ['required', Rule::in([1, 2, 3])],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => strtolower((string) $validated['email']),
            'eid' => $validated['eid'] !== '' ? $validated['eid'] : null,
            'designation' => $validated['designation'],
            'department' => $validated['department'],
            'role_id' => (int) $validated['role_id'],
            'status' => ucfirst(strtolower((string) $validated['status'])),
            'password' => $validated['password'],
        ]);

        return redirect()
            ->route('admin.dashboard', ['section' => 'user-management'])
            ->with('success', 'New user created successfully.');
    }

    public function adminToggleUserStatus(User $user)
    {
        $admin = Auth::user();

        if (!$this->isAdmin($admin)) {
            abort(403, 'Only admin can change user status.');
        }

        $currentStatus = strtolower(trim((string) $user->status));
        $newStatus = $currentStatus === 'active' || $currentStatus === ''
            ? 'Inactive'
            : 'Active';

        if ($user->id === $admin->id && $newStatus === 'Inactive') {
            return redirect()
                ->route('admin.dashboard', ['section' => 'user-management'])
                ->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['status' => $newStatus]);

        return redirect()
            ->route('admin.dashboard', ['section' => 'user-management'])
            ->with('success', 'User status updated to ' . $newStatus . '.');
    }

    public function adminDeleteUser(User $user)
    {
        $admin = Auth::user();

        if (!$this->isAdmin($admin)) {
            abort(403, 'Only admin can delete users.');
        }

        if ($user->id === $admin->id) {
            return redirect()
                ->route('admin.dashboard', ['section' => 'user-management'])
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        if (DB::table('users')->count() === 0) {
            DB::statement('ALTER TABLE users AUTO_INCREMENT = 1');
        }

        return redirect()
            ->route('admin.dashboard', ['section' => 'user-management'])
            ->with('success', 'User deleted successfully.');
    }

    public function adminResetUserPassword(Request $request, User $user)
    {
        $admin = Auth::user();

        if (!$this->isAdmin($admin)) {
            abort(403, 'Only admin can reset user passwords.');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => $validated['password'],
        ]);

        $routeParams = ['section' => 'user-management'];
        $search = trim((string) $request->input('user_search', ''));

        if ($search !== '') {
            $routeParams['user_search'] = $search;
        }

        return redirect()
            ->route('admin.dashboard', $routeParams)
            ->with('success', 'Password reset successfully for ' . $user->name . '.');
    }

    public function adminStoreDepartment(Request $request)
    {
        $admin = Auth::user();

        if (!$this->isAdmin($admin)) {
            abort(403, 'Only admin can create departments.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        Department::create([
            'name' => trim($validated['name']),
            'status' => ucfirst(strtolower((string) $validated['status'])),
        ]);

        return redirect()
            ->route('admin.dashboard', ['section' => 'department-hod-management'])
            ->with('success', 'Department added successfully.');
    }

    public function adminUpdateDepartment(Request $request, Department $department)
    {
        $admin = Auth::user();

        if (!$this->isAdmin($admin)) {
            abort(403, 'Only admin can update departments.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($department->id)],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        $department->update([
            'name' => trim($validated['name']),
            'status' => ucfirst(strtolower((string) $validated['status'])),
        ]);

        return redirect()
            ->route('admin.dashboard', ['section' => 'department-hod-management'])
            ->with('success', 'Department updated successfully.');
    }

    public function adminAssignDepartmentHod(Request $request, Department $department)
    {
        $admin = Auth::user();

        if (!$this->isAdmin($admin)) {
            abort(403, 'Only admin can assign HoD.');
        }

        $validated = $request->validate([
            'hod_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        $hodUser = User::findOrFail((int) $validated['hod_user_id']);

        if ((int) ($hodUser->role_id ?? 0) !== 2) {
            $hodUser->update(['role_id' => 2]);
        }

        $department->update([
            'hod_user_id' => $hodUser->id,
        ]);

        if (trim((string) $hodUser->department) !== trim((string) $department->name)) {
            $hodUser->update([
                'department' => $department->name,
            ]);
        }

        return redirect()
            ->route('admin.dashboard', ['section' => 'department-hod-management'])
            ->with('success', 'HoD assigned successfully.');
    }

    public function adminToggleDepartmentStatus(Department $department)
    {
        $admin = Auth::user();

        if (!$this->isAdmin($admin)) {
            abort(403, 'Only admin can change department status.');
        }

        $currentStatus = strtolower(trim((string) $department->status));
        $newStatus = $currentStatus === 'active' || $currentStatus === ''
            ? 'Inactive'
            : 'Active';

        $department->update([
            'status' => $newStatus,
        ]);

        return redirect()
            ->route('admin.dashboard', ['section' => 'department-hod-management'])
            ->with('success', 'Department status updated to ' . $newStatus . '.');
    }

    public function adminSaveRole(Request $request)
    {
        $admin = Auth::user();

        if (!$this->isAdmin($admin)) {
            abort(403, 'Only admin can manage roles.');
        }

        $roleId = (int) $request->input('role_id', 0);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($roleId > 0 ? $roleId : null),
            ],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        if ($roleId > 0) {
            $role = Role::findOrFail($roleId);
            $role->update([
                'name' => trim($validated['name']),
                'status' => ucfirst(strtolower((string) $validated['status'])),
            ]);

            $message = 'Role updated successfully.';
        } else {
            Role::create([
                'name' => trim($validated['name']),
                'status' => ucfirst(strtolower((string) $validated['status'])),
            ]);

            $message = 'Role added successfully.';
        }

        return redirect()
            ->route('admin.dashboard', ['section' => 'roles-permissions'])
            ->with('success', $message);
    }

    public function adminSavePermission(Request $request)
    {
        $admin = Auth::user();

        if (!$this->isAdmin($admin)) {
            abort(403, 'Only admin can manage permissions.');
        }

        $permissionId = (int) $request->input('permission_id', 0);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')->ignore($permissionId > 0 ? $permissionId : null),
            ],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        if ($permissionId > 0) {
            $permission = Permission::findOrFail($permissionId);
            $permission->update([
                'name' => trim($validated['name']),
                'status' => ucfirst(strtolower((string) $validated['status'])),
            ]);

            $message = 'Permission updated successfully.';
        } else {
            Permission::create([
                'name' => trim($validated['name']),
                'status' => ucfirst(strtolower((string) $validated['status'])),
            ]);

            $message = 'Permission added successfully.';
        }

        return redirect()
            ->route('admin.dashboard', ['section' => 'roles-permissions'])
            ->with('success', $message);
    }

    public function adminAssignRolePermissions(Request $request)
    {
        $admin = Auth::user();

        if (!$this->isAdmin($admin)) {
            abort(403, 'Only admin can assign permissions.');
        }

        $validated = $request->validate([
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        $role = Role::findOrFail((int) $validated['role_id']);
        $permissionIds = array_map('intval', $validated['permission_ids'] ?? []);

        $role->permissions()->sync($permissionIds);

        return redirect()
            ->route('admin.dashboard', [
                'section' => 'roles-permissions',
                'assign_role_id' => $role->id,
            ])
            ->with('success', 'Role permissions saved successfully.');
    }

    public function adminAssignUserToRole(Request $request, Role $role)
    {
        $admin = Auth::user();

        if (!$this->isAdmin($admin)) {
            abort(403, 'Only admin can assign user roles.');
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        $selectedUser = User::findOrFail((int) $validated['user_id']);

        $selectedUser->update(['role_id' => (int) $role->id]);

        return redirect()
            ->route('admin.dashboard', ['section' => 'roles-permissions'])
            ->with('success', 'Role assigned to ' . $selectedUser->name . ' successfully.');
    }

    public function adminStoreLeaveType(Request $request)
    {
        $admin = Auth::user();

        if (!$this->isAdmin($admin)) {
            abort(403, 'Only admin can manage leave types.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:leave_types,name',
            'code' => 'required|string|max:50|unique:leave_types,code',
            'description' => 'nullable|string|max:1000',
            'entitlement_days' => 'required|numeric|min:0|max:365',
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        LeaveType::create([
            'name' => trim($validated['name']),
            'code' => strtoupper(trim((string) $validated['code'])),
            'description' => $validated['description'] ?? null,
            'entitlement_days' => (float) $validated['entitlement_days'],
            'is_active' => strtolower((string) $validated['status']) === 'active',
        ]);

        return redirect()
            ->route('admin.dashboard', ['section' => 'leave-types'])
            ->with('success', 'Leave type added successfully.');
    }

    public function adminUpdateLeaveType(Request $request, LeaveType $leaveType)
    {
        $admin = Auth::user();

        if (!$this->isAdmin($admin)) {
            abort(403, 'Only admin can manage leave types.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('leave_types', 'name')->ignore($leaveType->id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('leave_types', 'code')->ignore($leaveType->id)],
            'description' => 'nullable|string|max:1000',
            'entitlement_days' => 'required|numeric|min:0|max:365',
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        $leaveType->update([
            'name' => trim($validated['name']),
            'code' => strtoupper(trim((string) $validated['code'])),
            'description' => $validated['description'] ?? null,
            'entitlement_days' => (float) $validated['entitlement_days'],
            'is_active' => strtolower((string) $validated['status']) === 'active',
        ]);

        return redirect()
            ->route('admin.dashboard', ['section' => 'leave-types'])
            ->with('success', 'Leave type updated successfully.');
    }

    public function adminToggleLeaveTypeStatus(LeaveType $leaveType)
    {
        $admin = Auth::user();

        if (!$this->isAdmin($admin)) {
            abort(403, 'Only admin can manage leave types.');
        }

        $leaveType->update([
            'is_active' => !(bool) $leaveType->is_active,
        ]);

        return redirect()
            ->route('admin.dashboard', ['section' => 'leave-types'])
            ->with('success', 'Leave type status updated successfully.');
    }

    public function adminSetLeaveBalance(Request $request)
    {
        $admin = Auth::user();

        if (!$this->isAdmin($admin)) {
            abort(403, 'Only admin can manage leave balances.');
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')],
            'max_per_year' => 'required|numeric|min:0|max:365',
        ]);

        UserLeaveBalance::updateOrCreate(
            [
                'user_id' => (int) $validated['user_id'],
                'leave_type_id' => (int) $validated['leave_type_id'],
            ],
            [
                'max_per_year' => (float) $validated['max_per_year'],
            ]
        );

        return redirect()
            ->route('admin.dashboard', ['section' => 'leave-balance'])
            ->with('success', 'Leave balance set successfully.');
    }

    public function adminAdjustLeaveBalance(Request $request)
    {
        $admin = Auth::user();

        if (!$this->isAdmin($admin)) {
            abort(403, 'Only admin can manage leave balances.');
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')],
            'adjustment' => 'required|numeric|min:-365|max:365',
        ]);

        $balance = UserLeaveBalance::firstOrCreate(
            [
                'user_id' => (int) $validated['user_id'],
                'leave_type_id' => (int) $validated['leave_type_id'],
            ],
            [
                'max_per_year' => 0,
                'adjustment' => 0,
            ]
        );

        $balance->update([
            'adjustment' => (float) $balance->adjustment + (float) $validated['adjustment'],
        ]);

        return redirect()
            ->route('admin.dashboard', ['section' => 'leave-balance'])
            ->with('success', 'Leave balance adjusted successfully.');
    }

    public function adminResetLeaveBalancesYearly()
    {
        $admin = Auth::user();

        if (!$this->isAdmin($admin)) {
            abort(403, 'Only admin can manage leave balances.');
        }

        UserLeaveBalance::query()->update([
            'adjustment' => 0,
        ]);

        return redirect()
            ->route('admin.dashboard', ['section' => 'leave-balance'])
            ->with('success', 'All leave balance adjustments were reset successfully.');
    }

    public function hodLeaveRequests(Request $request)
    {
        $user = Auth::user();
        $isHod = $this->isHod($user);
        $isMs = $this->isMs($user);

        if (!$isHod) {
            abort(403, 'Only HoD can view employee leave requests.');
        }

        $leaveRequests = LeaveRequest::query()
            ->with(['user', 'leaveType'])
            ->whereHas('user', function ($query) use ($user) {
                $query->where('department', $user->department)
                    ->where('users_id', '!=', $user->id);
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $leaveApproveCount = LeaveRequest::query()
            ->where('submit_to', 'HoD')
            ->where('hod_status', 'Pending')
            ->whereHas('user', function ($query) use ($user) {
                $query->where('department', $user->department)
                    ->where('users_id', '!=', $user->id);
            })
            ->count();

        return view('hod_leave_requests', [
            'user' => $user,
            'isHod' => $isHod,
            'isMs' => $isMs,
            'leaveApproveCount' => $leaveApproveCount,
            'leaveRequests' => $leaveRequests,
        ]);
    }

    public function hodStaffList(Request $request)
    {
        $user = Auth::user();
        $isHod = $this->isHod($user);
        $isMs = $this->isMs($user);

        if (!$isHod) {
            abort(403, 'Only HoD can view staff list.');
        }

        $leaveApproveCount = LeaveRequest::query()
            ->where('submit_to', 'HoD')
            ->where('hod_status', 'Pending')
            ->whereHas('user', function ($query) use ($user) {
                $query->where('department', $user->department)
                    ->where('users_id', '!=', $user->id);
            })
            ->count();

        $staffMembers = User::query()
            ->where('department', $user->department)
            ->where('users_id', '!=', $user->id)
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('hod_staff_list', [
            'user' => $user,
            'isHod' => $isHod,
            'isMs' => $isMs,
            'leaveApproveCount' => $leaveApproveCount,
            'staffMembers' => $staffMembers,
        ]);
    }

    public function hodLeaveRequestAction(Request $request, LeaveRequest $leaveRequest)
    {
        $user = Auth::user();

        if (!$this->isHod($user)) {
            abort(403, 'Only HoD can take leave action.');
        }

        $validated = $request->validate([
            'action' => ['required', Rule::in(['forward', 'reject'])],
            'rejection_reason' => ['nullable', 'string', 'max:1000', 'required_if:action,reject'],
        ]);

        $rejectionReason = trim((string) ($validated['rejection_reason'] ?? ''));

        if ($validated['action'] === 'reject' && $rejectionReason === '') {
            return back()->with('error', 'Please provide a reason before rejecting this leave request.');
        }

        $leaveRequest->loadMissing('user');

        $isDepartmentMatch = $leaveRequest->user
            && $leaveRequest->user->department === $user->department
            && $leaveRequest->user->id !== $user->id;

        if (!$isDepartmentMatch || $leaveRequest->submit_to !== 'HoD') {
            abort(403, 'You are not allowed to action this leave request.');
        }

        if (strtolower((string) $leaveRequest->hod_status) !== 'pending') {
            return back()->with('error', 'This leave request has already been processed.');
        }

        if ($validated['action'] === 'forward') {
            $leaveRequest->update([
                'hod_status' => 'Forwarded',
                'submit_to' => 'MS',
                'ms_status' => 'Pending',
                'is_direct_to_ms' => false,
                'rejection_reason' => null,
            ]);

            return back()->with('success', 'Leave request forwarded to MS.');
        }

        $leaveRequest->update([
            'hod_status' => 'Rejected',
            'ms_status' => 'Rejected',
            'rejection_reason' => $rejectionReason,
        ]);

        if ($leaveRequest->user) {
            $leaveRequest->user->notify(new LeaveRejectedBellNotification($leaveRequest, 'HoD'));
        }

        $mailSent = $this->sendLeaveStatusEmail($leaveRequest, 'Rejected', 'HoD');

        if (! $mailSent) {
            return back()->with('warning', 'Leave request rejected by HoD. Notification email could not be delivered.');
        }

        return back()->with('success', 'Leave request rejected by HoD. Notification email sent.');
    }

    public function msLeaveRequests(Request $request)
    {
        $user = Auth::user();

        if (!$this->isMs($user)) {
            abort(403, 'Only Medical Superintendent can view this page.');
        }

        $leaveRequests = LeaveRequest::query()
            ->with(['user', 'leaveType'])
            ->where('submit_to', 'MS')
            ->where(function ($query) {
                $query->where('hod_status', 'Forwarded')
                    ->orWhere('is_direct_to_ms', true);
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $attendanceFilters = [
            'from_date' => (string) $request->query('att_from_date', ''),
            'to_date' => (string) $request->query('att_to_date', ''),
            'employee' => trim((string) $request->query('att_employee', '')),
        ];

        $attendanceQuery = Attendance::query()
            ->with(['user:users_id,name,eid,email,department,role_id'])
            ->whereHas('user', function ($query) {
                $query->where('role_id', '!=', 1);
            })
            ->orderByDesc('date')
            ->orderByDesc('clock_in');

        if ($attendanceFilters['from_date'] !== '') {
            $attendanceQuery->whereDate('date', '>=', $attendanceFilters['from_date']);
        }

        if ($attendanceFilters['to_date'] !== '') {
            $attendanceQuery->whereDate('date', '<=', $attendanceFilters['to_date']);
        }

        if ($attendanceFilters['employee'] !== '') {
            $employee = $attendanceFilters['employee'];

            $attendanceQuery->whereHas('user', function ($query) use ($employee) {
                $query->where('name', 'like', "%{$employee}%")
                    ->orWhere('eid', 'like', "%{$employee}%")
                    ->orWhere('email', 'like', "%{$employee}%");
            });
        }

        $attendanceLogs = $attendanceQuery
            ->paginate(15, ['*'], 'attendance_page')
            ->withQueryString();

        return view('ms_leave_requests', [
            'user' => $user,
            'leaveRequests' => $leaveRequests,
            'attendanceLogs' => $attendanceLogs,
            'attendanceFilters' => $attendanceFilters,
        ]);
    }

    public function msAttendanceLogs(Request $request)
    {
        $user = Auth::user();

        if (!$this->isMs($user)) {
            abort(403, 'Only Medical Superintendent can view attendance logs.');
        }

        $filters = $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'employee' => 'nullable|string|max:255',
        ]);

        $attendanceQuery = Attendance::query()
            ->with(['user:users_id,name,eid,email,department,role_id'])
            ->whereHas('user', function ($query) {
                $query->where('role_id', '!=', 1);
            })
            ->orderByDesc('date')
            ->orderByDesc('clock_in');

        if (!empty($filters['from_date'])) {
            $attendanceQuery->whereDate('date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $attendanceQuery->whereDate('date', '<=', $filters['to_date']);
        }

        if (!empty($filters['employee'])) {
            $employee = trim((string) $filters['employee']);

            $attendanceQuery->whereHas('user', function ($query) use ($employee) {
                $query->where('name', 'like', "%{$employee}%")
                    ->orWhere('eid', 'like', "%{$employee}%")
                    ->orWhere('email', 'like', "%{$employee}%");
            });
        }

        $attendances = $attendanceQuery->paginate(20)->withQueryString();

        return view('ms_attendance_logs', [
            'user' => $user,
            'attendances' => $attendances,
            'filters' => [
                'from_date' => (string) ($filters['from_date'] ?? ''),
                'to_date' => (string) ($filters['to_date'] ?? ''),
                'employee' => (string) ($filters['employee'] ?? ''),
            ],
        ]);
    }

    public function msLeaveRequestAction(Request $request, LeaveRequest $leaveRequest)
    {
        $user = Auth::user();

        if (!$this->isMs($user)) {
            abort(403, 'Only Medical Superintendent can take leave action.');
        }

        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject'])],
        ]);

        $leaveRequest->loadMissing(['user', 'leaveType']);

        $isForwardedToMs = strtoupper(trim((string) $leaveRequest->submit_to)) === 'MS'
            && (
                strtoupper(trim((string) $leaveRequest->hod_status)) === 'FORWARDED'
                || (bool) $leaveRequest->is_direct_to_ms
            );

        if (!$isForwardedToMs) {
            abort(403, 'This leave request is not available for MS action.');
        }

        if (strtolower((string) $leaveRequest->ms_status) !== 'pending') {
            return back()->with('error', 'This leave request has already been processed by MS.');
        }

        if ($validated['action'] === 'approve') {
            $leaveRequest->update([
                'ms_status' => 'Approved',
            ]);

            $mailSent = $this->sendLeaveStatusEmail($leaveRequest, 'Approved', 'MS');

            if (! $mailSent) {
                return back()->with('warning', 'Leave request approved by MS. Notification email could not be delivered.');
            }

            return back()->with('success', 'Leave request approved by MS. Notification email sent.');
        }

        $leaveRequest->update([
            'ms_status' => 'Rejected',
        ]);

        if ($leaveRequest->user) {
            $leaveRequest->user->notify(new LeaveRejectedBellNotification($leaveRequest, 'MS'));
        }

        $mailSent = $this->sendLeaveStatusEmail($leaveRequest, 'Rejected', 'MS');

        if (! $mailSent) {
            return back()->with('warning', 'Leave request rejected by MS. Notification email could not be delivered.');
        }

        return back()->with('success', 'Leave request rejected by MS. Notification email sent.');
    }

    public function profile()
    {
        $user = Auth::user();
        
        return view('profile', [
            'user' => $user,
        ]);
    }

    public function markNotificationAsRead(string $notification)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Authentication required.');
        }

        $notificationKey = Schema::hasColumn('notifications', 'notifications_id')
            ? 'notifications_id'
            : 'id';

        $updates = [
            'read_at' => now(),
        ];

        if (Schema::hasColumn('notifications', 'updated_at')) {
            $updates['updated_at'] = now();
        }

        DB::table('notifications')
            ->where($notificationKey, $notification)
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->getAuthIdentifier())
            ->whereNull('read_at')
            ->update($updates);

        return back();
    }

    public function attendanceHistory(Request $request)
    {
        $user = Auth::user();
        $isHod = $this->isHod($user);
        $isMs = $this->isMs($user);
        $leaveApproveCount = 0;

        if ($isHod) {
            $leaveApproveCount = LeaveRequest::query()
                ->where('submit_to', 'HoD')
                ->where('hod_status', 'Pending')
                ->whereHas('user', function ($query) use ($user) {
                    $query->where('department', $user->department)
                        ->where('users_id', '!=', $user->id);
                })
                ->count();
        }
        
        // Get current month for summary
        $currentMonth = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();

        // Get attendance summary for current month
        $currentMonthAttendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$currentMonth, $currentMonthEnd])
            ->get();

        // Calculate summary stats
        // Present = both clock_in and clock_out recorded
        $presentDays = $currentMonthAttendances->filter(function($att) {
            return $att->clock_in && $att->clock_out;
        })->count();
        
        // Late = clock_in recorded and remarks contain "Late"
        $lateDays = $currentMonthAttendances->filter(function($att) {
            return $att->clock_in && stripos($att->remarks ?? '', 'late') !== false;
        })->count();
        
        // Absent = no clock_in and not a leave day
        $absentDays = $currentMonthAttendances->filter(function($att) {
            return $att->status !== 'leave' && !$att->clock_in;
        })->count();
        
        // Leave = status is leave
        $leaveDays = $currentMonthAttendances->where('status', 'leave')->count();

        // Get all attendance records (current month by default, can be expanded for all records)
        $historyQuery = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$currentMonth, $currentMonthEnd])
            ->orderByDesc('date');

        $attendances = $historyQuery->paginate(15)->withQueryString();

        return view('attendance_history', [
            'user' => $user,
            'isHod' => $isHod,
            'isMs' => $isMs,
            'leaveApproveCount' => $leaveApproveCount,
            'attendances' => $attendances,
            'currentMonth' => $currentMonth->format('F Y'),
            'presentDays' => $presentDays,
            'lateDays' => $lateDays,
            'absentDays' => $absentDays,
            'leaveDays' => $leaveDays,
        ]);
    }

    public function showTourRecords()
    {
        $user = Auth::user();
        $dzongkhags = [
            'Bumthang',
            'Chhukha',
            'Dagana',
            'Gasa',
            'Haa',
            'Lhuentse',
            'Mongar',
            'Paro',
            'Pemagatshel',
            'Punakha',
            'Samdrup Jongkhar',
            'Samtse',
            'Sarpang',
            'Thimphu',
            'Trashigang',
            'Trashi Yangtse',
            'Trongsa',
            'Tsirang',
            'Wangdue Phodrang',
            'Zhemgang',
        ];

        if ($this->isMs($user)) {
            abort(403, 'MS users cannot access tour records.');
        }

        if (!Schema::hasTable('tour')) {
            return back()->with('error', 'Tour table is not available yet.');
        }

        $tourRecords = Tour::query()
            ->where('users_id', $user->id)
            ->orderByDesc('start_date')
            ->orderByDesc('tour_id')
            ->get();

        return view('tour_records', [
            'user' => $user,
            'isHod' => $this->isHod($user),
            'isMs' => $this->isMs($user),
            'leaveApproveCount' => $this->isHod($user)
                ? LeaveRequest::query()
                    ->where('submit_to', 'HoD')
                    ->where('hod_status', 'Pending')
                    ->whereHas('user', function ($query) use ($user) {
                        $query->where('department', $user->department)
                            ->where('users_id', '!=', $user->id);
                    })
                    ->count()
                : 0,
            'dzongkhags' => $dzongkhags,
            'tourRecords' => $tourRecords,
        ]);
    }

    public function storeTourRecord(Request $request)
    {
        $user = Auth::user();
        $dzongkhags = [
            'Bumthang',
            'Chhukha',
            'Dagana',
            'Gasa',
            'Haa',
            'Lhuentse',
            'Mongar',
            'Paro',
            'Pemagatshel',
            'Punakha',
            'Samdrup Jongkhar',
            'Samtse',
            'Sarpang',
            'Thimphu',
            'Trashigang',
            'Trashi Yangtse',
            'Trongsa',
            'Tsirang',
            'Wangdue Phodrang',
            'Zhemgang',
        ];

        if ($this->isMs($user)) {
            abort(403, 'MS users cannot create tour records.');
        }

        if (!Schema::hasTable('tour')) {
            return back()->with('error', 'Tour table is not available yet.');
        }

        $validated = $request->validate([
            'place' => ['required', Rule::in($dzongkhags)],
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'purpose' => 'nullable|string|max:2000',
            'office_order_pdf' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $startDate = Carbon::parse((string) $validated['start_date'])->toDateString();
        $endDate = Carbon::parse((string) $validated['end_date'])->toDateString();

        $hasOverlappingTour = Tour::query()
            ->where('users_id', $user->id)
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->exists();

        if ($hasOverlappingTour) {
            return back()->withInput()->with('error', 'Tour dates overlap with an existing tour record. Please choose a different date range.');
        }

        $department = Department::query()
            ->whereRaw('LOWER(name) = ?', [strtolower(trim((string) ($user->department ?? '')))])
            ->first();

        if (!$department) {
            return back()->withInput()->with('error', 'Your department is not mapped in department master. Please contact admin.');
        }

        $pdfPath = null;
        if ($request->hasFile('office_order_pdf')) {
            $pdfPath = $request->file('office_order_pdf')->store('tour-office-orders', 'public');
        }

        Tour::create([
            'users_id' => $user->id,
            'department_id' => $department->department_id,
            'place' => trim((string) $validated['place']),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'purpose' => trim((string) ($validated['purpose'] ?? '')) ?: null,
            'office_order_pdf' => $pdfPath,
        ]);

        return redirect()->route('tour.records')
            ->with('success', 'Tour record saved successfully.')
            ->with('tour_popup', [
                'place' => trim((string) $validated['place']),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'purpose' => trim((string) ($validated['purpose'] ?? '')) ?: '-',
            ]);
    }

    public function showApplyLeave()
    {
        $user = Auth::user();
        $isHod = $this->isHod($user);
        $isMs = $this->isMs($user);
        $leaveApproveCount = 0;

        if ($isHod) {
            $leaveApproveCount = LeaveRequest::query()
                ->where('submit_to', 'HoD')
                ->where('hod_status', 'Pending')
                ->whereHas('user', function ($query) use ($user) {
                    $query->where('department', $user->department)
                        ->where('users_id', '!=', $user->id);
                })
                ->count();
        }

        $leaveTypes = LeaveType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $balances = $this->getLeaveBalances($user->id, $leaveTypes);
        $leaveHistory = LeaveRequest::where('user_id', $user->id)
            ->with('leaveType')
            ->orderByDesc('created_at')
            ->get();

        return view('apply_leave', [
            'user' => $user,
            'isHod' => $isHod,
            'isMs' => $isMs,
            'leaveApproveCount' => $leaveApproveCount,
            'leaveTypes' => $leaveTypes,
            'balances' => $balances,
            'leaveHistory' => $leaveHistory,
        ]);
    }

    public function applyLeave(Request $request)
    {
        $user = Auth::user();
        $isHod = $this->isHod($user);
        $allowedSubmitTargets = $isHod ? ['MS'] : ['HoD', 'MS'];

        $leaveTypes = LeaveType::query()
            ->where('is_active', true)
            ->get();

        $rules = [
            'leave_type' => [
                'required',
                Rule::exists('leave_types', 'id')->where(function ($query) {
                    $query->where('is_active', true);
                }),
            ],
            'submit_to' => ['required', Rule::in($allowedSubmitTargets)],
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'total_days' => 'required|numeric|min:0.5',
            'reason' => 'required|string|max:1000',
        ];

        // Add prescription validation only for Medical Leave (id = 5)
        if ($request->filled('leave_type') && $request->input('leave_type') == 5) {
            $rules['prescription'] = 'required|file|mimes:jpeg,jpg,png,pdf|max:5120';
        }

        $validated = $request->validate($rules);
        $submitTo = $isHod ? 'MS' : $validated['submit_to'];
        $startDate = Carbon::parse($validated['start_date'])->toDateString();
        $endDate = Carbon::parse($validated['end_date'])->toDateString();
        $totalDays = (float) $validated['total_days'];

        $scaledDays = (int) round($totalDays * 10);
        if ($scaledDays % 5 !== 0) {
            return back()->withInput()->with('error', 'Total days must be in 0.5 increments (example: 1, 1.5, 2).');
        }

        $hasOverlappingLeave = LeaveRequest::where('user_id', $user->id)
            ->where('ms_status', '!=', 'Rejected')
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->exists();

        if ($hasOverlappingLeave) {
            return back()->withInput()->with('error', 'A leave request already exists for the selected date range.');
        }

        $selectedLeaveType = $leaveTypes->firstWhere('id', (int) $validated['leave_type']);

        if (!$selectedLeaveType) {
            return back()->withInput()->with('error', 'Selected leave type is not available.');
        }

        $balances = $this->getLeaveBalances($user->id, $leaveTypes);
        $availableBalance = (float) ($balances[$selectedLeaveType->id] ?? 0);
        $entitlement = (float) $selectedLeaveType->entitlement_days;
        $hasCustomBalance = UserLeaveBalance::query()
            ->where('user_id', $user->id)
            ->where('leave_type_id', $selectedLeaveType->id)
            ->exists();
        $shouldEnforceBalance = $hasCustomBalance || $entitlement > 0;

        if ($shouldEnforceBalance && $totalDays > $availableBalance) {
            return back()->withInput()->with('error', 'Insufficient leave balance for the selected leave type.');
        }

        $balanceAfterRequest = $shouldEnforceBalance
            ? max(0, $availableBalance - $totalDays)
            : 0;

        $prescriptionPath = null;
        if ($request->hasFile('prescription')) {
            $prescriptionPath = $request->file('prescription')->store('prescriptions', 'public');
        }

        LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $selectedLeaveType->id,
            'leave_type' => $selectedLeaveType->name,
            'is_direct_to_ms' => $submitTo === 'MS',
            'submit_to' => $submitTo,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'balance' => $balanceAfterRequest,
            'reason' => $validated['reason'],
            'prescription' => $prescriptionPath,
            'hod_status' => $submitTo === 'HoD' ? 'Pending' : '',
            'ms_status' => 'Pending',
        ]);

        return redirect()->route('leave.create')->with('success', 'Leave request submitted to HoD for approval.');
    }

    private function getLeaveBalances(int $userId, $leaveTypes): array
    {
        $usedDaysByTypeId = LeaveRequest::query()
            ->where('user_id', $userId)
            ->where('ms_status', 'Approved')
            ->whereNotNull('leave_type_id')
            ->selectRaw('leave_type_id, COALESCE(SUM(total_days), 0) as used_days')
            ->groupBy('leave_type_id')
            ->pluck('used_days', 'leave_type_id');

        $balances = [];

        $customBalancesByTypeId = UserLeaveBalance::query()
            ->where('user_id', $userId)
            ->select(['leave_type_id', 'max_per_year', 'adjustment'])
            ->get()
            ->keyBy('leave_type_id');

        foreach ($leaveTypes as $leaveType) {
            $usedDays = (float) ($usedDaysByTypeId[$leaveType->id] ?? 0);

            $customBalance = $customBalancesByTypeId->get($leaveType->id);
            $effectiveEntitlement = $customBalance
                ? (float) $customBalance->max_per_year
                : (float) $leaveType->entitlement_days;
            $effectiveEntitlement += $customBalance ? (float) $customBalance->adjustment : 0;

            $balances[$leaveType->id] = max(0, $effectiveEntitlement - $usedDays);
        }

        return $balances;
    }

    public function uploadProfilePicture(Request $request)
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = Auth::user();

        if (!$user instanceof User) {
            return back()->with('error', 'Unable to identify authenticated user.');
        }

        // Delete old profile picture if it exists
        if ($user->profile_picture && file_exists(public_path($user->profile_picture))) {
            unlink(public_path($user->profile_picture));
        }

        // Upload new profile picture
        $file = $request->file('profile_picture');
        $filename = 'profile_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('uploads/profiles'), $filename);

        // Update user profile picture path
        $user->update([
            'profile_picture' => 'uploads/profiles/' . $filename,
        ]);

        return back()->with('success', 'Profile picture updated successfully!');
    }

    public function clockIn(Request $request)
    {
        $user = Auth::user();
        $clockInTime = Carbon::now();
        $shift = $this->resolveShiftForUser($user, $clockInTime);
        $attendanceDate = $shift['shift_date']->toDateString();
        $latestOnTime = $shift['on_time_until_at'];
        $remarks = $clockInTime->gt($latestOnTime) ? 'Late Clockin' : null;
        
        // Check if already clocked in for this shift date.
        $existingAttendance = Attendance::where('user_id', $user->id)
            ->where('date', $attendanceDate)
            ->first();

        if ($existingAttendance) {
            return back()->with('error', 'You have already clocked in for this shift.');
        }

        // Get location from request
        $location = $request->input('location', 'Location not available');

        $attendancePayload = [
            'user_id' => $user->id,
            'date' => $attendanceDate,
            'clock_in' => $clockInTime->format('H:i:s'),
            'clockIn_address' => $location,
            'status' => 'present',
            'remarks' => $remarks,
        ];

        $optionalShiftFields = [
            'shift_name' => $shift['name'],
            'shift_on_time_until' => $shift['on_time_until'],
            'shift_clock_out_after' => $shift['clock_out_after'],
            'shift_is_overnight' => $shift['is_overnight'],
        ];

        foreach ($optionalShiftFields as $column => $value) {
            if ($this->attendanceHasColumn($column)) {
                $attendancePayload[$column] = $value;
            }
        }

        Attendance::create($attendancePayload);

        return back()->with('success', 'Clocked in successfully!');
    }

    public function clockOut(Request $request)
    {
        $user = Auth::user();
        
        $attendance = Attendance::where('user_id', $user->id)
            ->whereNull('clock_out')
            ->orderByDesc('date')
            ->first();

        if (!$attendance) {
            return back()->with('error', 'You need to clock in first.');
        }

        $shift = $this->resolveShiftForAttendance($user, $attendance);
        $clockOutAt = $shift['clock_out_after_at'];

        if ($attendance->clock_out) {
            return back()->with('error', 'You have already clocked out today.');
        }

        if (Carbon::now()->lt($clockOutAt)) {
            return back()->with('error', 'You can clock out only after ' . $clockOutAt->format('g:i A') . '.');
        }

        // Get location from request
        $location = $request->input('location', 'Location not available');
        
        $attendance->update([
            'clock_out' => Carbon::now()->format('H:i:s'),
            'clockOut_address' => $location,
        ]);

        return back()->with('success', 'Clocked out successfully!');
    }

    private function resolveShiftForUser($user, ?Carbon $referenceAt = null): array
    {
        $referenceAt = $referenceAt ?: Carbon::now();

        if ($this->isIpdDepartment($user)) {
            $ipdShift = $this->resolveIpdShift($referenceAt);

            if ($ipdShift !== null) {
                return $ipdShift;
            }
        }

        $defaultShift = config('attendance.shifts.default', []);

        $shift = array_merge(
            [
                'name' => 'General Shift',
                'on_time_until' => '09:30',
                'clock_out_after' => '15:00',
                'start_time' => '09:00',
                'end_time' => '15:00',
                'is_overnight' => false,
            ],
            is_array($defaultShift) ? $defaultShift : []
        );

        $shiftDate = $referenceAt->copy()->startOfDay();
        $startTime = $this->normalizeTime((string) ($shift['start_time'] ?? '09:00'), '09:00');
        $endTime = $this->normalizeTime((string) ($shift['end_time'] ?? '15:00'), '15:00');
        $onTimeUntil = $this->normalizeTime((string) $shift['on_time_until'], '09:30');
        $clockOutAfter = $this->normalizeTime((string) $shift['clock_out_after'], '15:00');
        $onTimeUntilAt = $this->timeForDate($shiftDate, $onTimeUntil);
        $clockOutAfterAt = $this->timeForDate($shiftDate, $clockOutAfter);

        return [
            'name' => (string) $shift['name'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'on_time_until' => $onTimeUntil,
            'clock_out_after' => $clockOutAfter,
            'is_overnight' => false,
            'shift_date' => $shiftDate,
            'on_time_until_at' => $onTimeUntilAt,
            'clock_out_after_at' => $clockOutAfterAt,
        ];
    }

    private function resolveShiftForAttendance($user, ?Attendance $attendance): array
    {
        if ($attendance && $attendance->shift_clock_out_after) {
            $shiftDate = Carbon::parse($attendance->date)->startOfDay();
            $defaultShift = $this->resolveShiftForUser($user, $shiftDate->copy()->setTime(9, 0, 0));
            $startTime = $defaultShift['start_time'];
            $endTime = $defaultShift['end_time'];
            $onTimeUntil = $this->normalizeTime((string) ($attendance->shift_on_time_until ?? $defaultShift['on_time_until']), $defaultShift['on_time_until']);
            $clockOutAfter = $this->normalizeTime((string) $attendance->shift_clock_out_after, '15:00');
            $isOvernight = (bool) $attendance->shift_is_overnight;
            $clockOutAfterAt = $this->timeForDate($shiftDate, $clockOutAfter);

            if ($isOvernight) {
                $clockOutAfterAt->addDay();
            }

            return [
                'name' => (string) ($attendance->shift_name ?: 'General Shift'),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'on_time_until' => $onTimeUntil,
                'clock_out_after' => $clockOutAfter,
                'is_overnight' => $isOvernight,
                'shift_date' => $shiftDate,
                'on_time_until_at' => $this->timeForDate($shiftDate, $onTimeUntil),
                'clock_out_after_at' => $clockOutAfterAt,
            ];
        }

        return $this->resolveShiftForUser($user, Carbon::now());
    }

    private function resolveIpdShift(Carbon $referenceAt): ?array
    {
        if (!Schema::hasTable('department_shifts')) {
            return null;
        }

        $shifts = DepartmentShift::query()
            ->where('department', 'IPD')
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        foreach ($shifts as $shift) {
            $startTime = $this->normalizeTime(substr((string) $shift->start_time, 0, 5), '08:00');
            $endTime = $this->normalizeTime(substr((string) $shift->end_time, 0, 5), '14:00');
            $onTimeUntil = $this->normalizeTime(substr((string) $shift->on_time_until, 0, 5), $startTime);
            $clockOutAfter = $this->normalizeTime(substr((string) $shift->clock_out_after, 0, 5), $endTime);
            [$startAt, $endAt] = $this->buildShiftWindow($referenceAt, $startTime, $endTime, (bool) $shift->is_overnight);

            if ($referenceAt->gte($startAt) && $referenceAt->lt($endAt)) {
                $onTimeUntilAt = $this->timeForDate($startAt->copy()->startOfDay(), $onTimeUntil);

                if ($onTimeUntilAt->lt($startAt)) {
                    $onTimeUntilAt->addDay();
                }

                $clockOutAfterAt = $this->timeForDate($startAt->copy()->startOfDay(), $clockOutAfter);

                if ($clockOutAfterAt->lte($startAt)) {
                    $clockOutAfterAt->addDay();
                }

                return [
                    'name' => 'IPD ' . (string) $shift->name . ' Shift',
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'on_time_until' => $onTimeUntil,
                    'clock_out_after' => $clockOutAfter,
                    'is_overnight' => (bool) $shift->is_overnight,
                    'shift_date' => $startAt->copy()->startOfDay(),
                    'on_time_until_at' => $onTimeUntilAt,
                    'clock_out_after_at' => $clockOutAfterAt,
                ];
            }
        }

        return null;
    }

    private function buildShiftWindow(Carbon $referenceAt, string $startTime, string $endTime, bool $isOvernight): array
    {
        $shiftDate = $referenceAt->copy()->startOfDay();
        $startAt = $this->timeForDate($shiftDate, $startTime);
        $endAt = $this->timeForDate($shiftDate, $endTime);

        if ($isOvernight || $endAt->lte($startAt)) {
            if ($referenceAt->lt($endAt)) {
                $startAt->subDay();
            } else {
                $endAt->addDay();
            }
        }

        return [$startAt, $endAt];
    }

    private function isIpdDepartment($user): bool
    {
        return strtoupper(trim((string) ($user->department ?? ''))) === 'IPD';
    }

    private function isHod($user): bool
    {
        return (int) ($user->role_id ?? 0) === 2;
    }

    private function isMs($user): bool
    {
        return (int) ($user->role_id ?? 0) === 1;
    }

    private function isAdmin($user): bool
    {
        return Auth::guard('admin')->check();
    }

    private function issueVerificationCode(User $user): string
    {
        $code = (string) random_int(100000, 999999);

        $user->update([
            'verification_code' => Hash::make($code),
            'verification_code_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        return $code;
    }

    private function sendVerificationCodeEmail(User $user, string $code): void
    {
        try {
            Mail::raw(
                "Your verification code is: {$code}\n\nThis code expires in 10 minutes.\nIf you did not request this, please ignore this email.",
                function ($message) use ($user) {
                    $message
                        ->to((string) $user->email)
                        ->subject('Device Verification Code');
                }
            );
        } catch (\Throwable $exception) {
            Log::error('Verification email failed to send', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $exception->getMessage(),
            ]);

            if (app()->environment('local')) {
                session()->flash('verification_code_debug', $code);
                return;
            }

            throw $exception;
        }
    }

    private function sendLeaveStatusEmail(LeaveRequest $leaveRequest, string $status, string $processedBy): bool
    {
        $leaveRequest->loadMissing('user');

        if (! $leaveRequest->user || ! filter_var($leaveRequest->user->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $subject = "Leave Request {$status}";
        $rejectionLine = '';

        if (strcasecmp($status, 'Rejected') === 0) {
            $rejectionLine = "Rejection reason: " . ($leaveRequest->rejection_reason ?: 'No reason provided.') . "\n";
        }

        $body = "Hello {$leaveRequest->user->name},\n\n"
            . "Your leave request from {$leaveRequest->start_date->format('Y-m-d')} to {$leaveRequest->end_date->format('Y-m-d')} "
            . "has been {$status} by {$processedBy}.\n\n"
            . "Leave type: {$leaveRequest->leaveType?->name}\n"
            . "Reason: {$leaveRequest->reason}\n"
            . $rejectionLine
            . "\n"
            . "If you have any questions, please contact your department.\n\n"
            . "Regards,\nAttendance Management System";

        try {
            Mail::raw($body, function ($message) use ($leaveRequest, $subject) {
                $message
                    ->to((string) $leaveRequest->user->email)
                    ->subject($subject);
            });

            return true;
        } catch (\Throwable $exception) {
            Log::error('Leave status email failed to send', [
                'leave_request_id' => $leaveRequest->id,
                'user_id' => $leaveRequest->user->id,
                'email' => $leaveRequest->user->email,
                'status' => $status,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function normalizeTime(string $time, string $fallback): string
    {
        if (!preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            return $fallback;
        }

        [$hour, $minute] = array_map('intval', explode(':', $time));

        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return $fallback;
        }

        return sprintf('%02d:%02d', $hour, $minute);
    }

    private function timeToday(string $time): Carbon
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return Carbon::today()->setTime($hour, $minute, 0);
    }

    private function timeForDate(Carbon $date, string $time): Carbon
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $date->copy()->setTime($hour, $minute, 0);
    }

    private function attendanceHasColumn(string $column): bool
    {
        static $columnLookup = [];

        if (!array_key_exists($column, $columnLookup)) {
            $columnLookup[$column] = Schema::hasTable('attendances')
                && Schema::hasColumn('attendances', $column);
        }

        return $columnLookup[$column];
    }

    private function syncDepartmentsFromUsers(): void
    {
        $departmentNames = User::query()
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->pluck('department')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '' && !filter_var($name, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();

        foreach ($departmentNames as $departmentName) {
            Department::firstOrCreate(
                ['name' => $departmentName],
                ['status' => 'Active']
            );
        }

        $departmentsByNormalizedName = Department::query()
            ->get(['id', 'name'])
            ->keyBy(fn ($department) => strtolower(trim((string) $department->name)));

        $hodUsers = User::query()
            ->where('role_id', 2)
            ->whereRaw("LOWER(COALESCE(status, 'active')) = ?", ['active'])
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->orderBy('id')
            ->get(['id', 'department']);

        foreach ($hodUsers as $hodUser) {
            $normalizedName = strtolower(trim((string) $hodUser->department));
            $department = $departmentsByNormalizedName->get($normalizedName);

            if ($department) {
                Department::where('id', $department->id)->update(['hod_user_id' => $hodUser->id]);
            }
        }
    }
}


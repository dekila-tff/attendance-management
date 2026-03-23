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
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function showAdminLogin()
    {
        return view('admin_login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // Try to authenticate using the username field as email
        $credentials = [
            'email' => $request->username,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['username' => 'The provided credentials do not match our records.'])->withInput($request->only('username'));
    }

    public function adminLogin(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $admin = DB::table('admins')
            ->where('username', $request->username)
            ->first();

        if (! $admin || ! Hash::check($request->password, (string) $admin->password)) {
            return back()->withErrors([
                'username' => 'The provided credentials do not match our records.',
            ])->withInput($request->only('username'));
        }

        $user = User::query()
            ->where('eid', (string) $admin->username)
            ->orWhere('email', (string) $admin->username)
            ->first();

        if (! $user) {
            return back()->withErrors([
                'username' => 'Admin account is not linked to any user account.',
            ])->withInput($request->only('username'));
        }

        Auth::login($user, $request->filled('remember'));

        $request->session()->regenerate();

        if (!$this->isAdmin(Auth::user())) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'username' => 'This account is not allowed to use admin login.',
            ])->withInput($request->only('username'));
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect(route('login'));
    }

    public function dashboard(Request $request)
    {
        $user = Auth::user();

        if ($this->isAdmin($user)) {
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
            $leaveBalanceEmployees = collect();
            $leaveBalanceTypes = collect();
            $leaveBalanceRows = collect();
            $leaveBalanceYear = (int) Carbon::now()->year;

            if ($activeSection === 'roles-permissions') {
                $managedRoles = Role::query()->orderByDesc('id')->get();
                $managedPermissions = Permission::query()->orderByDesc('id')->get();
                $roleAssignableUsers = User::query()
                    ->orderBy('name')
                    ->get(['id', 'name', 'eid', 'email', 'role_id']);

                if ($selectedRoleIdForPermissions > 0) {
                    $selectedRole = Role::query()->with('permissions:id')->find($selectedRoleIdForPermissions);
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

                    $usersQuery->where(function ($query) use ($term) {
                        $query->where('name', 'like', "%{$term}%")
                            ->orWhere('eid', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%")
                            ->orWhere('department', 'like', "%{$term}%")
                            ->orWhere('designation', 'like', "%{$term}%");
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
                    ->with(['user:id,name,eid,department'])
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
                'leaveBalanceEmployees' => $leaveBalanceEmployees,
                'leaveBalanceTypes' => $leaveBalanceTypes,
                'leaveBalanceRows' => $leaveBalanceRows,
                'leaveBalanceYear' => $leaveBalanceYear,
            ]);
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
        
        return view('dashboard_employee', [
            'user' => $user,
            'attendance' => $attendance,
            'clockOutLocked' => $clockOutLocked,
            'clockOutUnlockTime' => $clockOutAt->format('g:i A'),
            'shiftName' => $shift['name'],
            'isHod' => $this->isHod($user),
            'isMs' => $this->isMs($user),
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
                Rule::unique('users', 'eid')->ignore($user->id),
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
            ->route('dashboard', ['section' => 'user-management'])
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
            ->route('dashboard', ['section' => 'user-management'])
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
                ->route('dashboard', ['section' => 'user-management'])
                ->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['status' => $newStatus]);

        return redirect()
            ->route('dashboard', ['section' => 'user-management'])
            ->with('success', 'User status updated to ' . $newStatus . '.');
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
            ->route('dashboard', ['section' => 'department-hod-management'])
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
            ->route('dashboard', ['section' => 'department-hod-management'])
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
            ->route('dashboard', ['section' => 'department-hod-management'])
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
            ->route('dashboard', ['section' => 'department-hod-management'])
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
            ->route('dashboard', ['section' => 'roles-permissions'])
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
            ->route('dashboard', ['section' => 'roles-permissions'])
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
            ->route('dashboard', [
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
            ->route('dashboard', ['section' => 'roles-permissions'])
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
            ->route('dashboard', ['section' => 'leave-types'])
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
            ->route('dashboard', ['section' => 'leave-types'])
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
            ->route('dashboard', ['section' => 'leave-types'])
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
            ->route('dashboard', ['section' => 'leave-balance'])
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
            ->route('dashboard', ['section' => 'leave-balance'])
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
            ->route('dashboard', ['section' => 'leave-balance'])
            ->with('success', 'All leave balance adjustments were reset successfully.');
    }

    public function hodLeaveRequests(Request $request)
    {
        $user = Auth::user();

        if (!$this->isHod($user)) {
            abort(403, 'Only HoD can view employee leave requests.');
        }

        $leaveRequests = LeaveRequest::query()
            ->with(['user', 'leaveType'])
            ->where('submit_to', 'HoD')
            ->whereHas('user', function ($query) use ($user) {
                $query->where('department', $user->department)
                    ->where('id', '!=', $user->id);
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('hod_leave_requests', [
            'user' => $user,
            'leaveRequests' => $leaveRequests,
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
        ]);

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
            ]);

            return back()->with('success', 'Leave request forwarded to MS.');
        }

        $leaveRequest->update([
            'hod_status' => 'Rejected',
            'ms_status' => 'Rejected',
        ]);

        return back()->with('success', 'Leave request rejected by HoD.');
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
            ->where('hod_status', 'Forwarded')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('ms_leave_requests', [
            'user' => $user,
            'leaveRequests' => $leaveRequests,
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

        $isForwardedToMs = strtoupper(trim((string) $leaveRequest->submit_to)) === 'MS'
            && strtoupper(trim((string) $leaveRequest->hod_status)) === 'FORWARDED';

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

            return back()->with('success', 'Leave request approved by MS.');
        }

        $leaveRequest->update([
            'ms_status' => 'Rejected',
        ]);

        return back()->with('success', 'Leave request rejected by MS.');
    }

    public function profile()
    {
        $user = Auth::user();
        
        return view('profile', [
            'user' => $user,
        ]);
    }

    public function attendanceHistory(Request $request)
    {
        $validated = $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $user = Auth::user();

        $historyQuery = Attendance::where('user_id', $user->id)
            ->orderByDesc('date');

        if (!empty($validated['from_date'])) {
            $historyQuery->whereDate('date', '>=', $validated['from_date']);
        }

        if (!empty($validated['to_date'])) {
            $historyQuery->whereDate('date', '<=', $validated['to_date']);
        }

        $attendances = $historyQuery->paginate(15)->withQueryString();

        return view('attendance_history', [
            'user' => $user,
            'attendances' => $attendances,
            'filters' => $validated,
        ]);
    }

    public function showApplyLeave()
    {
        $user = Auth::user();
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
            'leaveTypes' => $leaveTypes,
            'balances' => $balances,
            'leaveHistory' => $leaveHistory,
        ]);
    }

    public function applyLeave(Request $request)
    {
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
            'submit_to' => ['required', Rule::in(['HoD', 'MS'])],
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

        $user = Auth::user();
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
            'submit_to' => $validated['submit_to'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'balance' => $balanceAfterRequest,
            'reason' => $validated['reason'],
            'prescription' => $prescriptionPath,
            'hod_status' => $validated['submit_to'] === 'HoD' ? 'Pending' : 'Forwarded',
            'ms_status' => 'Pending',
        ]);

        return redirect()->route('leave.create')->with('success', 'Leave request submitted successfully.');
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
        return (int) ($user->role_id ?? 0) === 1 || (bool) ($user->is_admin ?? false);
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

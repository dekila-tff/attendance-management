<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Attendance;
use App\Models\DepartmentShift;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('login');
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

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect(route('login'));
    }

    public function dashboard()
    {
        $user = Auth::user();
        
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
        ]);
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

        if ($entitlement > 0 && $totalDays > $availableBalance) {
            return back()->withInput()->with('error', 'Insufficient leave balance for the selected leave type.');
        }

        $balanceAfterRequest = $entitlement > 0
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

        foreach ($leaveTypes as $leaveType) {
            $usedDays = (float) ($usedDaysByTypeId[$leaveType->id] ?? 0);
            $balances[$leaveType->id] = max(0, (float) $leaveType->entitlement_days - $usedDays);
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
        
        Attendance::create([
            'user_id' => $user->id,
            'date' => $attendanceDate,
            'shift_name' => $shift['name'],
            'shift_start_time' => $shift['start_time'],
            'shift_end_time' => $shift['end_time'],
            'shift_on_time_until' => $shift['on_time_until'],
            'shift_clock_out_after' => $shift['clock_out_after'],
            'shift_is_overnight' => $shift['is_overnight'],
            'clock_in' => $clockInTime->format('H:i:s'),
            'clockIn_address' => $location,
            'status' => 'present',
            'remarks' => $remarks,
        ]);

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
            $startTime = $this->normalizeTime((string) ($attendance->shift_start_time ?? '09:00'), '09:00');
            $endTime = $this->normalizeTime((string) ($attendance->shift_end_time ?? '15:00'), '15:00');
            $onTimeUntil = $this->normalizeTime((string) ($attendance->shift_on_time_until ?? $startTime), $startTime);
            $clockOutAfter = $this->normalizeTime((string) $attendance->shift_clock_out_after, '15:00');
            $isOvernight = (bool) $attendance->shift_is_overnight;
            $startAt = $this->timeForDate($shiftDate, $startTime);
            $clockOutAfterAt = $this->timeForDate($shiftDate, $clockOutAfter);

            if ($isOvernight && $clockOutAfterAt->lte($startAt)) {
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
}

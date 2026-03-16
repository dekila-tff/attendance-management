<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

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
        $user = Auth::user();
        
        // Record clock out time for today's attendance
        if ($user) {
            $today = Carbon::today();
            $attendance = Attendance::where('user_id', $user->id)
                ->where('date', $today)
                ->first();

            if ($attendance && !$attendance->clock_out) {
                // Get location from request
                $location = $request->input('location', 'Location not available');
                
                $attendance->update([
                    'clock_out' => Carbon::now()->format('H:i:s'),
                    'clockOut_address' => $location,
                ]);
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect(route('login'));
    }

    public function dashboard()
    {
        $user = Auth::user();
        $isIpdDepartment = strtoupper(trim((string) ($user->department ?? ''))) === 'IPD';
        $clockOutLocked = !$isIpdDepartment && Carbon::now()->lt(Carbon::today()->setTime(15, 0, 0));
        
        // Get today's attendance record
        $today = Carbon::today();
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
        
        return view('dashboard_employee', [
            'user' => $user,
            'attendance' => $attendance,
            'clockOutLocked' => $clockOutLocked,
        ]);
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
        $today = Carbon::today();
        $clockInTime = Carbon::now();
        $latestOnTime = Carbon::today()->setTime(9, 30, 0);
        $remarks = $clockInTime->gt($latestOnTime) ? 'Late Clockin' : null;
        
        // Check if already clocked in today
        $existingAttendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($existingAttendance) {
            return back()->with('error', 'You have already clocked in today.');
        }

        // Get location from request
        $location = $request->input('location', 'Location not available');
        
        Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
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
        $today = Carbon::today();
        $isIpdDepartment = strtoupper(trim((string) ($user->department ?? ''))) === 'IPD';
        
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            return back()->with('error', 'You need to clock in first.');
        }

        if ($attendance->clock_out) {
            return back()->with('error', 'You have already clocked out today.');
        }

        if (!$isIpdDepartment && Carbon::now()->lt(Carbon::today()->setTime(15, 0, 0))) {
            return back()->with('error', 'You can clock out only after 3:00 PM.');
        }

        // Get location from request
        $location = $request->input('location', 'Location not available');
        
        $attendance->update([
            'clock_out' => Carbon::now()->format('H:i:s'),
            'clockOut_address' => $location,
        ]);

        return back()->with('success', 'Clocked out successfully!');
    }
}

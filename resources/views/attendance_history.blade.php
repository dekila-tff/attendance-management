@extends('layouts.app')

@section('title', 'Attendance History')

@section('content')
    <div class="employee-page">
        <div class="employee-layout">
            <aside class="employee-sidebar">
                <div class="employee-profile">
                    @if($user->profile_picture && file_exists(public_path($user->profile_picture)))
                        <img src="{{ asset($user->profile_picture) }}" alt="Profile Picture" class="employee-avatar-image">
                    @else
                        <div class="employee-avatar-fallback">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    @endif

                    <div class="employee-profile-meta">
                        <h2>{{ $user->name }}</h2>
                        <p>{{ $user->eid ?? 'N/A' }}</p>
                    </div>

                    @include('auth.notification_bell_widget')
                </div>

                <nav class="employee-nav">
                    <a href="{{ route('dashboard') }}">My Dashboard</a>

                    @if($isMs)
                        <a href="{{ route('ms.leave.requests') }}">MS Dashboard</a>
                    @else
                        <span class="disabled">MS Dashboard</span>
                    @endif

                    @if($isHod)
                        <a href="{{ route('hod.leave.requests') }}" class="leave-approve-link" style="position:relative; display:block;">
                            <span style="display:inline-block;">Leave Approve</span>
                            @if(!empty($leaveApproveCount) && $leaveApproveCount > 0)
                                <span class="leave-approve-badge" style="position:absolute; right:16px; top:50%; transform:translateY(-50%); background:#fff; color:#2f3f4a; display:inline-flex; align-items:center; justify-content:center; border-radius:16px; font-size:14px; font-weight:700; box-shadow:0 1px 4px rgba(0,0,0,0.08); padding:0 10px; height:auto; min-width:0;">{{ $leaveApproveCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('hod.staff.list') }}">Staff List</a>
                    @else
                        <span class="disabled">Leave Approve</span>
                    @endif

                    <a href="{{ route('attendance.history') }}" class="active">Attendance</a>
                    <a href="{{ route('leave.create') }}">Leave</a>
                    <a href="{{ route('adhoc.requests') }}">Adhoc Request</a>
                    <a href="{{ route('tour.records') }}">Tour</a>
                </nav>

                <form method="POST" action="{{ route('logout') }}" class="sidebar-logout-form">
                    @csrf
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </aside>

            <main class="employee-main">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-white">Attendance History</h1>
                    <p class="text-white/70 mt-1">Review your daily attendance records</p>
                </div>

                <div class="mb-6">
                    <div class="mb-4">
                        <h2 class="text-xl font-bold text-white">{{ $currentMonth }} Summary</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="card-backdrop rounded-xl p-6">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-white/70 text-sm mb-2">Present Days</p>
                                    <p class="text-3xl font-bold text-green-400">{{ $presentDays }}</p>
                                </div>
                                <div class="text-4xl">✓</div>
                            </div>
                        </div>
                        <div class="card-backdrop rounded-xl p-6">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-white/70 text-sm mb-2">Late</p>
                                    <p class="text-3xl font-bold text-orange-400">{{ $lateDays }}</p>
                                </div>
                                <div class="text-4xl">⏰</div>
                            </div>
                        </div>
                        <div class="card-backdrop rounded-xl p-6">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-white/70 text-sm mb-2">Absent Days</p>
                                    <p class="text-3xl font-bold text-red-400">{{ $absentDays }}</p>
                                </div>
                                <div class="text-4xl">✕</div>
                            </div>
                        </div>
                        <div class="card-backdrop rounded-xl p-6">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-white/70 text-sm mb-2">Leave Days</p>
                                    <p class="text-3xl font-bold text-yellow-400">{{ $leaveDays }}</p>
                                </div>
                                <div class="text-4xl">⏱️</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-backdrop rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-white/5 border-b border-white/10">
                                <tr>
                                    <th class="px-6 py-3 text-sm text-white/70 font-medium">Date</th>
                                    <th class="px-6 py-3 text-sm text-white/70 font-medium">Clock In</th>
                                    <th class="px-6 py-3 text-sm text-white/70 font-medium">Clock Out</th>
                                    <th class="px-6 py-3 text-sm text-white/70 font-medium">Status</th>
                                    <th class="px-6 py-3 text-sm text-white/70 font-medium">Remarks</th>
                                    <th class="px-6 py-3 text-sm text-white/70 font-medium">Clock In Location</th>
                                    <th class="px-6 py-3 text-sm text-white/70 font-medium">Clock Out Location</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendances as $attendance)
                                    <tr class="border-b border-white/10">
                                        <td class="px-6 py-4 text-white">{{ \Carbon\Carbon::parse($attendance->date)->format('M d, Y') }}</td>
                                        <td class="px-6 py-4 text-blue-300">
                                            {{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('h:i A') : '--:--' }}
                                        </td>
                                        <td class="px-6 py-4 text-orange-300">
                                            {{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('h:i A') : '--:--' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($attendance->status === 'leave')
                                                <span class="text-blue-300">Leave</span>
                                            @elseif($attendance->clock_out)
                                                <span class="text-green-400">{{ ucfirst($attendance->status) }}</span>
                                            @elseif($attendance->clock_in)
                                                <span class="text-yellow-300">{{ ucfirst($attendance->status) }}</span>
                                            @else
                                                <span class="text-red-300">Absent</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-white/80">{{ $attendance->remarks ?: '-' }}</td>
                                        <td class="px-6 py-4 text-white/80">{{ $attendance->clockIn_address ?: '-' }}</td>
                                        <td class="px-6 py-4 text-white/80">{{ $attendance->clockOut_address ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-8 text-center text-white/60">
                                            No attendance records found for the selected period.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 border-t border-white/10">
                        {{ $attendances->links() }}
                    </div>
                </div>
            </main>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .employee-page {
        min-height: 100vh;
    }

    .employee-layout {
        display: flex;
        min-height: 100vh;
        max-width: 1700px;
        margin: 0 auto;
    }

    .employee-sidebar {
        width: 280px;
        min-width: 280px;
        background: #2f3f4a;
        padding: 24px 20px;
    }

    .employee-profile {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
    }

    .employee-avatar-image,
    .employee-avatar-fallback {
        width: 48px;
        height: 48px;
        border-radius: 999px;
        object-fit: cover;
    }

    .employee-avatar-fallback {
        background: #f97316;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
    }

    .employee-profile-meta h2 {
        margin: 0;
        color: #fff;
        font-size: 20px;
        font-weight: 700;
        line-height: 1.1;
    }

    .employee-profile-meta p {
        margin: 2px 0 0;
        color: #cbd5e1;
        font-size: 13px;
    }

    .employee-nav {
        display: grid;
        gap: 6px;
    }

    .employee-nav a,
    .employee-nav span {
        display: block;
        padding: 10px 12px;
        border-radius: 8px;
        color: #e2e8f0;
        text-decoration: none;
        font-size: 16px;
        font-weight: 500;
    }

    .employee-nav a:hover {
        background: rgba(148, 163, 184, 0.18);
    }

    .employee-nav a.active {
        background: rgba(20, 184, 166, 0.22);
        color: #fff;
    }

    .employee-nav span.disabled {
        color: #94a3b8;
    }

    .sidebar-logout-form {
        margin-top: 6px;
    }

    .logout-btn {
        width: 100%;
        border: 0;
        background: transparent;
        color: #e2e8f0;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        text-align: left;
        transition: background 0.15s ease, color 0.15s ease;
    }

    .logout-btn:hover {
        background: rgba(148, 163, 184, 0.18);
        color: #fff;
    }

    .employee-main {
        flex: 1;
        padding: 20px;
    }

    @media (max-width: 900px) {
        .employee-layout {
            display: block;
        }

        .employee-sidebar {
            width: 100%;
            min-width: 0;
        }
    }
</style>
@endpush

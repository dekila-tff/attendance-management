@extends('layouts.app')

@section('title', 'Employee Dashboard')

@section('content')
    @php
        $isCheckedIn = (bool) ($attendance && $attendance->clock_in);
        $isCheckedOut = (bool) ($attendance && $attendance->clock_out);
        $timelineProgress = $isCheckedOut ? 100 : ($isCheckedIn ? 55 : 0);
        $statusLabel = $attendance ? ucfirst((string) $attendance->status) : 'Not Checked';
        $statusClass = $isCheckedOut ? 'status-ok' : ($isCheckedIn ? 'status-warn' : 'status-neutral');
        $clockInDateLabel = now()->format('d M Y');
    @endphp

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
                        <p>{{ $user->email }}</p>
                    </div>
                </div>

                <nav class="employee-nav">
                    <a href="{{ route('dashboard') }}">My Dashboard</a>

                    @if($isMs)
                        <a href="{{ route('ms.leave.requests') }}">MS Dashboard</a>
                    @else
                        <span class="disabled">MS Dashboard</span>
                    @endif

                    @if($isHod)
                        <a href="{{ route('hod.leave.requests') }}">Leave Request</a>
                    @else
                        <span class="disabled">Leave Request</span>
                    @endif

                    <a href="{{ route('attendance.history') }}">Attendance</a>
                    <a href="{{ route('leave.create') }}">Leave</a>
                    <a href="{{ route('profile') }}">Profile</a>
                </nav>

                <form method="POST" action="{{ route('logout') }}" class="sidebar-logout-form">
                    @csrf
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </aside>

            <main class="employee-main">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if(session('error'))
                    <div class="alert alert-error">{{ session('error') }}</div>
                @endif

                <section class="employee-cards">
                    <article class="card">
                        <h1>Welcome, <span>{{ $user->name }}</span></h1>
                        <h2>Attendance</h2>
                        <p class="subtle">Track your daily activity</p>

                        <div class="action-grid">
                            <form method="POST" action="{{ route('clock.in') }}" id="clockInForm" onsubmit="return captureLocation(event, 'clockInForm', 'clockInLocation', 'clockInButton')">
                                @csrf
                                <input type="hidden" name="location" id="clockInLocation" value="Location not available">
                                <button
                                    type="submit"
                                    id="clockInButton"
                                    class="action-btn {{ $attendance ? 'is-disabled' : '' }}"
                                    @if($attendance) disabled @endif
                                >
                                    {{ $attendance ? 'Check-in Closed' : 'Check-in' }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('clock.out') }}" id="clockOutForm" onsubmit="return captureLocation(event, 'clockOutForm', 'clockOutLocation', 'clockOutButton')">
                                @csrf
                                <input type="hidden" name="location" id="clockOutLocation" value="Location not available">
                                <button
                                    type="submit"
                                    id="clockOutButton"
                                    class="action-btn {{ (!$attendance || $attendance->clock_out || $clockOutLocked) ? 'is-disabled' : '' }}"
                                    @if(!$attendance || $attendance->clock_out || $clockOutLocked) disabled @endif
                                >
                                    @if($attendance && $attendance->clock_out)
                                        Check-out Closed
                                    @elseif($clockOutLocked)
                                        Check-out After {{ $clockOutUnlockTime }}
                                    @else
                                        Check-out
                                    @endif
                                </button>
                            </form>
                        </div>

                        <div class="attendance-status">
                            <div class="status-title-row">
                                <span class="status-dot"></span>
                                <p class="{{ $statusClass }}">{{ $statusLabel }}</p>
                            </div>
                            <p>Check-in: {{ $attendance && $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('h:i A') : '--' }}</p>
                            <p>Check-out: {{ $attendance && $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('h:i A') : '--' }}</p>
                            <p class="subtle">Shift: {{ $shiftName }}</p>
                        </div>

                        <div class="timeline-head">
                            <h3>Today's Timeline</h3>
                            <span>{{ $timelineProgress }}%</span>
                        </div>

                        <div class="mb-6">
                            <progress value="{{ $timelineProgress }}" max="100" class="employee-progress h-4 w-full"></progress>
                        </div>

                        <div class="stat-grid">
                            <div class="stat-box">
                                <p class="value date-value">{{ $clockInDateLabel }}</p>
                            </div>
                            <div class="stat-box">
                                <p class="value">{{ $isCheckedIn ? 1 : 0 }}</p>
                                <p class="label">Morning</p>
                            </div>
                            <div class="stat-box">
                                <p class="value">{{ $isCheckedOut ? 1 : 0 }}</p>
                                <p class="label">Evening</p>
                            </div>
                        </div>

                        <div class="reminder-box">
                            {{ $isCheckedIn ? 'Good progress. Complete your check-out before shift ends.' : 'Reminder: Please check-in.' }}
                        </div>
                    </article>
                </section>
            </main>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .employee-page {
        min-height: 100vh;
        background: #eef3f6;
        color: #1e293b;
    }

    .employee-layout {
        display: flex;
        min-height: 100vh;
        max-width: 1600px;
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

    .employee-nav span.disabled {
        color: #94a3b8;
    }

    .employee-main {
        flex: 1;
        padding: 20px;
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

    .alert {
        border-radius: 10px;
        padding: 10px 12px;
        margin-bottom: 12px;
        border: 1px solid;
        font-size: 14px;
    }

    .alert-success {
        background: #ecfdf5;
        color: #047857;
        border-color: #a7f3d0;
    }

    .alert-error {
        background: #fef2f2;
        color: #b91c1c;
        border-color: #fecaca;
    }

    .employee-cards {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .card {
        background: #fff;
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
    }

    .card h1 {
        margin: 0 0 14px;
        font-size: 24px;
        font-weight: 800;
        color: #ea580c;
        line-height: 1.2;
    }

    .card h1 span {
        color: #2563eb;
    }

    .card h2 {
        margin: 0 0 8px;
        font-size: 22px;
        font-weight: 800;
        color: #1f2937;
    }

    .card .subtle {
        margin: 0 0 14px;
        font-size: 15px;
        color: #64748b;
    }

    .details-grid {
        display: grid;
        grid-template-columns: 150px 1fr;
        gap: 8px 14px;
        margin: 0;
    }

    .details-grid dt,
    .details-grid dd {
        margin: 0;
        font-size: 17px;
        line-height: 1.25;
    }

    .details-grid dt {
        font-weight: 700;
        color: #0f172a;
    }

    .details-grid dd {
        color: #1f2937;
    }

    .status-active {
        color: #2563eb;
        font-weight: 700;
    }

    .action-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 14px;
    }

    .action-btn {
        width: 100%;
        border: 0;
        border-radius: 12px;
        padding: 10px 14px;
        background: #fb923c;
        color: #fff;
        font-size: 18px;
        font-weight: 700;
        cursor: pointer;
        transition: background 0.15s ease;
    }

    .action-btn:hover {
        background: #f97316;
    }

    .action-btn.is-disabled,
    .action-btn:disabled {
        background: #fdba74;
        cursor: not-allowed;
    }

    .attendance-status {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        border-radius: 14px;
        padding: 12px 14px;
        margin-bottom: 14px;
    }

    .attendance-status p {
        margin: 4px 0;
        font-size: 17px;
        color: #334155;
    }

    .status-title-row {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 6px;
    }

    .status-dot {
        width: 22px;
        height: 22px;
        border-radius: 999px;
        border: 4px solid #10b981;
    }

    .status-ok,
    .status-warn,
    .status-neutral {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
    }

    .status-ok {
        color: #047857;
    }

    .status-warn {
        color: #b45309;
    }

    .status-neutral {
        color: #334155;
    }

    .timeline-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .timeline-head h3 {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
    }

    .timeline-head span {
        font-size: 14px;
        color: #64748b;
        font-weight: 600;
    }

    .employee-progress {
        appearance: none;
        border: 0;
        border-radius: 9999px;
        overflow: hidden;
        background: #e2e8f0;
    }

    .employee-progress::-webkit-progress-bar {
        background: #e2e8f0;
    }

    .employee-progress::-webkit-progress-value {
        background: #10b981;
    }

    .employee-progress::-moz-progress-bar {
        background: #10b981;
    }

    .stat-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 14px;
    }

    .stat-box {
        background: #f1f5f9;
        border-radius: 12px;
        padding: 12px;
        text-align: center;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        min-height: 110px;
    }

    .stat-box .value {
        margin: 0;
        font-size: 30px;
        color: #10b981;
        font-weight: 700;
    }

    .stat-box .value.date-value {
        font-size: 18px;
        line-height: 1.25;
    }

    .stat-box .label {
        margin: 2px 0 0;
        font-size: 14px;
        color: #475569;
    }

    .reminder-box {
        background: #fff7ed;
        border-left: 4px solid #f97316;
        border-radius: 8px;
        padding: 10px 12px;
        color: #9a3412;
        font-size: 15px;
    }

    @media (max-width: 1024px) {
        .employee-cards {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 1024px) {
        .employee-layout {
            flex-direction: column;
        }

        .employee-sidebar {
            width: 100%;
            min-width: 0;
        }
    }

    @media (max-width: 640px) {
        .employee-main {
            padding: 14px;
        }

        .action-grid {
            grid-template-columns: 1fr;
        }

        .details-grid {
            grid-template-columns: 1fr;
            gap: 4px;
        }

        .details-grid dt {
            margin-top: 8px;
        }

        .stat-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

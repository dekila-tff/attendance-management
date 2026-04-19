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
                        <p>{{ $user->eid ?? 'N/A' }}</p>
                    </div>
                    <!-- Notification Bell -->
                    <div class="notification-bell-container">
                        <div class="notification-bell" onclick="openNotificationModal()">
                            <svg width="28" height="28" fill="none" viewBox="0 0 24 24"><path d="M12 22a2 2 0 0 0 2-2H10a2 2 0 0 0 2 2Zm6-6V11a6 6 0 1 0-12 0v5l-2 2v1h16v-1l-2-2Z" stroke="#334155" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            @if($user->unreadNotifications->count() > 0)
                                <span class="notification-badge">{{ $user->unreadNotifications->count() }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Notification Modal (Unity Theme) -->
                    <div id="notificationModalOverlay" class="notification-modal-overlay" style="display:none;">
                        <div class="notification-modal unity-theme">
                            <div class="notification-modal-header">
                                <span>Notifications</span>
                                <button class="notification-modal-close" onclick="closeNotificationModal()">&times;</button>
                            </div>
                            <div class="notification-modal-body">
                                @if($user->unreadNotifications->count() === 0)
                                    <div class="notification-empty">No new notifications.</div>
                                @else
                                    <ul class="notification-list">
                                        @foreach($user->unreadNotifications as $notification)
                                            @php
                                                $notificationId = $notification->notifications_id ?? $notification->id;
                                            @endphp
                                            <li class="notification-item">
                                                <div class="notification-message">
                                                    {{ $notification->data['message'] ?? 'You have a new notification.' }}
                                                    <span class="notification-time">{{ $notification->created_at->diffForHumans() }}</span>
                                                </div>
                                                <form method="POST" action="{{ route('notifications.markAsRead', ['notification' => $notificationId]) }}">
                                                    @csrf
                                                    <button type="submit" class="mark-as-read-btn">Mark as read</button>
                                                </form>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>

                    <script>
                        function openNotificationModal() {
                            document.getElementById('notificationModalOverlay').style.display = 'flex';
                        }
                        function closeNotificationModal() {
                            document.getElementById('notificationModalOverlay').style.display = 'none';
                        }
                        // Close modal when clicking outside the modal box
                        document.addEventListener('click', function(event) {
                            var overlay = document.getElementById('notificationModalOverlay');
                            var modal = document.querySelector('.notification-modal');
                            var bell = document.querySelector('.notification-bell');
                            if (overlay.style.display === 'flex' && !modal.contains(event.target) && !bell.contains(event.target)) {
                                overlay.style.display = 'none';
                            }
                        });
                        // Optional: Close modal with Escape key
                        document.addEventListener('keydown', function(event) {
                            if (event.key === 'Escape') {
                                closeNotificationModal();
                            }
                        });
                    </script>
                    <style>
                        /* Unity Theme for Notification Modal */
                        .notification-modal.unity-theme {
                            background: #034a50;
                            border-radius: 18px;
                            box-shadow: 0 12px 40px rgba(15,23,42,0.28);
                            border: 2px solid #1f8a90;
                            color: #e2e8f0;
                        }
                        .notification-modal.unity-theme .notification-modal-header {
                            background: #1f8a90;
                            color: #fff;
                            border-top-left-radius: 16px;
                            border-top-right-radius: 16px;
                            border-bottom: 1px solid #2a9fa6;
                        }
                        .notification-modal.unity-theme .notification-modal-close {
                            color: #fff;
                        }
                        .notification-modal.unity-theme .notification-modal-close:hover {
                            color: #f87171;
                        }
                        .notification-modal.unity-theme .notification-modal-body {
                            background: #034a50;
                            color: #e2e8f0;
                        }
                        .notification-modal.unity-theme .notification-list {
                            padding: 0;
                            margin: 0;
                            list-style: none;
                        }
                        .notification-modal.unity-theme .notification-item {
                            background: transparent;
                            border-radius: 10px;
                            margin-bottom: 10px;
                            padding: 10px 12px;
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
                        }
                        .notification-modal.unity-theme .notification-message {
                            color: #e0e7ef;
                            font-size: 15px;
                        }
                        .notification-modal.unity-theme .notification-time {
                            display: block;
                            font-size: 12px;
                            color: #9ad6df;
                            margin-top: 2px;
                        }
                        .notification-modal.unity-theme .mark-as-read-btn {
                            background: linear-gradient(90deg, #1f909a 0%, #2aa8b0 100%);
                            color: #fff;
                            border: none;
                            border-radius: 8px;
                            padding: 5px 12px;
                            font-size: 12px;
                            font-weight: 700;
                            line-height: 1.2;
                            cursor: pointer;
                            transition: background 0.18s;
                        }
                        .notification-modal.unity-theme .mark-as-read-btn:hover {
                            background: linear-gradient(90deg, #2aa8b0 0%, #1f909a 100%);
                        }
                        .notification-modal.unity-theme .notification-empty {
                            color: #9ad6df;
                            text-align: center;
                            padding: 18px 0;
                        }
                    </style>
                </div>

                <nav class="employee-nav">
                    <a href="{{ route('dashboard') }}">My Dashboard</a>

                    @if($isMs)
                        <a href="{{ route('ms.leave.requests') }}" class="font-semibold" style="color: #fbbf24;">MS Dashboard</a>
                    @endif

                    @if($isHod)
                        <a href="{{ route('hod.leave.requests') }}" class="leave-approve-link" style="position:relative; display:block;">
                            <span style="display:inline-block;">Leave Approve</span>
                            @if(!empty($leaveApproveCount) && $leaveApproveCount > 0)
                                <span class="leave-approve-badge" style="position:absolute; right:16px; top:50%; transform:translateY(-50%); background:#fff; color:#2f3f4a; display:inline-flex; align-items:center; justify-content:center; border-radius:16px; font-size:14px; font-weight:700; box-shadow:0 1px 4px rgba(0,0,0,0.08); padding:0 10px; height:auto; min-width:0;">{{ $leaveApproveCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('hod.staff.list') }}">Staff List</a>
                    @endif

                    <a href="{{ route('attendance.history') }}">Attendance</a>
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
                @if(session('success') && !session('device_bound'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if(session('error'))
                    <div class="alert alert-error">{{ session('error') }}</div>
                @endif

                @if(session('device_bound'))
                    <div id="deviceBoundPopup" class="device-bound-popup">
                        <div class="device-bound-popup-content">
                            <h3>Device Bound</h3>
                            <p>{{ session('success') }}</p>
                            <button type="button" onclick="document.getElementById('deviceBoundPopup').style.display='none'">OK</button>
                        </div>
                    </div>
                @endif

                <section class="employee-cards">
                    <article class="card">
                        <h1>Welcome, <span>{{ $user->name }}</span></h1>
                        <h2>Attendance</h2>
                        <p class="subtle">Date: {{ $clockInDateLabel }}</p>
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
                                    {{ $attendance ? 'Completed' : 'Check-in' }}
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
                                        Completed
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


                        <div class="reminder-box">
                            {{ $isCheckedIn ? 'Good progress. Complete your check-out before shift ends.' : 'Reminder: Please check-in.' }}
                        </div>
                    </article>

                    <article class="card profile-info-card">
                        <h2>Personal Information</h2>
                        <p class="subtle">Your current employee details</p>

                        <div class="profile-info-list">
                            <div class="profile-info-row">
                                <span class="label">Full Name</span>
                                <span class="value">{{ $user->name }}</span>
                            </div>

                            <div class="profile-info-row">
                                <span class="label">Employee ID</span>
                                <span class="value">{{ $user->eid ?? 'N/A' }}</span>
                            </div>

                            <div class="profile-info-row">
                                <span class="label">Designation</span>
                                <span class="value value-designation">{{ $user->designation ?? 'Not Assigned' }}</span>
                            </div>

                            <div class="profile-info-row">
                                <span class="label">Department</span>
                                <span class="value">{{ $user->department ?? 'Not Assigned' }}</span>
                            </div>

                            <div class="profile-info-row">
                                <span class="label">Role</span>
                                <span class="value value-role">{{ $user->role_name }}</span>
                            </div>

                            <div class="profile-info-row">
                                <span class="label">Account Status</span>
                                <span>
                                    @if($user->out_of_station)
                                        <span class="status-badge status-badge-inactive">Out of Station</span>
                                    @else
                                        <span class="status-badge status-badge-active">Active</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </article>

                    @if(!$isMs)
                        <article class="card" id="my-tours">
                            <h2>My Tours</h2>
                            <p class="subtle">Recent out-of-station tour entries</p>

                            @if(($tourRecords ?? collect())->isEmpty())
                                <div class="reminder-box" style="margin-top: 16px;">No tours found yet.</div>
                            @else
                                <div class="profile-info-list" style="margin-top: 12px;">
                                    @foreach($tourRecords as $tour)
                                        @php
                                            $start = \Carbon\Carbon::parse($tour->start_date);
                                            $end = \Carbon\Carbon::parse($tour->end_date);
                                            $today = \Carbon\Carbon::today();
                                            $tourStatus = $today->lt($start) ? 'Upcoming' : ($today->gt($end) ? 'Completed' : 'Ongoing');
                                        @endphp
                                        <div class="profile-info-row" style="align-items: flex-start;">
                                            <span class="label">{{ $start->format('d M') }} - {{ $end->format('d M Y') }}</span>
                                            <span class="value" style="text-align: right; max-width: 60%;">
                                                <strong>{{ $tour->place }}</strong><br>
                                                <span class="subtle" style="display: inline-block; margin-top: 2px;">{{ $tour->department?->name ?? 'N/A' }} | {{ $tourStatus }}</span>
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </article>
                    @endif
                </section>
            </main>
        </div>
    </div>
@endsection

@push('styles')
<style>
    :root {
        --teal-dark: #08353b;
        --teal-darker: #062b31;
        --teal-light: #5eead4;
        --teal-soft: #99f6e4;
        --teal-border: rgba(94, 234, 212, 0.35);
    }

    .employee-page {
        min-height: 100vh;
        background: linear-gradient(160deg, #062a30 0%, #08353b 65%, #094149 100%);
        color: #dff8f5;
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
        background: rgba(6, 30, 35, 0.96);
        border-right: 1px solid var(--teal-border);
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

    .notification-bell-container {
        margin-left: auto;
    }

    .notification-bell {
        position: relative;
        width: 40px;
        height: 40px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        background: rgba(15, 23, 42, 0.28);
        border: 1px solid rgba(125, 211, 252, 0.45);
        box-shadow: 0 6px 16px rgba(2, 132, 199, 0.22);
        transition: transform 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
    }

    .notification-bell:hover {
        transform: translateY(-1px);
        background: rgba(14, 116, 144, 0.45);
        box-shadow: 0 8px 20px rgba(2, 132, 199, 0.32);
    }

    .notification-bell svg {
        width: 22px;
        height: 22px;
    }

    .notification-bell svg path {
        stroke: #e2e8f0;
    }

    .notification-badge {
        position: absolute;
        top: -6px;
        right: -7px;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f97316 0%, #ef4444 100%);
        border: 2px solid #2f3f4a;
        color: #fff;
        font-size: 11px;
        font-weight: 800;
        line-height: 1;
        box-shadow: 0 6px 14px rgba(239, 68, 68, 0.45);
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

    .employee-nav a.leave-approve-link {
        display: block;
    }

    .leave-approve-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        color: #ef4444;
    }

    .employee-nav a:hover {
        background: rgba(94, 234, 212, 0.16);
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
        background: rgba(94, 234, 212, 0.16);
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
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
        align-items: start;
    }

    .card {
        background: rgba(7, 51, 57, 0.76);
        border: 1px solid var(--teal-border);
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 12px 28px rgba(2, 20, 28, 0.24);
    }

    .card h1 {
        margin: 0 0 14px;
        font-size: 24px;
        font-weight: 800;
        color: var(--teal-light);
        line-height: 1.2;
    }

    .card h1 span {
        color: #cffafe;
    }

    .card h2 {
        margin: 0 0 8px;
        font-size: 22px;
        font-weight: 800;
        color: #e6fffb;
    }

    .card .subtle {
        margin: 0 0 14px;
        font-size: 15px;
        color: #a7f3eb;
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
        color: #ecfeff;
    }

    .details-grid dd {
        color: #d9fffa;
    }

    .status-active {
        color: var(--teal-light);
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
        background: linear-gradient(90deg, #0f766e 0%, #14b8a6 100%);
        color: #ecfeff;
        font-size: 18px;
        font-weight: 700;
        cursor: pointer;
        transition: background 0.15s ease;
    }

    .action-btn:hover {
        background: linear-gradient(90deg, #14b8a6 0%, #0f766e 100%);
    }

    .action-btn.is-disabled,
    .action-btn:disabled {
        background: rgba(148, 163, 184, 0.35);
        cursor: not-allowed;
    }

    .attendance-status {
        background: rgba(6, 42, 47, 0.82);
        border: 1px solid var(--teal-border);
        border-radius: 14px;
        padding: 12px 14px;
        margin-bottom: 14px;
    }

    .attendance-status p {
        margin: 4px 0;
        font-size: 17px;
        color: #dff8f5;
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
        border: 4px solid var(--teal-light);
    }

    .status-ok,
    .status-warn,
    .status-neutral {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
    }

    .status-ok {
        color: #5eead4;
    }

    .status-warn {
        color: #fbbf24;
    }

    .status-neutral {
        color: #cbd5e1;
    }

    .reminder-box {
        background: rgba(5, 35, 40, 0.78);
        border-left: 4px solid var(--teal-light);
        border-radius: 8px;
        padding: 10px 12px;
        color: #ccfbf1;
        font-size: 15px;
    }

    .device-bound-popup {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(15, 23, 42, 0.75);
        z-index: 50;
    }

    .device-bound-popup-content {
        background: #0f172a;
        border-radius: 18px;
        padding: 24px;
        max-width: 400px;
        width: 90%;
        text-align: center;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.4);
    }

    .device-bound-popup-content h3 {
        margin: 0 0 12px;
        font-size: 22px;
        color: #34d399;
    }

    .device-bound-popup-content p {
        margin: 0 0 18px;
        color: #e2e8f0;
        line-height: 1.6;
    }

    .device-bound-popup-content button {
        border: 0;
        border-radius: 9999px;
        background: #22c55e;
        color: #0f172a;
        font-weight: 700;
        padding: 10px 22px;
        cursor: pointer;
    }

    .profile-info-card {
        height: 100%;
    }

    .profile-info-list {
        display: grid;
        gap: 0;
        border-top: 1px solid rgba(148, 163, 184, 0.28);
    }

    .profile-info-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 10px 0;
        border-bottom: 1px solid rgba(148, 163, 184, 0.28);
    }

    .profile-info-row .label {
        font-size: 15px;
        color: #a7f3eb;
        font-weight: 600;
    }

    .profile-info-row .value {
        text-align: right;
        font-size: 20px;
        color: #ecfeff;
        font-weight: 700;
        word-break: break-word;
    }

    .profile-info-row .value-designation {
        color: #67e8f9;
    }

    .profile-info-row .value-role {
        color: #5eead4;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 14px;
        font-weight: 700;
        border: 1px solid;
    }

    .status-badge-active {
        color: #99f6e4;
        background: rgba(16, 185, 129, 0.16);
        border-color: rgba(94, 234, 212, 0.45);
    }

    .status-badge-inactive {
        color: #b91c1c;
        background: #fef2f2;
        border-color: #fecaca;
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

        .profile-info-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
        }

        .profile-info-row .value {
            text-align: left;
            font-size: 18px;
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
<style>
    .notification-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(30, 41, 59, 0.55);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        transition: background 0.2s;
    }
    .notification-modal {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 12px 40px rgba(15,23,42,0.18);
        width: 95vw;
        max-width: 400px;
        padding: 0;
        animation: notificationModalFadeIn 0.25s;
        display: flex;
        flex-direction: column;
    }
    @keyframes notificationModalFadeIn {
        from { transform: translateY(-30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .notification-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 22px 12px 22px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 20px;
        font-weight: 800;
        color: #1e293b;
    }
    .notification-modal-close {
        background: none;
        border: none;
        font-size: 28px;
        color: #64748b;
        cursor: pointer;
        font-weight: 700;
        line-height: 1;
        padding: 0 0 2px 0;
        transition: color 0.15s;
    }
    .notification-modal-close:hover {
        color: #ef4444;
    }
    .notification-modal-body {
        padding: 18px 22px 22px 22px;
        max-height: 60vh;
        overflow-y: auto;
    }
</style>
@endpush

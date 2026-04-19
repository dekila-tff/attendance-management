@extends('layouts.app')

@section('title', 'MS Attendance Logs')

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
                    <h1 class="font-black tracking-tight text-white" style="font-size: 32px; line-height: 1.1;">Employee Attendance Logs</h1>
                    <p class="mt-1 text-white/70">Clock-in and clock-out records visible to Medical Superintendent</p>
                </div>

                @if(session('success'))
                    <div class="mb-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                        {{ session('error') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="GET" action="{{ route('ms.attendance.logs') }}" class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label for="from_date" class="mb-2 block text-sm text-white/80">From Date</label>
                        <input
                            id="from_date"
                            name="from_date"
                            type="date"
                            value="{{ $filters['from_date'] ?? '' }}"
                            class="h-10 w-full rounded-lg border border-white/20 bg-white/5 px-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-400"
                        >
                    </div>

                    <div>
                        <label for="to_date" class="mb-2 block text-sm text-white/80">To Date</label>
                        <input
                            id="to_date"
                            name="to_date"
                            type="date"
                            value="{{ $filters['to_date'] ?? '' }}"
                            class="h-10 w-full rounded-lg border border-white/20 bg-white/5 px-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-400"
                        >
                    </div>

                    <div>
                        <label for="employee" class="mb-2 block text-sm text-white/80">Employee</label>
                        <input
                            id="employee"
                            name="employee"
                            type="text"
                            value="{{ $filters['employee'] ?? '' }}"
                            placeholder="Name, EID or email"
                            class="h-10 w-full rounded-lg border border-white/20 bg-white/5 px-3 text-white placeholder:text-white/40 focus:outline-none focus:ring-2 focus:ring-blue-400"
                        >
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="inline-flex h-10 items-center justify-center whitespace-nowrap rounded-lg border border-white/20 bg-white/5 px-4 font-medium text-white transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/40">
                            Filter
                        </button>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1300px] text-left text-sm text-white/85">
                        <thead class="bg-white/5 text-white/70">
                            <tr class="border-b border-white/10">
                                <th class="px-4 py-3 font-medium">Date</th>
                                <th class="px-4 py-3 font-medium">Employee</th>
                                <th class="px-4 py-3 font-medium">EID</th>
                                <th class="px-4 py-3 font-medium">Department</th>
                                <th class="px-4 py-3 font-medium">Clock In</th>
                                <th class="px-4 py-3 font-medium">Clock Out</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 font-medium">Remarks</th>
                                <th class="px-4 py-3 font-medium">Clock In Location</th>
                                <th class="px-4 py-3 font-medium">Clock Out Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendances as $attendance)
                                <tr class="border-b border-white/10 hover:bg-white/5">
                                    <td class="px-4 py-3">{{ \Carbon\Carbon::parse($attendance->date)->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 text-white font-medium">{{ $attendance->user?->name ?? 'Unknown' }}</td>
                                    <td class="px-4 py-3">{{ $attendance->user?->eid ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $attendance->user?->department ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('h:i A') : '--' }}</td>
                                    <td class="px-4 py-3">{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('h:i A') : '--' }}</td>
                                    <td class="px-4 py-3">{{ ucfirst((string) $attendance->status) }}</td>
                                    <td class="px-4 py-3">{{ $attendance->remarks ?: '-' }}</td>
                                    <td class="px-4 py-3">{{ $attendance->clockIn_address ?: '-' }}</td>
                                    <td class="px-4 py-3">{{ $attendance->clockOut_address ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-4 py-10 text-center text-white/60">No attendance logs found for the selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $attendances->links() }}
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
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: flex-start;
        max-width: 1400px;
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

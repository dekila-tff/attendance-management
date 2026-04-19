@extends('layouts.app')

@section('title', 'HoD Staff List')

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

                    <a href="{{ route('hod.leave.requests') }}" class="leave-approve-link" style="position:relative; display:block;">
                        <span style="display:inline-block;">Leave Approve</span>
                        @if(!empty($leaveApproveCount) && $leaveApproveCount > 0)
                            <span class="leave-approve-badge" style="position:absolute; right:16px; top:50%; transform:translateY(-50%); background:#fff; color:#2f3f4a; display:inline-flex; align-items:center; justify-content:center; border-radius:16px; font-size:14px; font-weight:700; box-shadow:0 1px 4px rgba(0,0,0,0.08); padding:0 10px; height:auto; min-width:0;">{{ $leaveApproveCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('hod.staff.list') }}" class="active">Staff List</a>

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
                <div class="max-w-6xl mx-auto">
                    <div class="card-backdrop rounded-xl p-8 mb-6">
                        <div class="mb-6">
                            <h1 class="text-3xl font-bold text-white">Staff List</h1>
                            <p class="text-white/70 mt-1">Department: {{ $staffScopeLabel ?? ($user->department ?? 'N/A') }}</p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="staff-list-table w-full min-w-[880px] text-sm text-white/85">
                                <thead class="bg-white/5 text-white/70">
                                    <tr class="border-b border-white/10">
                                        <th class="px-4 py-3 font-medium">Name</th>
                                        <th class="px-4 py-3 font-medium">EID</th>
                                        <th class="px-4 py-3 font-medium">Designation</th>
                                        <th class="px-4 py-3 font-medium">Duty Status</th>
                                        <th class="px-4 py-3 font-medium">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($staffMembers as $staff)
                                        <tr class="border-b border-white/10 hover:bg-white/5">
                                            <td class="px-4 py-3 text-white font-medium">{{ $staff->name }}</td>
                                            <td class="px-4 py-3">{{ $staff->eid ?? '-' }}</td>
                                            <td class="px-4 py-3">{{ $staff->designation ?? '-' }}</td>
                                            <td class="px-4 py-3">
                                                @if(($staff->duty_status ?? 'On Duty') === 'On Leave')
                                                    <span class="inline-flex rounded-full bg-red-500/20 px-2.5 py-1 text-xs font-semibold text-red-200">On Leave</span>
                                                @elseif(($staff->duty_status ?? 'On Duty') === 'On Tour')
                                                    <span class="inline-flex rounded-full bg-sky-500/20 px-2.5 py-1 text-xs font-semibold text-sky-200">On Tour</span>
                                                @else
                                                    <span class="inline-flex rounded-full bg-emerald-500/20 px-2.5 py-1 text-xs font-semibold text-emerald-200">On Duty</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-white/80">{{ $staff->remarks ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-10 text-center text-white/60">No staff members found in your department.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6">
                            {{ $staffMembers->links() }}
                        </div>
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

    .staff-list-table {
        table-layout: fixed;
    }

    .staff-list-table th,
    .staff-list-table td {
        width: 20%;
        vertical-align: middle;
        text-align: center;
    }

    .staff-list-table td {
        word-wrap: break-word;
        overflow-wrap: anywhere;
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

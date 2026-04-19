@extends('layouts.app')

@section('title', 'MS Leave Requests')

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
                        <a href="{{ route('ms.leave.requests') }}" class="font-semibold text-amber-400">MS Dashboard</a>
                    @endif

                    @if($isHod)
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
                <div class="mb-6">
                    <h1 class="font-black tracking-tight text-white" style="font-size: 32px; line-height: 1.1;">MS Dashboard</h1>
                </div>

                <div class="mb-8 grid w-full grid-cols-1 gap-6 md:grid-cols-3">
                <a href="{{ route('ms.staff.directory') }}" class="group relative overflow-hidden rounded-2xl bg-amber-700 p-8 text-white shadow-lg hover:shadow-xl transition flex items-center gap-6 border-2 border-amber-800">
                    <div class="flex h-20 w-20 items-center justify-center rounded-lg bg-amber-900 text-4xl flex-shrink-0">👥</div>
                    <div class="flex-grow">
                        <h2 class="text-2xl font-bold leading-tight">Total Staff</h2>
                        <p class="mt-1 text-sm text-white/90">View staff directory</p>
                    </div>
                    <p class="text-4xl font-black flex-shrink-0">{{ number_format((int) ($msQuickLinks['total_staff'] ?? 0)) }}</p>
                </a>

                <a href="{{ route('ms.leave.requests') }}#leave-requests" class="group relative overflow-hidden rounded-2xl bg-amber-700 p-8 text-white shadow-lg hover:shadow-xl transition flex items-center gap-6 border-2 border-amber-800">
                    <div class="flex h-20 w-20 items-center justify-center rounded-lg bg-amber-900 text-4xl flex-shrink-0">⏳</div>
                    <div class="flex-grow">
                        <h2 class="text-2xl font-bold leading-tight">Pending</h2>
                        <p class="mt-1 text-sm text-white/90">Requests awaiting your action</p>
                    </div>
                    <p class="text-4xl font-black flex-shrink-0">{{ number_format((int) ($msQuickLinks['pending'] ?? 0)) }}</p>
                </a>

                <a href="{{ route('ms.leave.requests') }}#leave-requests" class="group relative overflow-hidden rounded-2xl bg-amber-700 p-8 text-white shadow-lg hover:shadow-xl transition flex items-center gap-6 border-2 border-amber-800">
                    <div class="flex h-20 w-20 items-center justify-center rounded-lg bg-amber-900 text-4xl flex-shrink-0">📋</div>
                    <div class="flex-grow">
                        <h2 class="text-2xl font-bold leading-tight">Approved Leaves</h2>
                        <p class="mt-1 text-sm text-white/90">Recently approved</p>
                    </div>
                    <p class="text-4xl font-black flex-shrink-0">{{ number_format((int) ($msQuickLinks['approved'] ?? 0)) }}</p>
                </a>

                <a href="{{ route('adhoc.requests') }}" class="group relative overflow-hidden rounded-2xl bg-amber-700 p-8 text-white shadow-lg hover:shadow-xl transition flex items-center gap-6 border-2 border-amber-800">
                    <div class="flex h-20 w-20 items-center justify-center rounded-lg bg-amber-900 text-4xl flex-shrink-0">📌</div>
                    <div class="flex-grow">
                        <h2 class="text-2xl font-bold leading-tight">Adhoc Requests</h2>
                        <p class="mt-1 text-sm text-white/90">Manage adhoc duty requests</p>
                    </div>
                    <p class="text-4xl font-black flex-shrink-0">{{ number_format((int) ($msQuickLinks['adhoc_requests'] ?? 0)) }}</p>
                </a>

                <a href="{{ route('ms.leave.requests') }}#leave-requests" class="group relative overflow-hidden rounded-2xl bg-amber-700 p-8 text-white shadow-lg hover:shadow-xl transition flex items-center gap-6 border-2 border-amber-800">
                    <div class="flex h-20 w-20 items-center justify-center rounded-lg bg-amber-900 text-4xl flex-shrink-0">❌</div>
                    <div class="flex-grow">
                        <h2 class="text-2xl font-bold leading-tight">Rejected</h2>
                        <p class="mt-1 text-sm text-white/90">Requests you rejected</p>
                    </div>
                    <p class="text-4xl font-black flex-shrink-0">{{ number_format((int) ($msQuickLinks['rejected'] ?? 0)) }}</p>
                </a>

                <a href="{{ route('tour.records') }}" class="group relative overflow-hidden rounded-2xl bg-amber-700 p-8 text-white shadow-lg hover:shadow-xl transition flex items-center gap-6 border-2 border-amber-800">
                    <div class="flex h-20 w-20 items-center justify-center rounded-lg bg-amber-900 text-4xl flex-shrink-0">🧭</div>
                    <div class="flex-grow">
                        <h2 class="text-2xl font-bold leading-tight">Staff On Tour</h2>
                        <p class="mt-1 text-sm text-white/90">Current tours in your depts</p>
                    </div>
                    <p class="text-4xl font-black flex-shrink-0">{{ number_format((int) ($msQuickLinks['staff_on_tour'] ?? 0)) }}</p>
                </a>
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

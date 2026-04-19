@extends('layouts.app')

@section('title', 'Adhoc Requests')

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

                    <a href="{{ route('attendance.history') }}">Attendance</a>
                    <a href="{{ route('leave.create') }}">Leave</a>
                    <a href="{{ route('adhoc.requests') }}" class="active">Adhoc Request</a>
                    <a href="{{ route('tour.records') }}">Tour</a>
                </nav>

                <form method="POST" action="{{ route('logout') }}" class="sidebar-logout-form">
                    @csrf
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </aside>

            <main class="employee-main">
                @if(session('success'))
                    <div id="adhocSuccessPopup" class="adhoc-popup-overlay">
                        <div class="adhoc-popup-card">
                            <button type="button" class="adhoc-popup-close" onclick="closeAdhocSuccessPopup()">&times;</button>
                            <h3>Success</h3>
                            <p>{{ session('success') }}</p>
                            <div class="adhoc-popup-actions">
                                <button type="button" class="adhoc-popup-ok" onclick="closeAdhocSuccessPopup()">OK</button>
                            </div>
                        </div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-error">{{ $errors->first() }}</div>
                @endif

                <section class="card adhoc-card">
                    <h1>Adhoc Request</h1>
                    <p class="subtle">Submit request for meeting or emergency.</p>

                    <form method="POST" action="{{ route('adhoc.requests.store') }}" class="adhoc-grid">
                        @csrf

                        <div>
                            <label for="date">Date</label>
                            <input type="date" id="date" name="date" value="{{ old('date', now()->toDateString()) }}" required>
                        </div>

                        <div>
                            <label for="purpose">Purpose</label>
                            <select id="purpose" name="purpose" required>
                                <option value="meeting" @selected(old('purpose') === 'meeting')>Meeting</option>
                                <option value="emergency" @selected(old('purpose') === 'emergency')>Emergency</option>
                            </select>
                        </div>

                        <div class="adhoc-grid-full">
                            <label for="remark">Remark</label>
                            <textarea id="remark" name="remark" rows="3" placeholder="Add details based on selected purpose" required>{{ old('remark') }}</textarea>
                        </div>

                        <div class="adhoc-grid-full">
                            <button type="submit" class="save-btn">Submit Request</button>
                        </div>
                    </form>

                    <div class="adhoc-table-wrap">
                        <table class="adhoc-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Date</th>
                                    <th>Purpose</th>
                                    <th>Remark</th>
                                    <th>Updated At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($adhocRequests as $requestItem)
                                    <tr>
                                        <td>{{ $requestItem->name ?: ($user->name ?? '-') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($requestItem->date)->format('d M Y') }}</td>
                                        <td>{{ ucfirst((string) $requestItem->purpose) }}</td>
                                        <td>{{ $requestItem->remark }}</td>
                                        <td>{{ $requestItem->updated_at ? \Carbon\Carbon::parse($requestItem->updated_at)->format('d M Y h:i A') : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">No adhoc requests found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $adhocRequests->links() }}
                    </div>
                </section>
            </main>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .employee-page { min-height: 100vh; }
    .employee-layout { display: flex; min-height: 100vh; max-width: 1700px; margin: 0 auto; }
    .employee-sidebar { width: 280px; min-width: 280px; background: #2f3f4a; padding: 24px 20px; }
    .employee-profile { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
    .employee-avatar-image, .employee-avatar-fallback { width: 48px; height: 48px; border-radius: 999px; object-fit: cover; }
    .employee-avatar-fallback { background: #f97316; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; }
    .employee-profile-meta h2 { margin: 0; color: #fff; font-size: 20px; font-weight: 700; line-height: 1.1; }
    .employee-profile-meta p { margin: 2px 0 0; color: #cbd5e1; font-size: 13px; }
    .employee-nav { display: grid; gap: 6px; }
    .employee-nav a, .employee-nav span { display: block; padding: 10px 12px; border-radius: 8px; color: #e2e8f0; text-decoration: none; font-size: 16px; font-weight: 500; }
    .employee-nav a:hover { background: rgba(148, 163, 184, 0.18); }
    .employee-nav a.active { background: rgba(20, 184, 166, 0.22); color: #fff; }
    .employee-nav span.disabled { color: #94a3b8; }
    .sidebar-logout-form { margin-top: 6px; }
    .logout-btn { width: 100%; border: 0; background: transparent; color: #e2e8f0; border-radius: 8px; padding: 10px 12px; font-size: 16px; font-weight: 500; cursor: pointer; text-align: left; transition: background 0.15s ease, color 0.15s ease; }
    .logout-btn:hover { background: rgba(148, 163, 184, 0.18); color: #fff; }
    .employee-main { flex: 1; padding: 20px; }

    .card.adhoc-card {
        border-radius: 16px;
        background: rgba(8, 47, 52, 0.82);
        border: 1px solid rgba(255, 255, 255, 0.14);
        padding: 24px;
        color: #fff;
    }

    .adhoc-card h1 { margin: 0 0 6px; font-size: 2rem; font-weight: 800; }
    .adhoc-card .subtle { color: rgba(255,255,255,0.72); margin: 0 0 16px; }

    .adhoc-grid { display: grid; grid-template-columns: 1fr; gap: 12px; margin-bottom: 16px; }
    .adhoc-grid label { display: block; margin-bottom: 6px; color: rgba(255,255,255,0.86); }
    .adhoc-grid input, .adhoc-grid select, .adhoc-grid textarea {
        width: 100%; border-radius: 10px; border: 1px solid rgba(255,255,255,0.22); background: rgba(255,255,255,0.08);
        color: #fff; padding: 10px 12px; outline: none;
    }
    .adhoc-grid select option { color: #0f172a; }
    .adhoc-grid-full { grid-column: 1 / -1; }

    .save-btn {
        display: inline-flex; align-items: center; justify-content: center;
        border: 0; border-radius: 10px; background: #ff7a00; color: #fff; padding: 10px 16px;
        font-weight: 700; cursor: pointer;
    }

    .adhoc-table-wrap { overflow-x: auto; margin-top: 8px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.16); }
    .adhoc-table { width: 100%; border-collapse: collapse; min-width: 700px; }
    .adhoc-table th, .adhoc-table td { padding: 10px 12px; border-bottom: 1px solid rgba(255,255,255,0.12); text-align: left; }
    .adhoc-table thead { background: rgba(255,255,255,0.08); }

    .adhoc-popup-overlay {
        position: fixed;
        inset: 0;
        background: rgba(6, 78, 59, 0.35);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
        z-index: 1200;
    }

    .adhoc-popup-card {
        width: min(440px, 100%);
        border-radius: 16px;
        background: #ffffff;
        color: #0f172a;
        box-shadow: 0 24px 50px rgba(2, 6, 23, 0.35);
        border: 1px solid rgba(15, 23, 42, 0.08);
        padding: 20px 20px 18px;
        position: relative;
    }

    .adhoc-popup-card h3 { margin: 0 0 8px; font-size: 1.25rem; font-weight: 800; color: #0f766e; }
    .adhoc-popup-card p { margin: 0; color: #334155; line-height: 1.45; }

    .adhoc-popup-close {
        position: absolute;
        top: 8px;
        right: 10px;
        border: 0;
        background: transparent;
        color: #64748b;
        font-size: 26px;
        line-height: 1;
        cursor: pointer;
        padding: 2px;
    }

    .adhoc-popup-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 16px;
    }

    .adhoc-popup-ok {
        border: 0;
        border-radius: 10px;
        background: #0f766e;
        color: #fff;
        padding: 9px 16px;
        font-weight: 700;
        cursor: pointer;
    }

    @media (min-width: 900px) {
        .adhoc-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 900px) {
        .employee-layout { display: block; }
        .employee-sidebar { width: 100%; min-width: 0; }
    }
</style>
@endpush

@push('scripts')
<script>
    function closeAdhocSuccessPopup() {
        const popup = document.getElementById('adhocSuccessPopup');
        if (popup) {
            popup.style.display = 'none';
        }
    }
</script>
@endpush

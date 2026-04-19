@extends('layouts.app')

@section('title', 'Tour Records')

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
                    <a href="{{ route('adhoc.requests') }}">Adhoc Request</a>
                    <a href="{{ route('tour.records') }}" class="active">Tour</a>
                </nav>

                <form method="POST" action="{{ route('logout') }}" class="sidebar-logout-form">
                    @csrf
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </aside>

            <main class="employee-main">
                @if(session('success') && !session('tour_popup'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @php
                    $tourErrorMessage = session('error') ?: ($errors->any() ? $errors->first() : null);
                @endphp

                @if($tourErrorMessage)
                    <div id="tourErrorPopup" class="tour-saved-popup-overlay">
                        <div class="tour-saved-popup-card tour-error-popup-card">
                            <button type="button" class="tour-saved-popup-close" onclick="closeTourErrorPopup()">&times;</button>
                            <h3>Unable to Save Tour</h3>
                            <p>{{ $tourErrorMessage }}</p>
                            <div class="tour-saved-popup-actions">
                                <button type="button" class="tour-saved-popup-ok" onclick="closeTourErrorPopup()">OK</button>
                            </div>
                        </div>
                    </div>
                @endif

                @if(session('tour_popup'))
                    @php $savedTour = session('tour_popup'); @endphp
                    <div id="tourSavedPopup" class="tour-saved-popup-overlay">
                        <div class="tour-saved-popup-card">
                            <button type="button" class="tour-saved-popup-close" onclick="closeTourSavedPopup()">&times;</button>
                            <h3>Tour Record Saved</h3>
                            <p><strong>Place:</strong> {{ $savedTour['place'] ?? '-' }}</p>
                            <p><strong>Start:</strong> {{ $savedTour['start_date'] ?? '-' }}</p>
                            <p><strong>End:</strong> {{ $savedTour['end_date'] ?? '-' }}</p>
                            <p><strong>Purpose:</strong> {{ $savedTour['purpose'] ?? '-' }}</p>
                            <div class="tour-saved-popup-actions">
                                <button type="button" class="tour-saved-popup-ok" onclick="closeTourSavedPopup()">OK</button>
                            </div>
                        </div>
                    </div>
                @endif

                <section class="card tour-card">
                    <h1>Tour Records</h1>

                    <form method="POST" action="{{ route('tour.records.store') }}" enctype="multipart/form-data" id="tourForm">
                        @csrf

                        <div class="tour-grid">
                            <div>
                                <label for="place">Place</label>
                                <select id="place" name="place" required>
                                    <option value="" disabled @selected(!old('place'))>Select Dzongkhag</option>
                                    @foreach($dzongkhags as $dzongkhag)
                                        <option value="{{ $dzongkhag }}" @selected(old('place') === $dzongkhag)>{{ $dzongkhag }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="start_date">Start</label>
                                <input id="start_date" name="start_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('start_date') }}" required>
                            </div>

                            <div>
                                <label for="end_date">End</label>
                                <input id="end_date" name="end_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('end_date') }}" required>
                            </div>

                            <div>
                                <label for="total_days">Total Days</label>
                                <input id="total_days" type="text" value="-" readonly>
                            </div>
                        </div>

                        <div class="tour-grid-single">
                            <label for="purpose">Purpose</label>
                            <textarea id="purpose" name="purpose" rows="2" placeholder="Purpose of tour">{{ old('purpose') }}</textarea>
                        </div>

                        <div class="tour-grid-single">
                            <label for="office_order_pdf">Office Order (PDF)</label>
                            <input id="office_order_pdf" name="office_order_pdf" type="file" accept="application/pdf">
                        </div>

                        <button type="submit" class="save-btn">Save Tour Record</button>
                    </form>

                    <div class="tour-table-wrap">
                        <table class="tour-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>To date</th>
                                    <th>Total Days</th>
                                    <th>Destination</th>
                                    <th>Purpose</th>
                                    <th>Office Order</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tourRecords as $tour)
                                    @php
                                        $start = \Carbon\Carbon::parse($tour->start_date);
                                        $end = \Carbon\Carbon::parse($tour->end_date);
                                        $totalDays = $start->diffInDays($end) + 1;
                                    @endphp
                                    <tr>
                                        <td>{{ $start->format('Y-m-d') }}</td>
                                        <td>{{ $end->format('Y-m-d') }}</td>
                                        <td>{{ $totalDays }}</td>
                                        <td>{{ $tour->place }}</td>
                                        <td>{{ $tour->purpose ?: '-' }}</td>
                                        <td>
                                            @if($tour->office_order_pdf)
                                                <a href="{{ asset('storage/' . $tour->office_order_pdf) }}" target="_blank" class="tour-link">View PDF</a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">No tour records found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .employee-page {
        min-height: 100vh;
        background: transparent;
        color: #e2e8f0;
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
        font-size: 15px;
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

    .leave-approve-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        color: #ef4444;
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
        font-size: 15px;
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

    .card.tour-card {
        background: rgba(10, 67, 74, 0.72);
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 12px 26px rgba(2, 16, 24, 0.22);
    }

    .tour-card h1 {
        margin: 0 0 14px;
        color: #f8fafc;
        font-size: 24px;
        line-height: 1;
        font-weight: 900;
    }

    .tour-grid {
        display: grid;
        grid-template-columns: 1.2fr 1.2fr 1.2fr 0.8fr;
        gap: 16px;
        margin-bottom: 14px;
    }

    .tour-grid-single {
        margin-bottom: 14px;
    }

    .tour-card label {
        display: block;
        margin-bottom: 6px;
        color: rgba(226, 232, 240, 0.78);
        font-size: 15px;
        font-weight: 500;
    }

    .tour-card input,
    .tour-card select,
    .tour-card textarea {
        width: 100%;
        border: 1px solid rgba(45, 212, 191, 0.62);
        border-radius: 12px;
        background: rgba(6, 41, 46, 0.65);
        color: #e2e8f0;
        padding: 10px 12px;
        font-size: 14px;
        line-height: 1.2;
        outline: none;
    }

    .tour-card input::placeholder,
    .tour-card textarea::placeholder {
        color: rgba(203, 213, 225, 0.62);
    }

    .tour-card input:focus,
    .tour-card select:focus,
    .tour-card textarea:focus {
        border-color: #67e8f9;
        box-shadow: 0 0 0 2px rgba(103, 232, 249, 0.22);
    }

    .tour-card input[type="file"] {
        font-size: 14px;
        padding: 10px;
    }

    .tour-card input[readonly] {
        background: rgba(15, 61, 66, 0.72);
        color: #dbeafe;
    }

    .save-btn {
        margin-top: 2px;
        border: 0;
        background: linear-gradient(90deg, #0f766e 0%, #14b8a6 100%);
        color: #fff;
        border-radius: 10px;
        padding: 10px 16px;
        font-size: 14px;
        font-weight: 800;
        cursor: pointer;
    }

    .save-btn:hover {
        background: linear-gradient(90deg, #14b8a6 0%, #0f766e 100%);
    }

    .tour-table-wrap {
        margin-top: 18px;
        overflow-x: auto;
    }

    .tour-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 860px;
    }

    .tour-table th,
    .tour-table td {
        text-align: left;
        padding: 11px 12px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.25);
        color: #e2e8f0;
        font-size: 14px;
    }

    .tour-table th {
        background: rgba(255, 255, 255, 0.06);
        font-weight: 800;
        color: rgba(226, 232, 240, 0.9);
    }

    .tour-link {
        color: #67e8f9;
        text-decoration: underline;
    }

    .tour-saved-popup-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(15, 23, 42, 0.45);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
        z-index: 9998;
    }

    .tour-saved-popup-card {
        width: min(420px, 92vw);
        background: rgba(6, 52, 58, 0.95);
        border: 1px solid rgba(94, 234, 212, 0.5);
        border-radius: 14px;
        padding: 16px 16px 12px;
        color: #e2e8f0;
        box-shadow: 0 18px 40px rgba(2, 20, 28, 0.45);
        position: relative;
    }

    .tour-saved-popup-card h3 {
        margin: 0 0 10px;
        color: #5eead4;
        font-size: 20px;
        font-weight: 800;
    }

    .tour-error-popup-card h3 {
        color: #fca5a5;
    }

    .tour-saved-popup-card p {
        margin: 5px 0;
        font-size: 14px;
        color: #dff8f5;
    }

    .tour-saved-popup-card p strong {
        color: #99f6e4;
    }

    .tour-saved-popup-close {
        position: absolute;
        top: 8px;
        right: 10px;
        border: 0;
        background: transparent;
        color: #e2e8f0;
        font-size: 24px;
        line-height: 1;
        cursor: pointer;
    }

    .tour-saved-popup-actions {
        margin-top: 14px;
        display: flex;
        justify-content: center;
    }

    .tour-saved-popup-ok {
        border: 0;
        border-radius: 8px;
        padding: 7px 16px;
        font-size: 13px;
        font-weight: 700;
        color: #062b31;
        background: linear-gradient(90deg, #5eead4 0%, #2dd4bf 100%);
        cursor: pointer;
    }

    .tour-saved-popup-ok:hover {
        background: linear-gradient(90deg, #2dd4bf 0%, #5eead4 100%);
    }

    @media (max-width: 1200px) {
        .tour-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 900px) {
        .employee-layout {
            display: block;
        }

        .employee-sidebar {
            width: 100%;
            min-width: 0;
        }

        .tour-grid {
            grid-template-columns: 1fr;
        }

        .tour-card h1 {
            font-size: 32px;
        }

        .tour-card input,
        .tour-card textarea,
        .tour-card input[type="file"],
        .save-btn,
        .tour-table th,
        .tour-table td {
            font-size: 17px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function closeTourSavedPopup() {
        const popup = document.getElementById('tourSavedPopup');
        if (popup) {
            popup.style.display = 'none';
        }
    }

    function closeTourErrorPopup() {
        const popup = document.getElementById('tourErrorPopup');
        if (popup) {
            popup.style.display = 'none';
        }
    }

    (function () {
        const popup = document.getElementById('tourSavedPopup');
        if (popup) {
            setTimeout(closeTourSavedPopup, 4500);
        }
    })();

    (function () {
        const startInput = document.getElementById('start_date');
        const endInput = document.getElementById('end_date');
        const totalDaysInput = document.getElementById('total_days');
        const todayIso = new Date().toISOString().slice(0, 10);

        if (startInput && !startInput.min) {
            startInput.min = todayIso;
        }

        if (endInput && !endInput.min) {
            endInput.min = todayIso;
        }

        function calculateTotalDays() {
            if (!startInput.value || !endInput.value) {
                totalDaysInput.value = '-';
                return;
            }

            const start = new Date(startInput.value + 'T00:00:00');
            const end = new Date(endInput.value + 'T00:00:00');

            if (end < start) {
                totalDaysInput.value = '-';
                return;
            }

            const diffMs = end.getTime() - start.getTime();
            const days = Math.floor(diffMs / (1000 * 60 * 60 * 24)) + 1;
            totalDaysInput.value = String(days);
        }

        function syncDateLimits() {
            if (!startInput.value) {
                endInput.min = todayIso;
                return;
            }

            endInput.min = startInput.value >= todayIso ? startInput.value : todayIso;

            if (endInput.value && endInput.value < endInput.min) {
                endInput.value = endInput.min;
            }
        }

        startInput.addEventListener('change', function () {
            syncDateLimits();
            calculateTotalDays();
        });
        endInput.addEventListener('change', calculateTotalDays);
        syncDateLimits();
        calculateTotalDays();
    })();
</script>
@endpush

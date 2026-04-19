@extends('layouts.app')

@section('title', 'HoD Leave Requests')

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

                    <a href="{{ route('hod.leave.requests') }}" class="leave-approve-link active" style="position:relative; display:block;">
                        <span style="display:inline-block;">Leave Approve</span>
                        @if(!empty($leaveApproveCount) && $leaveApproveCount > 0)
                            <span class="leave-approve-badge" style="position:absolute; right:16px; top:50%; transform:translateY(-50%); background:#fff; color:#2f3f4a; display:inline-flex; align-items:center; justify-content:center; border-radius:16px; font-size:14px; font-weight:700; box-shadow:0 1px 4px rgba(0,0,0,0.08); padding:0 10px; height:auto; min-width:0;">{{ $leaveApproveCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('hod.staff.list') }}">Staff List</a>

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
                            <h1 class="text-3xl font-bold text-white">Employee Leave Requests</h1>
                            <p class="text-white/70 mt-1">Department: {{ $user->department ?? 'N/A' }}</p>
                        </div>

                @if(session('success'))
                    <div id="hodSuccessPopup" class="hod-success-popup-overlay">
                        <div class="hod-success-popup-card">
                            <button type="button" class="hod-success-popup-close" onclick="closeHodSuccessPopup()">&times;</button>
                            <h3>Success</h3>
                            <p>{{ session('success') }}</p>
                            <div class="hod-success-popup-actions">
                                <button type="button" class="hod-success-popup-ok" onclick="closeHodSuccessPopup()">OK</button>
                            </div>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1080px] text-left text-sm text-white/85">
                        <thead class="bg-white/5 text-white/70">
                            <tr class="border-b border-white/10">
                                <th class="px-4 py-3 font-medium">Employee</th>
                                <th class="px-4 py-3 font-medium">Type</th>
                                <th class="px-4 py-3 font-medium">Start</th>
                                <th class="px-4 py-3 font-medium">End</th>
                                <th class="px-4 py-3 font-medium">Days</th>
                                <th class="px-4 py-3 font-medium">Reason</th>
                                <th class="px-4 py-3 font-medium">MS Status</th>
                                <th class="px-4 py-3 font-medium">Prescription</th>
                                <th class="px-4 py-3 font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leaveRequests as $leave)
                                <tr class="border-b border-white/10 hover:bg-white/5 align-top">
                                    <td class="px-4 py-3 text-white font-medium">{{ $leave->user?->name ?? 'Unknown' }}</td>
                                    <td class="px-4 py-3">{{ $leave->leaveType?->name ?? $leave->leave_type }}</td>
                                    <td class="px-4 py-3">{{ \Carbon\Carbon::parse($leave->start_date)->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3">{{ \Carbon\Carbon::parse($leave->end_date)->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3">{{ number_format((float) $leave->total_days, 2) }}</td>
                                    <td class="px-4 py-3 max-w-sm break-words">{{ $leave->reason }}</td>
                                    <td class="px-4 py-3">
                                        @if(strtolower((string) $leave->hod_status) === 'rejected')
                                            <span class="text-white/50">-</span>
                                        @else
                                            {{ $leave->ms_status }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($leave->prescription)
                                            <a href="{{ asset('storage/' . $leave->prescription) }}" target="_blank" class="text-cyan-300 hover:text-cyan-200 underline">
                                                View
                                            </a>
                                        @else
                                            <span class="text-white/50">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if(strtolower((string) $leave->hod_status) === 'pending')
                                            <div class="flex gap-2">
                                                <form method="POST" action="{{ route('hod.leave.requests.action', $leave) }}">
                                                    @csrf
                                                    <input type="hidden" name="action" value="forward">
                                                    <button type="submit" class="rounded-md bg-cyan-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-cyan-700 transition">
                                                        Forward
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('hod.leave.requests.action', $leave) }}" class="reject-leave-form">
                                                    @csrf
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="rejection_reason" value="">
                                                    <button type="button" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700 transition reject-btn">
                                                        Reject
                                                    </button>
                                                </form>
                                            </div>
                                        @elseif(strtolower((string) $leave->hod_status) === 'rejected')
                                            <span class="inline-flex rounded-full bg-red-500/20 px-2.5 py-1 text-xs font-semibold text-red-200">Rejected</span>
                                        @elseif(strtolower((string) $leave->hod_status) === 'forwarded')
                                            <span class="inline-flex rounded-full bg-sky-500/20 px-2.5 py-1 text-xs font-semibold text-sky-200">Forwarded</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-white/15 px-2.5 py-1 text-xs font-semibold text-white/80">{{ $leave->hod_status }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-10 text-center text-white/60">No leave requests submitted to HoD in your department.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $leaveRequests->links() }}
                </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <!-- Unity-Themed Confirmation Modal -->
    <div id="unityConfirmModal" class="unity-modal-overlay" style="display:none;">
        <div class="unity-modal unity-modal-unity-colors">
            <div class="unity-modal-header unity-modal-header-unity">
                <span>Confirm Action</span>
            </div>
            <div class="unity-modal-body unity-modal-body-unity">
                <p>Are you sure you want to <span style="color:#ef4444;font-weight:600;">reject</span> this leave request?</p>
                <div class="unity-reason-wrap">
                    <label for="unityRejectReason" class="unity-reason-label">Reason for rejection</label>
                    <input id="unityRejectReason" type="text" class="unity-reason-input" placeholder="Enter reason" maxlength="1000">
                </div>
            </div>
            <div class="unity-modal-footer">
                <button id="unityModalCancel" class="unity-modal-btn unity-modal-cancel">Cancel</button>
                <button id="unityModalConfirm" class="unity-modal-btn unity-modal-confirm">Yes, Reject</button>
            </div>
        </div>
    </div>

    <script>
        let formToSubmit = null;
        const unityModal = document.getElementById('unityConfirmModal');
        const unityReasonInput = document.getElementById('unityRejectReason');
        const hodSuccessPopup = document.getElementById('hodSuccessPopup');

        function closeHodSuccessPopup() {
            if (hodSuccessPopup) {
                hodSuccessPopup.style.display = 'none';
            }
        }

        function resetRejectModal() {
            unityModal.style.display = 'none';
            formToSubmit = null;
            unityReasonInput.value = '';
            unityReasonInput.classList.remove('is-invalid');
        }

        document.querySelectorAll('.reject-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                formToSubmit = btn.closest('form');
                unityModal.style.display = 'flex';
                unityReasonInput.focus();
            });
        });
        document.getElementById('unityModalCancel').onclick = function() {
            resetRejectModal();
        };
        document.getElementById('unityModalConfirm').onclick = function() {
            const rejectReason = unityReasonInput.value.trim();

            if (!rejectReason) {
                unityReasonInput.classList.add('is-invalid');
                unityReasonInput.focus();
                return;
            }

            unityReasonInput.classList.remove('is-invalid');

            if (formToSubmit) {
                const reasonField = formToSubmit.querySelector('input[name="rejection_reason"]');
                if (reasonField) {
                    reasonField.value = rejectReason;
                }
                formToSubmit.submit();
            }

            resetRejectModal();
        };
        unityReasonInput.addEventListener('input', function() {
            if (unityReasonInput.value.trim().length > 0) {
                unityReasonInput.classList.remove('is-invalid');
            }
        });
        // Close modal on overlay click
        unityModal.addEventListener('click', function(e) {
            if(e.target === this) {
                resetRejectModal();
            }
        });
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                resetRejectModal();
                closeHodSuccessPopup();
            }
        });

        if (hodSuccessPopup) {
            hodSuccessPopup.addEventListener('click', function(event) {
                if (event.target === hodSuccessPopup) {
                    closeHodSuccessPopup();
                }
            });
        }
    </script>
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

        .hod-success-popup-overlay {
            position: fixed;
            inset: 0;
            background: rgba(6, 78, 59, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            z-index: 1200;
        }

        .hod-success-popup-card {
            width: min(440px, 100%);
            border-radius: 16px;
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 24px 50px rgba(2, 6, 23, 0.35);
            border: 1px solid rgba(15, 23, 42, 0.08);
            padding: 20px 20px 18px;
            position: relative;
        }

        .hod-success-popup-card h3 {
            margin: 0 0 8px;
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f766e;
        }

        .hod-success-popup-card p {
            margin: 0;
            color: #334155;
            line-height: 1.45;
        }

        .hod-success-popup-close {
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

        .hod-success-popup-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 16px;
        }

        .hod-success-popup-ok {
            border: 0;
            border-radius: 10px;
            background: #0f766e;
            color: #fff;
            padding: 9px 16px;
            font-weight: 700;
            cursor: pointer;
        }

        .unity-modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(30, 41, 59, 0.55);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .unity-modal-unity-colors {
            background: #034a50;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.32);
            border: 2px solid #1f8a90;
            color: #e2e8f0;
            min-width: 320px;
            max-width: 95vw;
            width: 400px;
            animation: notificationModalFadeIn 0.25s;
            display: flex;
            flex-direction: column;
        }
        .unity-modal-header-unity {
            background: #1f8a90;
            color: #ffffff;
            border-top-left-radius: 18px;
            border-top-right-radius: 18px;
            border-bottom: 1px solid #2a9fa6;
            font-size: 2rem;
            font-weight: 800;
            padding: 18px 22px 12px 22px;
            font-family: 'Montserrat', 'Segoe UI', Arial, sans-serif;
            letter-spacing: 0.5px;
        }
        .unity-modal-body-unity {
            padding: 22px 22px 12px 22px;
            color: #e2e8f0;
            font-size: 1.15rem;
            background: #034a50;
        }
        .unity-modal-footer {
            display: flex;
            justify-content: center;
            gap: 18px;
            padding: 0 22px 22px 22px;
            background: #034a50;
            border-bottom-left-radius: 18px;
            border-bottom-right-radius: 18px;
        }
        .unity-reason-wrap {
            margin-top: 14px;
        }
        .unity-reason-label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.92rem;
            color: #cbd5e1;
            font-weight: 600;
        }
        .unity-reason-input {
            width: 100%;
            border: 1px solid #1aa8ff;
            border-radius: 10px;
            background: rgba(15, 58, 62, 0.95);
            color: #e2e8f0;
            padding: 10px 12px;
            font-size: 0.98rem;
            outline: none;
            transition: border-color 0.15s ease;
        }
        .unity-reason-input::placeholder {
            color: #94a3b8;
        }
        .unity-reason-input:focus {
            border-color: #1aa8ff;
        }
        .unity-reason-input.is-invalid {
            border-color: #ef4444;
        }
        .unity-modal-header-unity {
            background: #1f8a90;
            color: #ffffff;
            border-top-left-radius: 18px;
            border-top-right-radius: 18px;
            border-bottom: 1px solid #2a9fa6;
            font-size: 2rem;
            font-weight: 800;
            padding: 18px 22px 12px 22px;
            font-family: 'Montserrat', 'Segoe UI', Arial, sans-serif;
            letter-spacing: 0.5px;
        }
        .unity-modal-body-unity {
            padding: 22px 22px 12px 22px;
            color: #e2e8f0;
            font-size: 1.15rem;
            background: #034a50;
        }
        .unity-modal-footer {
            display: flex;
            justify-content: center;
            gap: 18px;
            padding: 0 22px 22px 22px;
            background: #034a50;
            border-bottom-left-radius: 18px;
            border-bottom-right-radius: 18px;
        }
        .unity-modal-btn {
            border: none;
            border-radius: 10px;
            padding: 12px 28px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.18s, color 0.18s;
            font-family: 'Montserrat', 'Segoe UI', Arial, sans-serif;
        }
        .unity-modal-cancel {
            background: linear-gradient(90deg, #1f909a 0%, #2aa8b0 100%);
            color: #fff;
            box-shadow: 0 2px 8px rgba(13,111,119,0.22);
        }
        .unity-modal-cancel:hover {
            background: linear-gradient(90deg, #2aa8b0 0%, #1f909a 100%);
        }
        .unity-modal-confirm {
            background: linear-gradient(90deg, #1f909a 0%, #2aa8b0 100%);
            color: #fff;
            box-shadow: 0 2px 8px rgba(13,111,119,0.22);
        }
        .unity-modal-confirm:hover {
            background: linear-gradient(90deg, #2aa8b0 0%, #1f909a 100%);
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
@endsection

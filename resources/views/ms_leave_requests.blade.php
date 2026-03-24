@extends('layouts.app')

@section('title', 'MS Leave Requests')

@section('content')
    <div class="min-h-screen p-8">
        <div class="max-w-6xl mx-auto">
            <div class="card-backdrop rounded-xl p-8 mb-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-white">Employee Leave Requests</h1>
                        <p class="text-white/70 mt-1">Forwarded by HoD to Medical Superintendent</p>
                    </div>
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition text-center">
                        Back to Dashboard
                    </a>
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

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1160px] text-left text-sm text-white/85">
                        <thead class="bg-white/5 text-white/70">
                            <tr class="border-b border-white/10">
                                <th class="px-4 py-3 font-medium">Employee</th>
                                <th class="px-4 py-3 font-medium">Department</th>
                                <th class="px-4 py-3 font-medium">Type</th>
                                <th class="px-4 py-3 font-medium">Start</th>
                                <th class="px-4 py-3 font-medium">End</th>
                                <th class="px-4 py-3 font-medium">Days</th>
                                <th class="px-4 py-3 font-medium">Reason</th>
                                <th class="px-4 py-3 font-medium">HoD Status</th>
                                <th class="px-4 py-3 font-medium">Prescription</th>
                                <th class="px-4 py-3 font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leaveRequests as $leave)
                                <tr class="border-b border-white/10 hover:bg-white/5 align-top">
                                    <td class="px-4 py-3 text-white font-medium">{{ $leave->user?->name ?? 'Unknown' }}</td>
                                    <td class="px-4 py-3">{{ $leave->user?->department ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $leave->leaveType?->name ?? $leave->leave_type }}</td>
                                    <td class="px-4 py-3">{{ \Carbon\Carbon::parse($leave->start_date)->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3">{{ \Carbon\Carbon::parse($leave->end_date)->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3">{{ number_format((float) $leave->total_days, 2) }}</td>
                                    <td class="px-4 py-3 max-w-sm break-words">{{ $leave->reason }}</td>
                                    <td class="px-4 py-3">{{ $leave->hod_status }}</td>
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
                                        @if(strtolower((string) $leave->ms_status) === 'pending')
                                            <div class="flex gap-2">
                                                <form method="POST" action="{{ route('ms.leave.requests.action', $leave) }}">
                                                    @csrf
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 transition">
                                                        Approve
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('ms.leave.requests.action', $leave) }}" onsubmit="return confirm('Reject this leave request?');">
                                                    @csrf
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700 transition">
                                                        Reject
                                                    </button>
                                                </form>
                                            </div>
                                        @elseif(strtolower((string) $leave->ms_status) === 'approved')
                                            <span class="inline-flex rounded-full bg-emerald-500/20 px-2.5 py-1 text-xs font-semibold text-emerald-200">Approved</span>
                                        @elseif(strtolower((string) $leave->ms_status) === 'rejected')
                                            <span class="inline-flex rounded-full bg-red-500/20 px-2.5 py-1 text-xs font-semibold text-red-200">Rejected</span>
                                        @else
                                            <span class="text-white/50">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-4 py-10 text-center text-white/60">No leave requests forwarded to MS yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $leaveRequests->links() }}
                </div>
            </div>

            <div class="card-backdrop rounded-xl p-8">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-white">Employee Attendance Logs</h2>
                    <p class="mt-1 text-white/70">Attendance records captured from employee clock-in and clock-out</p>
                </div>

                <form method="GET" action="{{ route('ms.leave.requests') }}" class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label for="att_from_date" class="mb-2 block text-sm text-white/80">From Date</label>
                        <input
                            id="att_from_date"
                            name="att_from_date"
                            type="date"
                            value="{{ $attendanceFilters['from_date'] ?? '' }}"
                            class="h-10 w-full rounded-lg border border-white/20 bg-white/5 px-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-400"
                        >
                    </div>

                    <div>
                        <label for="att_to_date" class="mb-2 block text-sm text-white/80">To Date</label>
                        <input
                            id="att_to_date"
                            name="att_to_date"
                            type="date"
                            value="{{ $attendanceFilters['to_date'] ?? '' }}"
                            class="h-10 w-full rounded-lg border border-white/20 bg-white/5 px-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-400"
                        >
                    </div>

                    <div>
                        <label for="att_employee" class="mb-2 block text-sm text-white/80">Employee</label>
                        <input
                            id="att_employee"
                            name="att_employee"
                            type="text"
                            value="{{ $attendanceFilters['employee'] ?? '' }}"
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
                            @forelse($attendanceLogs as $attendance)
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
                    {{ $attendanceLogs->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'HoD Leave Requests')

@section('content')
    <div class="min-h-screen p-8">
        <div class="max-w-6xl mx-auto">
            <div class="card-backdrop rounded-xl p-8 mb-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-white">Employee Leave Requests</h1>
                        <p class="text-white/70 mt-1">Department: {{ $user->department ?? 'N/A' }}</p>
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
                                    <td class="px-4 py-3">{{ $leave->ms_status }}</td>
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
                                                <form method="POST" action="{{ route('hod.leave.requests.action', $leave) }}" onsubmit="return confirm('Reject this leave request?');">
                                                    @csrf
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700 transition">
                                                        Reject
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-white/50">-</span>
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
    </div>
@endsection

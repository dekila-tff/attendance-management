@extends('layouts.app')

@section('title', 'MS Attendance Logs')

@section('content')
    <div class="min-h-screen p-8">
        <div class="max-w-7xl mx-auto">
            <div class="card-backdrop rounded-xl p-8 mb-6">
                <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-white">Employee Attendance Logs</h1>
                        <p class="mt-1 text-white/70">Clock-in and clock-out records visible to Medical Superintendent</p>
                    </div>
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-gray-600 px-4 py-2 text-center text-white transition hover:bg-gray-700">
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
            </div>
        </div>
    </div>
@endsection

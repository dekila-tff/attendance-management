@extends('layouts.app')

@section('title', 'Attendance History')

@section('content')
    <div class="min-h-screen p-8">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-white">Attendance History</h1>
                    <p class="text-white/70 mt-1">Review your daily attendance records</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('profile') }}" class="inline-block px-5 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-white transition">
                        Back to Profile
                    </a>
                    <a href="{{ route('dashboard') }}" class="inline-block px-5 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-white transition">
                        Back to Dashboard
                    </a>
                </div>
            </div>

            <div class="card-backdrop rounded-xl p-6 mb-6">
                <form method="GET" action="{{ route('attendance.history') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label for="from_date" class="block text-sm text-white/80 mb-2">From Date</label>
                        <input
                            id="from_date"
                            name="from_date"
                            type="date"
                            value="{{ $filters['from_date'] ?? '' }}"
                            class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/20 text-white focus:outline-none focus:ring-2 focus:ring-blue-400"
                        >
                    </div>
                    <div>
                        <label for="to_date" class="block text-sm text-white/80 mb-2">To Date</label>
                        <input
                            id="to_date"
                            name="to_date"
                            type="date"
                            value="{{ $filters['to_date'] ?? '' }}"
                            class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/20 text-white focus:outline-none focus:ring-2 focus:ring-blue-400"
                        >
                    </div>
                    <div class="md:col-span-2 flex flex-wrap gap-3">
                        <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-white font-medium transition">
                            Filter
                        </button>
                        <a href="{{ route('attendance.history') }}" class="px-5 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-white transition">
                            Reset
                        </a>
                    </div>
                </form>

                @if($errors->any())
                    <div class="mt-4 p-3 rounded-lg bg-red-500/10 border border-red-500/20">
                        @foreach($errors->all() as $error)
                            <p class="text-sm text-red-300">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="card-backdrop rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-white/5 border-b border-white/10">
                            <tr>
                                <th class="px-6 py-3 text-sm text-white/70 font-medium">Date</th>
                                <th class="px-6 py-3 text-sm text-white/70 font-medium">Clock In</th>
                                <th class="px-6 py-3 text-sm text-white/70 font-medium">Clock Out</th>
                                <th class="px-6 py-3 text-sm text-white/70 font-medium">Status</th>
                                <th class="px-6 py-3 text-sm text-white/70 font-medium">Remarks</th>
                                <th class="px-6 py-3 text-sm text-white/70 font-medium">Clock In Location</th>
                                <th class="px-6 py-3 text-sm text-white/70 font-medium">Clock Out Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendances as $attendance)
                                <tr class="border-b border-white/10">
                                    <td class="px-6 py-4 text-white">{{ \Carbon\Carbon::parse($attendance->date)->format('M d, Y') }}</td>
                                    <td class="px-6 py-4 text-blue-300">
                                        {{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('h:i A') : '--:--' }}
                                    </td>
                                    <td class="px-6 py-4 text-orange-300">
                                        {{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('h:i A') : '--:--' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($attendance->status === 'leave')
                                            <span class="text-blue-300">Leave</span>
                                        @elseif($attendance->clock_out)
                                            <span class="text-green-400">{{ ucfirst($attendance->status) }}</span>
                                        @elseif($attendance->clock_in)
                                            <span class="text-yellow-300">{{ ucfirst($attendance->status) }}</span>
                                        @else
                                            <span class="text-red-300">Absent</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-white/80">{{ $attendance->remarks ?: '-' }}</td>
                                    <td class="px-6 py-4 text-white/80">{{ $attendance->clockIn_address ?: '-' }}</td>
                                    <td class="px-6 py-4 text-white/80">{{ $attendance->clockOut_address ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-white/60">
                                        No attendance records found for the selected period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-white/10">
                    {{ $attendances->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Apply Leave')

@push('styles')
    <style>
        #leave_type,
        #submit_to {
            background-color: #082b30;
            color: #ffffff;
        }

        #balance,
        #start_date,
        #end_date,
        #total_days,
        #reason,
        #prescription {
            background-color: #082b30 !important;
            color: #ffffff !important;
            border-color: rgba(255, 255, 255, 0.2) !important;
        }

        #leave_type option,
        #submit_to option {
            background-color: #0b3a40;
            color: #ffffff;
        }

        #balance::placeholder,
        #total_days::placeholder,
        #reason::placeholder {
            color: rgba(255, 255, 255, 0.45);
        }

        #start_date::-webkit-calendar-picker-indicator,
        #end_date::-webkit-calendar-picker-indicator {
            filter: invert(1) brightness(1.2);
            opacity: 0.8;
        }

        #prescription::file-selector-button {
            background-color: #0284c7;
            color: white;
            cursor: pointer;
        }
    </style>
@endpush

@section('content')
    <div class="min-h-screen px-4 pb-8 pt-10 sm:px-6 sm:pb-10 sm:pt-12 lg:px-8 flex items-center justify-center">
        <div class="max-w-6xl w-full rounded-2xl bg-[#082f34]/80 p-5 text-white shadow-[0_24px_55px_-24px_rgba(0,0,0,0.55)] backdrop-blur md:p-8">
            <div class="mb-5 h-px w-full bg-gradient-to-r from-cyan-200/0 via-cyan-200/45 to-cyan-200/0"></div>
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-white md:text-4xl">Leave</h1>
                    <p class="mt-1 text-sm text-white/70">Submit leave requests and review your request history</p>
                </div>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-lg border border-cyan-300/30 bg-cyan-500/10 px-4 py-2 text-sm font-medium text-cyan-100 transition hover:bg-cyan-500/20">
                    Back
                </a>
            </div>

            @if(session('success'))
                <div class="mb-4 rounded-lg border border-emerald-300/25 bg-emerald-500/10 p-3">
                    <p class="text-sm text-emerald-200">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 rounded-lg border border-red-300/25 bg-red-500/10 p-3">
                    <p class="text-sm text-red-200">{{ session('error') }}</p>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 rounded-lg border border-red-300/25 bg-red-500/10 p-3">
                    @foreach($errors->all() as $error)
                        <p class="text-sm text-red-200">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('leave.store') }}" class="mb-8 space-y-5 rounded-xl bg-[#0a383e]/70 p-4 shadow-sm md:mb-10 md:p-6" enctype="multipart/form-data">
                @csrf

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3 md:gap-5">
                    <div>
                        <label for="leave_type" class="mb-2 block text-sm font-semibold text-white/80">Type</label>
                        <select id="leave_type" name="leave_type" class="w-full rounded-xl border border-white/20 bg-[#082b30] px-3 py-2.5 text-base text-white focus:border-cyan-300 focus:outline-none focus:ring-2 focus:ring-cyan-400/30" required>
                            <option value="" disabled @selected(!old('leave_type'))>Select leave type</option>
                            @foreach($leaveTypes as $leaveType)
                                <option value="{{ $leaveType->id }}" @selected((string) old('leave_type') === (string) $leaveType->id)>{{ $leaveType->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="submit_to" class="mb-2 block text-sm font-semibold text-white/80">Submit To</label>
                        <select id="submit_to" name="submit_to" class="w-full rounded-xl border border-white/20 bg-[#082b30] px-3 py-2.5 text-base text-white focus:border-cyan-300 focus:outline-none focus:ring-2 focus:ring-cyan-400/30" required>
                            @if($isHod)
                                <option value="MS" selected>MS</option>
                            @else
                                <option value="" disabled @selected(!old('submit_to'))>Select approver</option>
                                <option value="HoD" @selected(old('submit_to') === 'HoD')>HoD (HoD)</option>
                                <option value="MS" @selected(old('submit_to') === 'MS')>MS</option>
                            @endif
                        </select>
                    </div>

                    <div>
                        <label for="balance" class="mb-2 block text-sm font-semibold text-white/80">Balance</label>
                        <input id="balance" name="balance" type="text" class="w-full rounded-xl border border-white/20 bg-[#0c343a] px-3 py-2.5 text-base font-medium text-cyan-100 placeholder:text-white/40 focus:outline-none" value="" placeholder="Select leave type first" readonly>
                    </div>

                    <div>
                        <label for="start_date" class="mb-2 block text-sm font-semibold text-white/80">Start</label>
                        <input id="start_date" name="start_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('start_date') }}" class="w-full rounded-xl border border-white/20 bg-[#082b30] px-3 py-2.5 text-base text-white focus:border-cyan-300 focus:outline-none focus:ring-2 focus:ring-cyan-400/30" required>
                    </div>

                    <div>
                        <label for="end_date" class="mb-2 block text-sm font-semibold text-white/80">End</label>
                        <input id="end_date" name="end_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('end_date') }}" class="w-full rounded-xl border border-white/20 bg-[#082b30] px-3 py-2.5 text-base text-white focus:border-cyan-300 focus:outline-none focus:ring-2 focus:ring-cyan-400/30" required>
                    </div>

                    <div>
                        <label for="total_days" class="mb-2 block text-sm font-semibold text-white/80">Total Days</label>
                        <input id="total_days" name="total_days" type="number" min="0.5" step="0.5" value="{{ old('total_days') }}" placeholder="e.g. 1 or 0.5" class="w-full rounded-xl border border-white/20 bg-[#082b30] px-3 py-2.5 text-base text-white placeholder:text-white/40 focus:border-cyan-300 focus:outline-none focus:ring-2 focus:ring-cyan-400/30" required>
                    </div>
                </div>

                <div>
                    <label for="reason" class="mb-2 block text-sm font-semibold text-white/80">Reason</label>
                    <textarea id="reason" name="reason" rows="3" placeholder="Briefly describe your leave reason" class="w-full rounded-xl border border-white/20 bg-[#082b30] px-3 py-2.5 text-base text-white placeholder:text-white/40 focus:border-cyan-300 focus:outline-none focus:ring-2 focus:ring-cyan-400/30" required>{{ old('reason') }}</textarea>
                </div>

                <div id="prescription-container" class="hidden">
                    <label for="prescription" class="mb-2 block text-sm font-semibold text-white/80">Upload Prescription <span class="text-red-400">*</span></label>
                    <input id="prescription" name="prescription" type="file" accept=".jpg,.jpeg,.png,.pdf" class="w-full rounded-xl border border-white/20 bg-[#082b30] px-3 py-2.5 text-base text-white file:mr-3 file:rounded file:border-0 file:bg-cyan-600 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white file:cursor-pointer focus:border-cyan-300 focus:outline-none focus:ring-2 focus:ring-cyan-400/30">
                    <p class="mt-2 text-xs text-white/60">Accepted formats: JPG, PNG, PDF (Max 5MB)</p>
                </div>

                <button type="submit" class="mt-6 inline-flex items-center justify-center rounded-lg border border-cyan-300/30 bg-cyan-500/10 px-4 py-2 text-sm font-medium text-cyan-100 transition hover:bg-cyan-500/20 focus:outline-none focus:ring-2 focus:ring-cyan-300/40 focus:ring-offset-2 focus:ring-offset-[#0a383e] md:mt-8">
                    Submit
                </button>
            </form>

            <div class="rounded-xl bg-[#0a383e]/70 p-4 shadow-sm md:p-6">
                <h2 class="mb-4 text-xl font-bold text-white md:text-2xl">Leave History</h2>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[820px] text-left text-sm text-white/80">
                        <thead class="bg-white/5 text-xs uppercase tracking-wide text-white/60">
                            <tr class="border-b border-white/10">
                                <th class="px-3 py-3 font-semibold">Type</th>
                                <th class="px-3 py-3 font-semibold">S.date</th>
                                <th class="px-3 py-3 font-semibold">E.date</th>
                                <th class="px-3 py-3 font-semibold">Reason</th>
                                <th class="px-3 py-3 font-semibold">Days</th>
                                @unless($isHod)
                                    <th class="px-3 py-3 font-semibold">HoD</th>
                                @endunless
                                @unless($isHod)
                                    <th class="px-3 py-3 font-semibold">MS</th>
                                @endunless
                                @unless($isHod)
                                    <th class="px-3 py-3 font-semibold">HoD Status</th>
                                @endunless
                                <th class="px-3 py-3 font-semibold">MS Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leaveHistory as $leave)
                                <tr class="border-b border-white/10 hover:bg-white/5">
                                    <td class="px-3 py-3 font-medium text-white">{{ str_replace(' ', '_', $leave->leaveType?->name ?? $leave->leave_type) }}</td>
                                    <td class="px-3 py-3">{{ \Carbon\Carbon::parse($leave->start_date)->format('Y-m-d') }}</td>
                                    <td class="px-3 py-3">{{ \Carbon\Carbon::parse($leave->end_date)->format('Y-m-d') }}</td>
                                    <td class="px-3 py-3">{{ $leave->reason }}</td>
                                    <td class="px-3 py-3 font-medium">{{ number_format((float) $leave->total_days, 2) }}</td>
                                    @unless($isHod)
                                        <td class="px-3 py-3">{{ $leave->is_direct_to_ms ? '-' : 'HoD' }}</td>
                                    @endunless
                                    @unless($isHod)
                                        <td class="px-3 py-3">{{ $leave->is_direct_to_ms ? 'MS' : '-' }}</td>
                                    @endunless
                                    @unless($isHod)
                                        <td class="px-3 py-3">
                                            @if($leave->is_direct_to_ms)
                                                <span class="text-white/50">-</span>
                                            @elseif(strtolower((string) $leave->hod_status) === 'pending')
                                                <span class="inline-flex rounded-full bg-amber-500/20 px-2.5 py-1 text-xs font-semibold text-amber-200">{{ $leave->hod_status }}</span>
                                            @elseif(strtolower((string) $leave->hod_status) === 'forwarded')
                                                <span class="inline-flex rounded-full bg-sky-500/20 px-2.5 py-1 text-xs font-semibold text-sky-200">{{ $leave->hod_status }}</span>
                                            @elseif(strtolower((string) $leave->hod_status) === 'approved')
                                                <span class="inline-flex rounded-full bg-emerald-500/20 px-2.5 py-1 text-xs font-semibold text-emerald-200">{{ $leave->hod_status }}</span>
                                            @elseif(strtolower((string) $leave->hod_status) === 'rejected')
                                                <span class="inline-flex rounded-full bg-red-500/20 px-2.5 py-1 text-xs font-semibold text-red-200">{{ $leave->hod_status }}</span>
                                            @else
                                                <span class="text-white/50">-</span>
                                            @endif
                                        </td>
                                    @endunless
                                    <td class="px-3 py-3">
                                        @if(strtolower($leave->ms_status) === 'pending')
                                            <span class="inline-flex rounded-full bg-amber-500/20 px-2.5 py-1 text-xs font-semibold text-amber-200">{{ $leave->ms_status }}</span>
                                        @elseif(strtolower($leave->ms_status) === 'approved')
                                            <span class="inline-flex rounded-full bg-emerald-500/20 px-2.5 py-1 text-xs font-semibold text-emerald-200">{{ $leave->ms_status }}</span>
                                        @elseif(strtolower($leave->ms_status) === 'rejected')
                                            <span class="inline-flex rounded-full bg-red-500/20 px-2.5 py-1 text-xs font-semibold text-red-200">{{ $leave->ms_status }}</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-white/15 px-2.5 py-1 text-xs font-semibold text-white/80">{{ $leave->ms_status }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isHod ? 6 : 9 }}" class="py-10 text-center text-white/50">No leave history found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="leaveBalancesData" data-balances='@json($balances)'></div>
        </div>
    </div>

    <script>
        const leaveBalancesDataElement = document.getElementById('leaveBalancesData');
        const leaveBalances = JSON.parse(leaveBalancesDataElement.dataset.balances ?? '{}');
        const leaveTypeElement = document.getElementById('leave_type');
        const balanceElement = document.getElementById('balance');
        const prescriptionContainer = document.getElementById('prescription-container');
        const prescriptionInput = document.getElementById('prescription');

        // Get the Medical Leave type ID (ID 5 in the database)
        const medicalLeaveTypeId = 5;

        function updateBalance() {
            const selectedLeaveType = leaveTypeElement.value;
            if (!selectedLeaveType || leaveBalances[selectedLeaveType] === undefined) {
                balanceElement.value = '';
                return;
            }

            const balance = leaveBalances[selectedLeaveType];
            balanceElement.value = Number(balance).toFixed(2);
        }

        function togglePrescriptionField() {
            const selectedLeaveType = leaveTypeElement.value;
            
            if (parseInt(selectedLeaveType) === medicalLeaveTypeId) {
                prescriptionContainer.classList.remove('hidden');
                prescriptionInput.setAttribute('required', 'required');
            } else {
                prescriptionContainer.classList.add('hidden');
                prescriptionInput.removeAttribute('required');
                prescriptionInput.value = '';
            }
        }

        leaveTypeElement.addEventListener('change', function() {
            updateBalance();
            togglePrescriptionField();
        });

        updateBalance();
        togglePrescriptionField();
    </script>
@endsection

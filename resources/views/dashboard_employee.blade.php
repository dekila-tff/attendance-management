@extends('layouts.app')

@section('title', 'Employee Dashboard')

@section('content')
    <div class="min-h-screen p-8">
        <div class="max-w-5xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-4">
                    @if($user->profile_picture && file_exists(public_path($user->profile_picture)))
                        <img src="{{ asset($user->profile_picture) }}" alt="Profile Picture" class="w-16 h-16 rounded-full object-cover border-2 border-white/20">
                    @else
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center border-2 border-white/20">
                            <span class="text-2xl font-bold text-white">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                        </div>
                    @endif
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-2">Welcome</h1>
                        <p class="text-white/70"><strong class="text-white">{{ $user->name }}</strong></p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" id="logoutForm">
                    @csrf
                    <input type="hidden" name="location" id="logoutLocation" value="Location not available">
                    <button type="submit" id="logoutButton" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                        Logout
                    </button>
                </form>
            </div>

            <!-- Today's Status -->
            <div class="card-backdrop rounded-xl p-8 mb-6">
                <h2 class="text-2xl font-bold text-white mb-4">Today's Attendance</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div>
                        <p class="text-white/70 text-sm mb-2">Date</p>
                        <p class="text-xl font-semibold text-white">{{ now()->format('F d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-white/70 text-sm mb-2">Status</p>
                        @if($attendance)
                            @if($attendance->status === 'leave')
                                <p class="text-xl font-semibold text-blue-300">Leave</p>
                            @elseif($attendance->clock_out)
                                <p class="text-xl font-semibold text-green-400">{{ ucfirst($attendance->status) }}</p>
                            @else
                                <p class="text-xl font-semibold text-red-400">{{ ucfirst($attendance->status) }}</p>
                            @endif
                        @else
                            <p class="text-xl font-semibold text-yellow-400">Not Clocked In</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-white/70 text-sm mb-2">Clock In</p>
                        @if($attendance && $attendance->clock_in)
                            <p class="text-xl font-semibold text-blue-400">{{ \Carbon\Carbon::parse($attendance->clock_in)->format('h:i A') }}</p>
                        @else
                            <p class="text-xl font-semibold text-white/40">--:--</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-white/70 text-sm mb-2">Clock Out</p>
                        @if($attendance && $attendance->clock_out)
                            <p class="text-xl font-semibold text-orange-400">{{ \Carbon\Carbon::parse($attendance->clock_out)->format('h:i A') }}</p>
                        @else
                            <p class="text-xl font-semibold text-white/40">--:--</p>
                        @endif
                    </div>
                </div>
                
                <!-- Location Information -->
                @if($attendance)
                    <div class="border-t border-white/10 pt-4 space-y-3">
                        @if($attendance->remarks)
                            <div>
                                <p class="text-white/70 text-xs mb-1">Remarks</p>
                                <p class="text-sm text-yellow-300">{{ $attendance->remarks }}</p>
                            </div>
                        @endif
                        @if($attendance->clockIn_address)
                            <div>
                                <p class="text-white/70 text-xs mb-1">Clock In Location</p>
                                <p class="text-sm text-white/90">📍 {{ $attendance->clockIn_address }}</p>
                            </div>
                        @endif
                        @if($attendance->clockOut_address)
                            <div>
                                <p class="text-white/70 text-xs mb-1">Clock Out Location</p>
                                <p class="text-sm text-white/90">📍 {{ $attendance->clockOut_address }}</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Quick Actions -->
            <div class="card-backdrop rounded-xl p-8">
                <h2 class="text-2xl font-bold text-white mb-4">Quick Actions</h2>
                
                @if(session('success'))
                    <div class="mb-4 p-3 rounded-lg bg-green-500/10 border border-green-500/20">
                        <p class="text-sm text-green-400">{{ session('success') }}</p>
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="mb-4 p-3 rounded-lg bg-red-500/10 border border-red-500/20">
                        <p class="text-sm text-red-400">{{ session('error') }}</p>
                    </div>
                @endif
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <form method="POST" action="{{ route('clock.in') }}" id="clockInForm" onsubmit="return captureLocation(event, 'clockInForm', 'clockInLocation', 'clockInButton')">
                        @csrf
                        <input type="hidden" name="location" id="clockInLocation" value="Location not available">
                        <button type="submit" id="clockInButton" class="w-full px-6 py-4 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition" @if($attendance) disabled @endif>
                            @if($attendance)
                                Clocked In
                            @else
                                Clock In
                            @endif
                        </button>
                    </form>
                    
                    <form method="POST" action="{{ route('clock.out') }}" id="clockOutForm" onsubmit="return captureLocation(event, 'clockOutForm', 'clockOutLocation', 'clockOutButton')">
                        @csrf
                        <input type="hidden" name="location" id="clockOutLocation" value="Location not available">
                        <button type="submit" id="clockOutButton" class="w-full px-6 py-4 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium transition" @if(!$attendance || $attendance->clock_out || $clockOutLocked) disabled @endif>
                            @if($attendance && $attendance->clock_out)
                                Clocked Out
                            @elseif($clockOutLocked)
                                Clock Out (After 3:00 PM)
                            @else
                                Clock Out
                            @endif
                        </button>
                    </form>
                    
                    <a href="{{ route('leave.create') }}" class="block px-6 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-center font-medium transition">
                        Apply Leave
                    </a>
                    <a href="{{ route('profile') }}" class="block px-6 py-4 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-center font-medium transition">
                        My Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function captureLocation(event, formId, locationFieldId, buttonId) {
            if (navigator.geolocation) {
                event.preventDefault();
                const submitButton = document.getElementById(buttonId);
                const originalText = submitButton.textContent;
                submitButton.textContent = 'Getting location...';
                submitButton.disabled = true;

                navigator.geolocation.getCurrentPosition(
                    async function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        try {
                            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
                            const data = await response.json();
                            const address = data.display_name || `Lat: ${lat}, Lng: ${lng}`;
                            document.getElementById(locationFieldId).value = address;
                        } catch (error) {
                            document.getElementById(locationFieldId).value = `Lat: ${lat}, Lng: ${lng}`;
                        }
                        
                        submitButton.textContent = originalText;
                        submitButton.disabled = false;
                        document.getElementById(formId).submit();
                    },
                    function(error) {
                        console.error('Geolocation error:', error);
                        document.getElementById(locationFieldId).value = 'Location permission denied';
                        submitButton.textContent = originalText;
                        submitButton.disabled = false;
                        document.getElementById(formId).submit();
                    }
                );
                return false;
            }
            return true;
        }
        
        function captureLogoutLocation(event) {
            if (navigator.geolocation) {
                event.preventDefault();
                const submitButton = document.getElementById('logoutButton');
                const originalText = submitButton.textContent;
                submitButton.textContent = 'Getting location...';
                submitButton.disabled = true;

                navigator.geolocation.getCurrentPosition(
                    async function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        // Try to get address from coordinates
                        try {
                            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
                            const data = await response.json();
                            const address = data.display_name || `Lat: ${lat}, Lng: ${lng}`;
                            document.getElementById('logoutLocation').value = address;
                        } catch (error) {
                            document.getElementById('logoutLocation').value = `Lat: ${lat}, Lng: ${lng}`;
                        }
                        
                        submitButton.textContent = originalText;
                        submitButton.disabled = false;
                        event.target.submit();
                    },
                    function(error) {
                        console.error('Geolocation error:', error);
                        document.getElementById('logoutLocation').value = 'Location permission denied';
                        submitButton.textContent = originalText;
                        submitButton.disabled = false;
                        event.target.submit();
                    }
                );
                return false;
            }
            return true;
        }
    </script>
@endsection

@extends('layouts.app')

@section('title', 'Admin Login')

@section('content')
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md rounded-2xl p-8 shadow-2xl card-backdrop">
            <h2 class="text-2xl font-bold text-center mb-2">Admin sign in</h2>
            <p class="text-center text-sm text-white/70 mb-6">Use your admin credentials to continue</p>

            @if ($errors->any())
                <div class="mb-4 p-3 rounded-lg bg-red-500/10 border border-red-500/20">
                    <ul class="text-sm text-red-400">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.attempt') }}" id="adminLoginForm">
                @csrf

                <div class="mb-4">
                    <label class="block text-sm text-white/70 mb-2">Admin Username</label>
                    <input type="text" name="username" value="{{ old('username') }}" placeholder="admin@ntmh.bt" required
                           class="w-full px-4 py-3 rounded-lg bg-white/5 border border-white/6 text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('username') border-red-500/50 @enderror">
                </div>

                <div class="mb-4">
                    <label class="block text-sm text-white/70 mb-2">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" placeholder="Your password" required
                               class="w-full px-4 py-3 pr-12 rounded-lg bg-white/5 border border-white/6 text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="button" onclick="togglePassword()"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-white/60 hover:text-white/90 focus:outline-none">
                            <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg id="eye-slash-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 hidden">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                </div>

                <script>
                    function togglePassword() {
                        const passwordInput = document.getElementById('password');
                        const eyeIcon = document.getElementById('eye-icon');
                        const eyeSlashIcon = document.getElementById('eye-slash-icon');

                        if (passwordInput.type === 'password') {
                            passwordInput.type = 'text';
                            eyeIcon.classList.add('hidden');
                            eyeSlashIcon.classList.remove('hidden');
                        } else {
                            passwordInput.type = 'password';
                            eyeIcon.classList.remove('hidden');
                            eyeSlashIcon.classList.add('hidden');
                        }
                    }
                </script>

                <div class="flex items-center justify-between mb-4">
                    <label class="flex items-center gap-2 text-sm text-white/80">
                        <input type="checkbox" id="remember" name="remember" class="h-4 w-4 rounded border-white/10 bg-white/5 text-blue-500 focus:ring-blue-500">
                        Remember me
                    </label>
                    <a class="text-sm text-white/80 hover:underline" href="{{ route('login') }}">Employee login</a>
                </div>

                <div class="mb-4">
                    <button type="submit" class="inline-block bg-gradient-to-b from-blue-500 to-blue-800 text-white font-bold py-3 px-6 rounded-xl shadow-lg">Admin Log in</button>
                </div>
            </form>

            <div class="flex items-center justify-between text-sm text-white/70 mt-6">
                <div>Need help? <a href="#" class="underline">Contact</a></div>
                <div>Version 1.0</div>
            </div>
        </div>
    </div>
@endsection

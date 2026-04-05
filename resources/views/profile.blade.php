@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
    <div class="min-h-screen p-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2">My Profile</h1>
                    <p class="text-white/70">View and manage your profile information</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('dashboard') }}" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                        Back to Dashboard
                    </a>
                </div>
            </div>

            <div style="display: flex; gap: 1.5rem; align-items: stretch; flex-wrap: wrap; margin-bottom: 1.5rem;">
            <!-- Profile Picture Card -->
            <div class="card-backdrop rounded-xl p-8" style="flex: 1; min-width: 340px; display: flex; flex-direction: column;">
                <h2 class="text-2xl font-bold text-white mb-6">Profile Picture</h2>
                
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

                @if ($errors->any())
                    <div class="mb-4 p-3 rounded-lg bg-red-500/10 border border-red-500/20">
                        <ul class="text-sm text-red-400 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <div class="flex items-center" style="gap: 2.5rem;">
                    <div class="flex-shrink-0">
                        @if($user->profile_picture && file_exists(public_path($user->profile_picture)))
                            <img src="{{ asset($user->profile_picture) }}" alt="Profile Picture" class="rounded-full object-cover border-4 border-white/10" style="width: 96px; height: 96px;">
                        @else
                            <div class="rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center border-4 border-white/10" style="width: 96px; height: 96px;">
                                <span class="text-4xl font-bold text-white">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            </div>
                        @endif
                    </div>
                    
                    <div class="flex-grow">
                        <form method="POST" action="{{ route('profile.uploadPicture') }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <div>
                                <label for="profile_picture" class="block text-sm font-medium text-white/70 mb-2">
                                    Choose a new profile picture
                                </label>
                                <input type="file" name="profile_picture" id="profile_picture" accept="image/*" class="block w-full text-sm text-white/70
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-lg file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-blue-600 file:text-white
                                    hover:file:bg-blue-700
                                    file:cursor-pointer cursor-pointer
                                    border border-white/10 rounded-lg
                                    focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                <p class="mt-2 text-xs text-white/50">Accepted formats: JPG, PNG, GIF (Max: 2MB)</p>
                            </div>
                            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition" style="margin-top: 16px;">
                                Upload Picture
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            </div>

        </div>
    </div>
@endsection

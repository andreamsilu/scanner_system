<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Admin</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-50">
        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 w-72 bg-white shadow-lg">
            <!-- Logo -->
            <div class="flex items-center justify-center h-20 border-b border-gray-100">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3">
                    <x-application-logo class="block h-10 w-auto fill-current text-gray-800" />
                    <span class="text-xl font-bold text-gray-800">Admin Panel</span>
                </a>
            </div>

            <!-- Navigation -->
            <nav class="mt-8 px-4">
                <div class="space-y-1">
                    <a href="{{ route('admin.dashboard') }}" class="group flex items-center px-4 py-3 text-base font-medium rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <i class="fas fa-tachometer-alt mr-3 h-5 w-5 {{ request()->routeIs('admin.dashboard') ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}"></i>
                        Dashboard
                    </a>

                    <a href="{{ route('events.index') }}" class="group flex items-center px-4 py-3 text-base font-medium rounded-lg {{ request()->routeIs('events.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <i class="fas fa-calendar-alt mr-3 h-5 w-5 {{ request()->routeIs('events.*') ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}"></i>
                        Events
                    </a>

                    <a href="{{ route('attendees.index') }}" class="group flex items-center px-4 py-3 text-base font-medium rounded-lg {{ request()->routeIs('attendees.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <i class="fas fa-users mr-3 h-5 w-5 {{ request()->routeIs('attendees.*') ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}"></i>
                        Attendees
                    </a>

                    <a href="{{ route('users.index') }}" class="group flex items-center px-4 py-3 text-base font-medium rounded-lg {{ request()->routeIs('users.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <i class="fas fa-user-cog mr-3 h-5 w-5 {{ request()->routeIs('users.*') ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}"></i>
                        Users
                    </a>

                    <a href="{{ route('activity-logs.index') }}" class="group flex items-center px-4 py-3 text-base font-medium rounded-lg {{ request()->routeIs('activity-logs.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <i class="fas fa-history mr-3 h-5 w-5 {{ request()->routeIs('activity-logs.*') ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}"></i>
                        Activity Logs
                    </a>
                </div>
            </nav>

            <!-- User Menu -->
            <div class="absolute bottom-0 w-full border-t border-gray-100 p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <img class="h-10 w-10 rounded-full ring-2 ring-gray-100" src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=0D8ABC&color=fff" alt="{{ Auth::user()->name }}">
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-700">{{ Auth::user()->name }}</p>
                        <div class="flex space-x-3 mt-1">
                            <a href="{{ route('profile.edit') }}" class="text-xs text-gray-500 hover:text-blue-600 transition-colors duration-200">
                                <i class="fas fa-user-edit mr-1"></i>Profile
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-xs text-gray-500 hover:text-red-600 transition-colors duration-200">
                                    <i class="fas fa-sign-out-alt mr-1"></i>Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="pl-72">
            <main class="py-8">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
</body>
</html> 
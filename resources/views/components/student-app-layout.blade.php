<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Transcript System') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                    },
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom Styles -->
    <style>
        [x-cloak] { display: none !important; }
        
        .gradient-bg {
            background: linear-gradient(135deg, #0ea5e9 0%, #075985 100%);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #0ea5e9 0%, #075985 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .gradient-border {
            border-image: linear-gradient(135deg, #0ea5e9 0%, #075985 100%) 1;
        }
    </style>
    
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen flex">
        <!-- Mobile Backdrop -->
        <div 
            x-show="sidebarOpen" 
            x-transition:enter="transition-opacity ease-in-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in-out duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-black bg-opacity-50 md:hidden"
        ></div>

        <!-- Sidebar -->
        <aside 
            x-cloak
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-gray-200 shadow-lg transform transition-transform duration-300 ease-in-out md:translate-x-0 md:static md:shadow-none md:min-h-screen md:w-64 flex-shrink-0"
        >
            <!-- Logo and Close Button -->
            <div class="flex items-center justify-between h-16 px-6 border-b border-gray-200">
                <h1 class="text-xl font-bold gradient-text">SLCE</h1>
                <button 
                    @click="sidebarOpen = false"
                    class="md:hidden text-gray-500 hover:text-gray-600 focus:outline-none focus:text-gray-600"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Navigation -->
            <nav class="p-4 space-y-1">
                <a 
                    href="{{ route('student.dashboard') }}" 
                    @click="sidebarOpen = false"
                    class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('student.dashboard') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}"
                >
                    <i class="fas fa-tachometer-alt w-5"></i>
                    <span>Dashboard</span>
                </a>
                
                <a 
                    href="{{ route('student.results') }}" 
                    @click="sidebarOpen = false"
                    class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('student.results') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}"
                >
                    <i class="fas fa-chart-bar w-5"></i>
                    <span>Results</span>
                </a>

                <a href="{{ route('student.profile.edit') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('student.profile.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-user w-5"></i>
                    <span>Edit Profile</span>
                </a>

                <a href="{{ route('student.change-password') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('student.change-password') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-key w-5"></i>
                    <span>Change Password</span>
                </a>

            
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-h-screen">
            <!-- Top Navigation -->
            <header class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-30">
                <div class="flex items-center justify-between h-16 px-4 md:px-6">
                    <!-- Mobile Menu Button -->
                    <button 
                        @click="sidebarOpen = !sidebarOpen" 
                        class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500"
                    >
                        <span class="sr-only">Open menu</span>
                        <svg 
                            :class="{'hidden': sidebarOpen, 'block': !sidebarOpen }" 
                            class="h-6 w-6" 
                            fill="none" 
                            viewBox="0 0 24 24" 
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg 
                            :class="{'block': sidebarOpen, 'hidden': !sidebarOpen }" 
                            class="h-6 w-6" 
                            fill="none" 
                            viewBox="0 0 24 24" 
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    <!-- Mobile Title -->
                    <div class="md:hidden font-semibold text-lg text-gray-800">
                        {{ $header ?? __('Dashboard') }}
                    </div>
                    
                    <!-- Right Navigation -->
                    <div class="flex items-center space-x-4">
                        <!-- User Menu -->
                        <div x-data="{ open: false }" class="relative">
                            <button 
                                @click="open = !open" 
                                class="flex items-center space-x-3 text-gray-600 hover:text-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 rounded-full p-1"
                            >
                                @php
                                    $student = Auth::user()->student;
                                    $name = $student ? $student->full_name : 'Student';
                                    $indexNumber = $student ? $student->index_number : '';
                                    $profileUrl = $student && $student->profile_photo 
                                        ? asset('storage/' . $student->profile_photo)
                                        : 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&color=7F9CF5&background=EBF4FF';
                                @endphp
                                <img 
                                    src="{{ $profileUrl }}" 
                                    alt="{{ $name }}" 
                                    class="w-8 h-8 rounded-full object-cover"
                                >
                                <span class="hidden md:block">{{ $name }}{{ $indexNumber ? ' - ' . $indexNumber : '' }}</span>
                            </button>
                            
                            <!-- User Menu Panel -->
                            <div 
                                x-show="open" 
                                @click.away="open = false" 
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="transform opacity-100 scale-100"
                                x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute right-0 z-10 w-56 mt-3 origin-top-right bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden"
                            >
                                <div class="py-1">
                                    <a href="{{ route('student.profile.edit') }}" 
                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary-600 {{ request()->routeIs('student.profile.*') ? 'bg-gray-50 text-primary-600' : '' }}">
                                        <i class="fas fa-user fa-fw mr-2"></i>
                                        Edit Profile
                                    </a>
                                    <a href="{{ route('student.change-password') }}" 
                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary-600 {{ request()->routeIs('student.change-password') ? 'bg-gray-50 text-primary-600' : '' }}">
                                        <i class="fas fa-key fa-fw mr-2"></i>
                                        Change Password
                                    </a>
                                    <hr class="my-1 border-gray-200">
                                <form method="POST" action="{{ route('student.logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary-600 rounded-md">
                                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                                    </button>
                                </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <main class="flex-1 flex flex-col px-4 md:px-6 py-4">
                <!-- Flash Messages -->
                @if (session('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="p-3 bg-green-50 border-l-4 border-green-500 text-green-700 flex justify-between items-center">
                        <div>
                            <i class="fas fa-check-circle mr-2"></i>
                            {{ session('success') }}
                        </div>
                        <button @click="show = false" class="text-green-700 hover:text-green-900">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif
                
                @if (session('error'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="p-3 bg-red-50 border-l-4 border-red-500 text-red-700 flex justify-between items-center">
                        <div>
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            {{ session('error') }}
                        </div>
                        <button @click="show = false" class="text-red-700 hover:text-red-900">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif
                
                <!-- Page Header -->
                @if (isset($header))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-2">
                        <div class="p-3 bg-white border-b border-gray-200">
                            {{ $header }}
                        </div>
                    </div>
                @else
                    <div class="py-2 mt-1">
                        <h1 class="text-2xl font-bold text-gray-800">@yield('title', 'Dashboard')</h1>
                        <p class="text-sm text-gray-500">@yield('subtitle', 'Welcome to the Transcript Management System')</p>
                    </div>
                @endif
                
                <!-- Content -->
                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </main>
            
            <!-- Footer -->
            <footer class="py-4 px-6 border-t border-gray-200">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-sm text-gray-500">
                        &copy; {{ date('Y') }} Transcript Management System. All rights reserved.
                    </p>
                    <div class="mt-2 md:mt-0">
                        <a href="#" class="text-sm text-gray-500 hover:text-primary-600 mr-4">Privacy Policy</a>
                        <a href="#" class="text-sm text-gray-500 hover:text-primary-600">Terms of Service</a>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    
    @stack('scripts')
</body>
</html>

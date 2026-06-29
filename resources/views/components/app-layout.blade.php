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
            background: linear-gradient(135deg, #0c9b13 0%, #0a6107 100%);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #0c9c24 0%, #064b07 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .gradient-border {
            border-image: linear-gradient(135deg, #41a10d 0%, #1f9330 100%) 1;
        }
    </style>
    
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen flex">
        <!-- Sidebar -->
        <aside 
            x-cloak
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 shadow-lg transform transition-transform duration-300 ease-in-out md:translate-x-0 md:static md:shadow-none md:min-h-screen md:w-64 flex-shrink-0"
        >
            <!-- Logo -->
            <div class="flex items-center justify-center h-16 px-6 border-b border-gray-200">
                <h1 class="text-xl font-bold gradient-text">Transcript System</h1>
            </div>
            
            <!-- Navigation -->
            <nav class="p-4 space-y-1">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-tachometer-alt w-5"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="{{ route('students.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('students.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-user-graduate w-5"></i>
                    <span>Students</span>
                </a>
                
                <a href="{{ route('programmes.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('programmes.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-graduation-cap w-5"></i>
                    <span>Programmes</span>
                </a>
                
                <a href="{{ route('semesters.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('semesters.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-calendar-alt w-5"></i>
                    <span>Semesters</span>
                </a>
                
                <a href="{{ route('courses.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('courses.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-book w-5"></i>
                    <span>Courses</span>
                </a>
                
                <a href="{{ route('results.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('results.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-chart-bar w-5"></i>
                    <span>Results</span>
                </a>
                
                <a href="{{ route('transcripts.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('transcripts.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-file-alt w-5"></i>
                    <span>Transcripts</span>
                </a>
                
                <a href="{{ route('reports.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('reports.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-chart-pie w-5"></i>
                    <span>Reports</span>
                </a>
                <a href="{{ route('gpa-distribution.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('settings.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-chart-line w-5"></i>
                    <span>CGPA Distribution</span>
                </a>
                
                <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('settings.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-cog w-5"></i>
                    <span>Settings</span>
                </a>
                
                @if (auth()->user()->role === 'admin')
                <a href="{{ route('activity-logs.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('activity-logs.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-history w-5"></i>
                    <span>Activity Logs</span>
                </a>

                <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('users.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-users-cog w-5"></i>
                    <span>User Management</span>
                </a>
                @endif
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-h-screen">
            <!-- Top Navigation -->
            <header class="bg-white border-b border-gray-200 shadow-sm">
                <div class="flex items-center justify-between h-14 px-4 md:px-6">
                    <!-- Mobile Menu Button -->
                    <button 
                        @click="sidebarOpen = !sidebarOpen" 
                        class="text-gray-600 md:hidden focus:outline-none"
                    >
                        <i class="fas fa-bars text-lg"></i>
                    </button>
                    
                    <!-- Page Title - Mobile -->
                    <div class="md:hidden font-semibold text-lg text-gray-800">
                        {{ $header ?? __('Dashboard') }}
                    </div>
                    
                    <!-- Search -->
                    <div class="hidden md:flex md:flex-1 md:max-w-md">
                        <div class="relative w-full">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input 
                                type="text" 
                                class="w-full py-2 pl-10 pr-4 text-sm text-gray-700 bg-gray-100 border-0 rounded-lg focus:bg-white focus:ring-2 focus:ring-primary-500 focus:outline-none" 
                                placeholder="Search..."
                            >
                        </div>
                    </div>
                    
                    <!-- Right Navigation -->
                    <div class="flex items-center space-x-4">
                        <!-- Notifications -->
                        <div x-data="{ open: false }" class="relative">
                            <button 
                                @click="open = !open" 
                                class="p-2 text-gray-600 transition-colors duration-200 rounded-full hover:bg-gray-100 hover:text-primary-600 focus:outline-none"
                            >
                                <i class="fas fa-bell"></i>
                            </button>
                            
                            <!-- Dropdown -->
                            <div 
                                x-show="open" 
                                @click.away="open = false" 
                                x-transition 
                                class="absolute right-0 z-10 w-80 mt-2 origin-top-right bg-white border border-gray-200 rounded-lg shadow-lg"
                            >
                                <div class="p-3 border-b border-gray-200">
                                    <h3 class="text-sm font-semibold text-gray-700">Notifications</h3>
                                </div>
                                <div class="max-h-64 overflow-y-auto">
                                    <div class="p-4 text-sm text-gray-500">
                                        No new notifications
                                    </div>
                                </div>
                                <div class="p-2 border-t border-gray-200">
                                    <a href="#" class="block px-4 py-2 text-xs font-medium text-center text-primary-600 hover:underline">
                                        View all notifications
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Menu -->
                        <div x-data="{ open: false }" class="relative">
                            <button 
                                @click="open = !open" 
                                class="flex items-center space-x-2 focus:outline-none"
                            >
                                <div class="w-8 h-8 overflow-hidden rounded-full bg-primary-100 flex items-center justify-center">
                                    <i class="fas fa-user text-primary-600"></i>
                                </div>
                                <span class="hidden md:block text-sm font-medium text-gray-700">{{ Auth::user()->name ?? 'User' }}</span>
                                <i class="hidden md:block fas fa-chevron-down text-xs text-gray-400"></i>
                            </button>
                            
                            <!-- Dropdown -->
                            <div 
                                x-show="open" 
                                @click.away="open = false" 
                                x-transition 
                                class="absolute right-0 z-10 w-48 mt-2 origin-top-right bg-white border border-gray-200 rounded-lg shadow-lg"
                            >
                                <div class="p-2">
                                    <a href="{{ route('profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary-600 rounded-md">
                                        <i class="fas fa-user-circle mr-2"></i> Profile
                                    </a>
                                    <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary-600 rounded-md">
                                        <i class="fas fa-cog mr-2"></i> Settings
                                    </a>
                                    <hr class="my-1 border-gray-200">
                                    <form method="POST" action="{{ route('logout') }}">
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

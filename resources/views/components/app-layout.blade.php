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

        /* Desktop sidebar collapse (icon-only rail). Scoped to md+ so mobile's
           overlay sidebar always shows full labels regardless of this state. */
        @media (min-width: 768px) {
            .sidebar-collapsed nav a > span,
            .sidebar-collapsed nav button > span > span,
            .sidebar-collapsed nav button > i.fa-chevron-down {
                display: none;
            }

            .sidebar-collapsed nav a,
            .sidebar-collapsed nav button {
                justify-content: center;
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }

            .sidebar-collapsed nav .pl-8 {
                display: none;
            }
        }
    </style>
    
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50">
    <div
        x-data="{ sidebarOpen: false, sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true' }"
        class="min-h-screen flex"
    >
        <!-- Sidebar -->
        <aside
            x-cloak
            :class="{
                'translate-x-0': sidebarOpen,
                '-translate-x-full': !sidebarOpen,
                'md:w-20': sidebarCollapsed,
                'md:w-64': !sidebarCollapsed,
                'sidebar-collapsed': sidebarCollapsed,
            }"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 shadow-lg transform transition-all duration-300 ease-in-out md:translate-x-0 md:static md:shadow-none md:min-h-screen flex-shrink-0"
        >
            <!-- Collapse toggle (desktop only) -->
            <button
                @click="sidebarCollapsed = !sidebarCollapsed; localStorage.setItem('sidebarCollapsed', sidebarCollapsed)"
                class="hidden md:flex absolute -right-3 top-20 w-6 h-6 items-center justify-center rounded-full bg-white border border-gray-300 shadow-sm text-gray-500 hover:text-primary-600 hover:border-primary-300 transition-colors z-10"
                :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            >
                <i class="fas text-[10px]" :class="sidebarCollapsed ? 'fa-chevron-right' : 'fa-chevron-left'"></i>
            </button>

            <!-- Logo -->
            <div class="flex items-center justify-center h-16 px-4 border-b border-gray-200 overflow-hidden">
                <h1 class="text-xl font-bold gradient-text whitespace-nowrap" x-show="!sidebarCollapsed" x-cloak>Transcript System</h1>
                <div class="w-9 h-9 rounded-lg gradient-bg flex items-center justify-center text-white font-bold text-sm flex-shrink-0" x-show="sidebarCollapsed" x-cloak>TS</div>
            </div>

            <!-- Navigation -->
            <nav class="p-4 space-y-1">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-tachometer-alt w-5"></i>
                    <span>Dashboard</span>
                </a>

                @if(auth()->user()->lecturer_id)
                <a href="{{ route('lecturer.timetable') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('lecturer.timetable') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-calendar-week w-5"></i>
                    <span>My Timetable</span>
                </a>

                <a href="{{ route('lecturer.profile.edit') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('lecturer.profile.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-id-badge w-5"></i>
                    <span>My Profile</span>
                </a>

                <a href="{{ route('sts-supervision.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('sts-supervision.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-user-graduate w-5"></i>
                    <span>My STS Students</span>
                </a>
                @endif

                @can('manage-students')
                <a href="{{ route('students.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('students.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-user-graduate w-5"></i>
                    <span>Students</span>
                </a>
                @endcan

                @can('manage-programmes')
                <a href="{{ route('programmes.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('programmes.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-graduation-cap w-5"></i>
                    <span>Programmes</span>
                </a>
                @endcan

                @can('manage-semesters')
                <a href="{{ route('semesters.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('semesters.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-calendar-alt w-5"></i>
                    <span>Semesters</span>
                </a>
                @endcan

                @can('manage-courses')
                <a href="{{ route('courses.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('courses.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-book w-5"></i>
                    <span>Courses</span>
                </a>

                <a href="{{ route('lecturers.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('lecturers.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-chalkboard-teacher w-5"></i>
                    <span>Lecturers</span>
                </a>

                <a href="{{ route('departments.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('departments.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-building w-5"></i>
                    <span>Departments</span>
                </a>
                @endcan

                @can('manage-results')
                <a href="{{ route('results.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('results.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-chart-bar w-5"></i>
                    <span>Results</span>
                </a>
                @endcan

                @can('manage-transcripts')
                <a href="{{ route('transcripts.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('transcripts.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-file-alt w-5"></i>
                    <span>Transcripts</span>
                </a>
                @endcan

                @can('view-reports')
                <a href="{{ route('reports.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('reports.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-chart-pie w-5"></i>
                    <span>Reports</span>
                </a>
                @endcan

                @can('manage-fees')
                <div x-data="{ open: {{ request()->routeIs('fees.*') || request()->routeIs('fee-structures.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('fees.*') || request()->routeIs('fee-structures.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                        <span class="flex items-center gap-3">
                            <i class="fas fa-money-bill-wave w-5"></i>
                            <span>Fees</span>
                        </span>
                        <i class="fas fa-chevron-down text-xs transition-transform" :class="open ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open" x-transition class="pl-8 space-y-1">
                        <a href="{{ route('fee-structures.index') }}" class="block px-4 py-2 text-sm text-gray-600 hover:text-primary-600 rounded-lg {{ request()->routeIs('fee-structures.*') ? 'text-primary-600 font-medium' : '' }}">
                            Fee Structures
                        </a>
                        <a href="{{ route('fees.index') }}" class="block px-4 py-2 text-sm text-gray-600 hover:text-primary-600 rounded-lg {{ request()->routeIs('fees.*') && !request()->routeIs('fees.arrears.*') ? 'text-primary-600 font-medium' : '' }}">
                            Payments
                        </a>
                        <a href="{{ route('fees.arrears.index') }}" class="block px-4 py-2 text-sm text-gray-600 hover:text-primary-600 rounded-lg {{ request()->routeIs('fees.arrears.*') ? 'text-primary-600 font-medium' : '' }}">
                            Arrears / Debtors
                        </a>
                    </div>
                </div>
                @endcan

                @can('manage-biometric')
                <div x-data="{ open: {{ request()->routeIs('biometric-*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('biometric-*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                        <span class="flex items-center gap-3">
                            <i class="fas fa-fingerprint w-5"></i>
                            <span>Biometric</span>
                        </span>
                        <i class="fas fa-chevron-down text-xs transition-transform" :class="open ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open" x-transition class="pl-8 space-y-1">
                        <a href="{{ route('biometric-devices.index') }}" class="block px-4 py-2 text-sm text-gray-600 hover:text-primary-600 rounded-lg {{ request()->routeIs('biometric-devices.*') ? 'text-primary-600 font-medium' : '' }}">
                            Devices
                        </a>
                        <a href="{{ route('biometric-verifications.index') }}" class="block px-4 py-2 text-sm text-gray-600 hover:text-primary-600 rounded-lg {{ request()->routeIs('biometric-verifications.*') ? 'text-primary-600 font-medium' : '' }}">
                            Verifications
                        </a>
                    </div>
                </div>
                @endcan

                @can('manage-classes')
                <a href="{{ route('class-groups.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('class-groups.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-users w-5"></i>
                    <span>Classes</span>
                </a>
                <a href="{{ route('promotions.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('promotions.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-level-up-alt w-5"></i>
                    <span>Promote Students</span>
                </a>
                @endcan

                @can('manage-timetable')
                <a href="{{ route('timetable.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('timetable.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-calendar-week w-5"></i>
                    <span>Timetable</span>
                </a>

                <a href="{{ route('venues.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('venues.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-map-marker-alt w-5"></i>
                    <span>Venues</span>
                </a>
                @endcan

                @can('manage-continuous-assessment')
                <a href="{{ route('continuous-assessment.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('continuous-assessment.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-tasks w-5"></i>
                    <span>Continuous Assessment</span>
                </a>
                @endcan

                @can('manage-sts')
                <a href="{{ route('sts-terms.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('sts-terms.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-school w-5"></i>
                    <span>STS Terms</span>
                </a>
                <a href="{{ route('partner-schools.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('partner-schools.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-map-marked-alt w-5"></i>
                    <span>Partner Schools</span>
                </a>
                <a href="{{ route('sts-placements.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('sts-placements.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-user-check w-5"></i>
                    <span>STS Placements</span>
                </a>
                <a href="{{ route('sts-score-settings.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('sts-score-settings.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-star-half-alt w-5"></i>
                    <span>STS Score Settings</span>
                </a>
                @endcan

                @can('send-sms')
                <div x-data="{ open: {{ request()->routeIs('sms.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="w-full flex items-center justify-between gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('sms.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                        <span class="flex items-center gap-3">
                            <i class="fas fa-sms w-5"></i>
                            <span>Bulk SMS</span>
                        </span>
                        <i class="fas fa-chevron-down text-xs transition-transform" :class="open ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open" x-transition class="pl-8 space-y-1">
                        <a href="{{ route('sms.index') }}" class="block px-4 py-2 text-sm text-gray-600 hover:text-primary-600 rounded-lg {{ request()->routeIs('sms.index') ? 'text-primary-600 font-medium' : '' }}">
                            Send SMS
                        </a>
                        <a href="{{ route('sms.history') }}" class="block px-4 py-2 text-sm text-gray-600 hover:text-primary-600 rounded-lg {{ request()->routeIs('sms.history*') ? 'text-primary-600 font-medium' : '' }}">
                            Sent Messages
                        </a>
                        <a href="{{ route('sms.templates.index') }}" class="block px-4 py-2 text-sm text-gray-600 hover:text-primary-600 rounded-lg {{ request()->routeIs('sms.templates.*') ? 'text-primary-600 font-medium' : '' }}">
                            Templates
                        </a>
                    </div>
                </div>
                @endcan

                @can('view-reports')
                <a href="{{ route('gpa-distribution.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('gpa-distribution.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-chart-line w-5"></i>
                    <span>CGPA Distribution</span>
                </a>
                @endcan

                @can('manage-settings')
                <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('settings.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-cog w-5"></i>
                    <span>Settings</span>
                </a>
                @endcan

                @can('view-activity-logs')
                <a href="{{ route('activity-logs.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('activity-logs.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-history w-5"></i>
                    <span>Activity Logs</span>
                </a>
                @endcan

                @can('manage-users')
                <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('users.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-users-cog w-5"></i>
                    <span>User Management</span>
                </a>
                @endcan

                @can('manage-roles')
                <a href="{{ route('roles.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 transition-all duration-200 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('roles.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-user-shield w-5"></i>
                    <span>Roles & Permissions</span>
                </a>
                @endcan
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

                @if (session('warning'))
                    <div x-data="{ show: true }" x-show="show" class="p-3 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 flex justify-between items-center">
                        <div>
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            {{ session('warning') }}
                        </div>
                        <button @click="show = false" class="text-yellow-700 hover:text-yellow-900">
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

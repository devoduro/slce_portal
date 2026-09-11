<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'College MIS') }}</title>

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

            /* Section labels have no room on the icon-only rail - keep the grouping visible as
               a divider rule instead of dropping it entirely. */
            .sidebar-collapsed nav .nav-heading {
                height: 1px;
                padding: 0;
                margin: 0.75rem 0.5rem;
                overflow: hidden;
                color: transparent;
                background-color: #334155;
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
            class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 border-r border-slate-800 shadow-lg transform transition-all duration-300 ease-in-out md:translate-x-0 md:static md:shadow-none md:min-h-screen flex-shrink-0 overflow-y-auto"
        >
            <!-- Collapse toggle (desktop only) -->
            <button
                @click="sidebarCollapsed = !sidebarCollapsed; localStorage.setItem('sidebarCollapsed', sidebarCollapsed)"
                class="hidden md:flex absolute -right-3 top-20 w-6 h-6 items-center justify-center rounded-full bg-slate-800 border border-slate-600 shadow-sm text-slate-300 hover:text-white hover:border-primary-400 transition-colors z-10"
                :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            >
                <i class="fas text-[10px]" :class="sidebarCollapsed ? 'fa-chevron-right' : 'fa-chevron-left'"></i>
            </button>

            <!-- Logo -->
            <div class="flex items-center justify-center h-16 px-4 border-b border-slate-800 overflow-hidden">
                <h1 class="text-xl font-bold text-white whitespace-nowrap" x-show="!sidebarCollapsed" x-cloak>College AIMS</h1>
                <div class="w-9 h-9 rounded-lg gradient-bg flex items-center justify-center text-white font-bold text-sm flex-shrink-0" x-show="sidebarCollapsed" x-cloak>TS</div>
            </div>

            <nav class="p-4 space-y-1">
                <x-nav-link href="{{ route('dashboard') }}" icon="fa-tachometer-alt" :active="request()->routeIs('dashboard')">
                    Dashboard
                </x-nav-link>

                {{-- ── My Workspace: a lecturer's own teaching load ───────────────────────── --}}
                @if(auth()->user()->lecturer_id)
                    <x-nav-heading>My Workspace</x-nav-heading>

                    <x-nav-link href="{{ route('lecturer.timetable') }}" icon="fa-calendar-week" :active="request()->routeIs('lecturer.timetable')">
                        My Timetable
                    </x-nav-link>

                    <x-nav-link href="{{ route('lecturer.profile.edit') }}" icon="fa-id-badge" :active="request()->routeIs('lecturer.profile.*')">
                        My Profile
                    </x-nav-link>

                    <x-nav-link href="{{ route('sts-supervision.index') }}" icon="fa-user-graduate" :active="request()->routeIs('sts-supervision.*')">
                        My STS Students
                    </x-nav-link>
                @endif

                {{-- ── Student Records ────────────────────────────────────────────────────── --}}
                @canany(['manage-students', 'view-student-directory', 'manage-classes', 'view-graduates'])
                    <x-nav-heading>Student Records</x-nav-heading>
                @endcanany

                @can('manage-students')
                    <x-nav-link href="{{ route('students.index') }}" icon="fa-user-graduate" :active="request()->routeIs('students.*')">
                        Students
                    </x-nav-link>
                @endcan

                @cannot('manage-students')
                    @can('view-student-directory')
                        <x-nav-link href="{{ route('student-directory.index') }}" icon="fa-user-graduate" :active="request()->routeIs('student-directory.*')">
                            Students
                        </x-nav-link>
                    @endcan
                @endcannot

                @can('manage-classes')
                    <x-nav-link href="{{ route('class-groups.index') }}" icon="fa-users" :active="request()->routeIs('class-groups.*')">
                        Classes
                    </x-nav-link>

                    <x-nav-link href="{{ route('promotions.index') }}" icon="fa-level-up-alt" :active="request()->routeIs('promotions.*')">
                        Promote Students
                    </x-nav-link>
                @endcan

                @can('view-graduates')
                    <x-nav-link href="{{ route('graduates.index') }}" icon="fa-user-tie" :active="request()->routeIs('graduates.*')">
                        Graduates
                    </x-nav-link>
                @endcan

                @can('manage-students')
                    <x-nav-link href="{{ route('student-halls.index') }}" icon="fa-building" :active="request()->routeIs('student-halls.*')">
                        Student Halls
                    </x-nav-link>
                @endcan

                {{-- ── Academics: the structures students are taught within ───────────────── --}}
                @canany(['manage-programmes', 'manage-courses', 'manage-semesters'])
                    <x-nav-heading>Academics</x-nav-heading>
                @endcanany

                @can('manage-programmes')
                    <x-nav-link href="{{ route('programmes.index') }}" icon="fa-graduation-cap" :active="request()->routeIs('programmes.*')">
                        Programmes
                    </x-nav-link>
                @endcan

                @can('manage-courses')
                    <x-nav-link href="{{ route('courses.index') }}" icon="fa-book" :active="request()->routeIs('courses.*')">
                        Courses
                    </x-nav-link>

                    <x-nav-link href="{{ route('departments.index') }}" icon="fa-sitemap" :active="request()->routeIs('departments.*')">
                        Departments
                    </x-nav-link>

                    <x-nav-link href="{{ route('lecturers.index') }}" icon="fa-chalkboard-teacher" :active="request()->routeIs('lecturers.*')">
                        Lecturers
                    </x-nav-link>
                @endcan

                @can('manage-semesters')
                    <x-nav-link href="{{ route('semesters.index') }}" icon="fa-calendar-alt" :active="request()->routeIs('semesters.*')">
                        Semesters
                    </x-nav-link>
                @endcan

                {{-- ── Assessment: what students are graded and certified on ──────────────── --}}
                @canany(['manage-results', 'view-results', 'manage-continuous-assessment', 'manage-transcripts', 'view-reports'])
                    <x-nav-heading>Assessment</x-nav-heading>
                @endcanany

                @canany(['manage-results', 'view-results'])
                    <x-nav-link href="{{ route('results.index') }}" icon="fa-chart-bar" :active="request()->routeIs('results.*')">
                        Results
                    </x-nav-link>
                @endcanany

                @can('manage-continuous-assessment')
                    <x-nav-link href="{{ route('continuous-assessment.index') }}" icon="fa-tasks" :active="request()->routeIs('continuous-assessment.*')">
                        Continuous Assessment
                    </x-nav-link>
                @endcan

                @can('manage-transcripts')
                    <x-nav-link href="{{ route('transcripts.index') }}" icon="fa-file-alt" :active="request()->routeIs('transcripts.*')">
                        Transcripts
                    </x-nav-link>
                @endcan

                @can('view-reports')
                    <x-nav-link href="{{ route('gpa-distribution.index') }}" icon="fa-chart-line" :active="request()->routeIs('gpa-distribution.*')">
                        CGPA Distribution
                    </x-nav-link>

                    <x-nav-link href="{{ route('reports.index') }}" icon="fa-chart-pie" :active="request()->routeIs('reports.*')">
                        Reports
                    </x-nav-link>
                @endcan

                {{-- ── Finance ────────────────────────────────────────────────────────────── --}}
                @can('manage-fees')
                    <x-nav-heading>Finance</x-nav-heading>

                    <x-nav-group label="Fees" icon="fa-money-bill-wave"
                                 :active="request()->routeIs('fees.*') || request()->routeIs('fee-structures.*')">
                        <x-nav-sublink href="{{ route('fee-structures.index') }}" :active="request()->routeIs('fee-structures.*')">
                            Fee Structures
                        </x-nav-sublink>
                        <x-nav-sublink href="{{ route('fees.index') }}" :active="request()->routeIs('fees.index') || request()->routeIs('fees.show')">
                            Student Fees
                        </x-nav-sublink>
                        <x-nav-sublink href="{{ route('fees.arrears.index') }}" :active="request()->routeIs('fees.arrears.*')">
                            Arrears / Debtors
                        </x-nav-sublink>
                        @can('view-graduates')
                            <x-nav-sublink href="{{ route('graduates.index', ['status' => 'owing']) }}" :active="request()->routeIs('graduates.*') && request('status') === 'owing'">
                                Graduates Owing
                            </x-nav-sublink>
                        @endcan
                        <x-nav-sublink href="{{ route('fees.payments.upload') }}" :active="request()->routeIs('fees.payments.upload')">
                            Upload Payments
                        </x-nav-sublink>
                        <x-nav-sublink href="{{ route('fees.reference-numbers.index') }}" :active="request()->routeIs('fees.reference-numbers.*')">
                            Reference Numbers
                        </x-nav-sublink>
                        <x-nav-sublink href="{{ route('fees.report') }}" :active="request()->routeIs('fees.report')">
                            Fees Report
                        </x-nav-sublink>
                    </x-nav-group>
                @endcan

                {{-- ── Admissions ─────────────────────────────────────────────────────────── --}}
                @canany(['manage-admissions', 'manage-admission-payments'])
                    <x-nav-heading>Admissions</x-nav-heading>
                @endcanany

                @can('manage-admissions')
                    <x-nav-group label="Admissions" icon="fa-user-plus" :active="request()->routeIs('admissions.*') || request()->routeIs('settings.admission') || request()->routeIs('admission-letter-templates.*')">
                        <x-nav-sublink href="{{ route('admissions.index') }}" :active="request()->routeIs('admissions.index') || request()->routeIs('admissions.show') || request()->routeIs('admissions.create')">
                            Admissions
                        </x-nav-sublink>
                        <x-nav-sublink href="{{ route('admissions.import.form') }}" :active="request()->routeIs('admissions.import*')">
                            Import Admissions
                        </x-nav-sublink>
                        <x-nav-sublink href="{{ route('admissions.halls') }}" :active="request()->routeIs('admissions.halls*')">
                            Halls
                        </x-nav-sublink>
                        <x-nav-sublink href="{{ route('admission-letter-templates.index') }}" :active="request()->routeIs('admission-letter-templates.*')">
                            Admission Letter
                        </x-nav-sublink>
                        <x-nav-sublink href="{{ route('settings.admission') }}" :active="request()->routeIs('settings.admission')">
                            Principal Signature
                        </x-nav-sublink>
                    </x-nav-group>
                @endcan

                @can('manage-admission-payments')
                    <x-nav-link href="{{ route('admission-billing.index') }}" icon="fa-file-invoice-dollar" :active="request()->routeIs('admission-billing.*')">
                        Admission Billing
                    </x-nav-link>
                @endcan

                {{-- ── Timetable & Attendance ─────────────────────────────────────────────── --}}
                @canany(['manage-timetable', 'manage-biometric'])
                    <x-nav-heading>Timetable &amp; Attendance</x-nav-heading>
                @endcanany

                @can('manage-timetable')
                    <x-nav-link href="{{ route('timetable.index') }}" icon="fa-calendar-week" :active="request()->routeIs('timetable.*')">
                        Timetable
                    </x-nav-link>

                    <x-nav-link href="{{ route('venues.index') }}" icon="fa-map-marker-alt" :active="request()->routeIs('venues.*')">
                        Venues
                    </x-nav-link>
                @endcan

                @can('manage-biometric')
                    <x-nav-group label="Biometric" icon="fa-fingerprint" :active="request()->routeIs('biometric-*')">
                        <x-nav-sublink href="{{ route('biometric-devices.index') }}" :active="request()->routeIs('biometric-devices.*')">
                            Devices
                        </x-nav-sublink>
                        <x-nav-sublink href="{{ route('biometric-verifications.index') }}" :active="request()->routeIs('biometric-verifications.*')">
                            Verifications
                        </x-nav-sublink>
                    </x-nav-group>
                @endcan

                {{-- ── Field Practice (STS) ───────────────────────────────────────────────── --}}
                @can('manage-sts')
                    <x-nav-heading>Field Practice</x-nav-heading>

                    <x-nav-group label="Teaching Practice" icon="fa-school"
                                 :active="request()->routeIs('sts-terms.*') || request()->routeIs('partner-schools.*') || request()->routeIs('sts-placements.*') || request()->routeIs('sts-score-settings.*') || request()->routeIs('settings.sts')">
                        <x-nav-sublink href="{{ route('sts-terms.index') }}" :active="request()->routeIs('sts-terms.*')">
                            STS Terms
                        </x-nav-sublink>
                        <x-nav-sublink href="{{ route('partner-schools.index') }}" :active="request()->routeIs('partner-schools.*')">
                            Partner Schools
                        </x-nav-sublink>
                        <x-nav-sublink href="{{ route('sts-placements.index') }}" :active="request()->routeIs('sts-placements.*')">
                            Placements
                        </x-nav-sublink>
                        <x-nav-sublink href="{{ route('sts-score-settings.index') }}" :active="request()->routeIs('sts-score-settings.*')">
                            Score Settings
                        </x-nav-sublink>
                        <x-nav-sublink href="{{ route('settings.sts') }}" :active="request()->routeIs('settings.sts')">
                            Coordinator Signature
                        </x-nav-sublink>
                    </x-nav-group>
                @endcan

                {{-- ── Communication ──────────────────────────────────────────────────────── --}}
                @can('send-sms')
                    <x-nav-heading>Communication</x-nav-heading>

                    <x-nav-group label="Bulk SMS" icon="fa-sms" :active="request()->routeIs('sms.*')">
                        <x-nav-sublink href="{{ route('sms.index') }}" :active="request()->routeIs('sms.index')">
                            Send SMS
                        </x-nav-sublink>
                        <x-nav-sublink href="{{ route('sms.history') }}" :active="request()->routeIs('sms.history*')">
                            Sent Messages
                        </x-nav-sublink>
                        <x-nav-sublink href="{{ route('sms.templates.index') }}" :active="request()->routeIs('sms.templates.*')">
                            Templates
                        </x-nav-sublink>
                    </x-nav-group>
                @endcan

                {{-- ── Administration ─────────────────────────────────────────────────────── --}}
                @canany(['manage-settings', 'manage-users', 'manage-roles', 'view-activity-logs'])
                    <x-nav-heading>Administration</x-nav-heading>
                @endcanany

                @can('manage-settings')
                    <x-nav-link href="{{ route('settings.index') }}" icon="fa-cog" :active="request()->routeIs('settings.index') || (request()->routeIs('settings.*') && !request()->routeIs('settings.sts') && !request()->routeIs('settings.admission'))">
                        Settings
                    </x-nav-link>
                @endcan

                @can('manage-users')
                    <x-nav-link href="{{ route('users.index') }}" icon="fa-users-cog" :active="request()->routeIs('users.*')">
                        User Management
                    </x-nav-link>
                @endcan

                @can('manage-roles')
                    <x-nav-link href="{{ route('roles.index') }}" icon="fa-user-shield" :active="request()->routeIs('roles.*')">
                        Roles &amp; Permissions
                    </x-nav-link>
                @endcan

                @can('view-activity-logs')
                    <x-nav-link href="{{ route('activity-logs.index') }}" icon="fa-history" :active="request()->routeIs('activity-logs.*')">
                        Activity Logs
                    </x-nav-link>
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

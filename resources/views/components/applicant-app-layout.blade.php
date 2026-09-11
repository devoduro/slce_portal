<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Transcript System') }} - Applicant Portal</title>

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
                            50: '#f0f9ff', 100: '#e0f2fe', 200: '#bae6fd', 300: '#7dd3fc',
                            400: '#38bdf8', 500: '#0ea5e9', 600: '#0284c7', 700: '#0369a1',
                            800: '#075985', 900: '#0c4a6e',
                        },
                    },
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                }
            }
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
        .gradient-text {
            background: linear-gradient(135deg, #0ea5e9 0%, #075985 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen flex">
        <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-black bg-opacity-50 md:hidden"></div>

        <!-- Sidebar -->
        <aside x-cloak :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-gray-200 shadow-lg transform transition-transform duration-300 ease-in-out md:translate-x-0 md:static md:shadow-none md:min-h-screen md:w-64 flex-shrink-0">
            <div class="flex items-center justify-between h-16 px-6 border-b border-gray-200">
                <h1 class="text-xl font-bold gradient-text">SLCE Admissions</h1>
                <button @click="sidebarOpen = false" class="md:hidden text-gray-500 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <nav class="p-4 space-y-1">
                <a href="{{ route('applicant.dashboard') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('applicant.dashboard') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-tachometer-alt w-5"></i>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('applicant.profile.edit') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('applicant.profile.*') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-user w-5"></i>
                    <span>My Information</span>
                </a>

                <a href="{{ route('applicant.letter') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-primary-50 hover:text-primary-600 rounded-lg">
                    <i class="fas fa-file-alt w-5"></i>
                    <span>Admission Letter</span>
                </a>

                <a href="{{ route('applicant.acceptance-form') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-primary-50 hover:text-primary-600 rounded-lg">
                    <i class="fas fa-file-signature w-5"></i>
                    <span>Acceptance Form</span>
                </a>

                <a href="{{ route('applicant.change-password') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-primary-50 hover:text-primary-600 rounded-lg {{ request()->routeIs('applicant.change-password') ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                    <i class="fas fa-key w-5"></i>
                    <span>Change Password</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-h-screen">
            <header class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-30">
                <div class="flex items-center justify-between h-16 px-4 md:px-6">
                    <button @click="sidebarOpen = !sidebarOpen" class="md:hidden text-gray-500 hover:text-gray-600">
                        <i class="fas fa-bars text-lg"></i>
                    </button>

                    <div class="md:hidden font-semibold text-lg text-gray-800">{{ $header ?? __('Dashboard') }}</div>

                    <div class="flex items-center space-x-4 ml-auto">
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="flex items-center space-x-3 text-gray-600 hover:text-primary-600 focus:outline-none">
                                <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
                                    <i class="fas fa-user text-primary-600"></i>
                                </div>
                                <span class="hidden md:block">{{ Auth::user()->name }} &middot; {{ Auth::user()->admission->applicant_number ?? '' }}</span>
                            </button>

                            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-10 w-56 mt-3 origin-top-right bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                                <div class="py-1">
                                    <a href="{{ route('applicant.profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary-600">
                                        <i class="fas fa-user fa-fw mr-2"></i> My Information
                                    </a>
                                    <a href="{{ route('applicant.change-password') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary-600">
                                        <i class="fas fa-key fa-fw mr-2"></i> Change Password
                                    </a>
                                    <hr class="my-1 border-gray-200">
                                    <form method="POST" action="{{ route('applicant.logout') }}">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary-600">
                                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 flex flex-col px-4 md:px-6 py-4">
                @if (session('success'))
                    <div class="p-3 mb-2 bg-green-50 border-l-4 border-green-500 text-green-700">
                        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="p-3 mb-2 bg-red-50 border-l-4 border-red-500 text-red-700">
                        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                    </div>
                @endif
                @if (session('warning'))
                    <div class="p-3 mb-2 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700">
                        <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('warning') }}
                    </div>
                @endif

                @if (isset($header))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-2">
                        <div class="p-3 bg-white border-b border-gray-200">{{ $header }}</div>
                    </div>
                @else
                    <div class="py-2 mt-1">
                        <h1 class="text-2xl font-bold text-gray-800">@yield('title', 'Dashboard')</h1>
                        <p class="text-sm text-gray-500">@yield('subtitle', '')</p>
                    </div>
                @endif

                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </main>

            <footer class="py-4 px-6 border-t border-gray-200">
                <p class="text-sm text-gray-500 text-center">&copy; {{ date('Y') }} St. Louis College of Education. All rights reserved.</p>
            </footer>
        </div>
    </div>

    @stack('scripts')
</body>
</html>

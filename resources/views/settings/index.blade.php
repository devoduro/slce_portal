<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Settings') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Settings Categories -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                <!-- Academic Years -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center mb-4">
                            <div class="rounded-full p-3 bg-blue-100 mr-4">
                                <i class="fas fa-calendar-alt text-blue-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">Academic Years</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Manage academic years and their associated semesters.</p>
                        <a href="{{ route('settings.academic-years') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-800 focus:outline-none focus:border-primary-800 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                            Manage
                        </a>
                    </div>
                </div>
                
                <!-- Grade Schemes -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center mb-4">
                            <div class="rounded-full p-3 bg-green-100 mr-4">
                                <i class="fas fa-chart-line text-green-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">Grade Schemes</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Configure grading scales, grade points, and remarks.</p>
                        <a href="{{ route('settings.grade-schemes') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-800 focus:outline-none focus:border-primary-800 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                            Manage
                        </a>
                    </div>
                </div>
                
                <!-- Classifications -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center mb-4">
                            <div class="rounded-full p-3 bg-purple-100 mr-4">
                                <i class="fas fa-award text-purple-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">Classifications</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Set up degree classifications based on CGPA ranges.</p>
                        <a href="{{ route('settings.classifications') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-800 focus:outline-none focus:border-primary-800 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                            Manage
                        </a>
                    </div>
                </div>
                
                <!-- CA Score Settings -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center mb-4">
                            <div class="rounded-full p-3 bg-teal-100 mr-4">
                                <i class="fas fa-tasks text-teal-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">CA Score Settings</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Set Continuous Assessment max marks (Attendance, Project, Assignment, Mid-Semester) per level.</p>
                        <a href="{{ route('ca-score-settings.index') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-800 focus:outline-none focus:border-primary-800 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                            Manage
                        </a>
                    </div>
                </div>

                <!-- Database Backup -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center mb-4">
                            <div class="rounded-full p-3 bg-yellow-100 mr-4">
                                <i class="fas fa-database text-yellow-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">Database Backup</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Create and manage database backups.</p>
                        <a href="{{ route('settings.backup') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-800 focus:outline-none focus:border-primary-800 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                            Manage
                        </a>
                    </div>
                </div>
                
                <!-- System Settings -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center mb-4">
                            <div class="rounded-full p-3 bg-red-100 mr-4">
                                <i class="fas fa-cog text-red-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">System Settings</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Configure general system settings and preferences.</p>
                        <a href="{{ route('settings.system') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-800 focus:outline-none focus:border-primary-800 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                            Manage
                        </a>
                    </div>
                </div>
                
                <!-- Institution Profile -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center mb-4">
                            <div class="rounded-full p-3 bg-indigo-100 mr-4">
                                <i class="fas fa-university text-indigo-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">Institution Profile</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Update institution details, logo, and contact information.</p>
                        <a href="{{ route('settings.institution') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-800 focus:outline-none focus:border-primary-800 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                            Manage
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Current Settings -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Current System Settings</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="flex justify-between py-3 border-b border-gray-200">
                                <span class="text-gray-600">Current Academic Year:</span>
                                <span class="font-medium">{{ $currentAcademicYear->name ?? 'Not set' }}</span>
                            </div>
                            
                            <div class="flex justify-between py-3 border-b border-gray-200">
                                <span class="text-gray-600">Current Semester:</span>
                                <span class="font-medium">{{ $currentSemester->name ?? 'Not set' }}</span>
                            </div>
                            
                            <div class="flex justify-between py-3 border-b border-gray-200">
                                <span class="text-gray-600">Institution Name:</span>
                                <span class="font-medium">{{ $institutionName ?? 'Not set' }}</span>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between py-3 border-b border-gray-200">
                                <span class="text-gray-600">System Version:</span>
                                <span class="font-medium">1.0.0</span>
                            </div>
                            
                            <div class="flex justify-between py-3 border-b border-gray-200">
                                <span class="text-gray-600">Last Backup:</span>
                                <span class="font-medium">{{ $lastBackup ?? 'Never' }}</span>
                            </div>
                            
                            <div class="flex justify-between py-3 border-b border-gray-200">
                                <span class="text-gray-600">Total Students:</span>
                                <span class="font-medium">{{ $totalStudents ?? '0' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <form action="{{ route('settings.backup.create') }}" method="POST" class="inline-block">
                            @csrf
                            <button type="submit" class="inline-flex justify-center items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-800 focus:outline-none focus:border-primary-800 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                                <i class="fas fa-database mr-2"></i> Create Backup
                            </button>
                        </form>

                        <a href="{{ route('settings.academic-years.create') }}" class="inline-flex justify-center items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-800 focus:outline-none focus:border-primary-800 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                            <i class="fas fa-calendar-plus mr-2"></i> New Academic Year
                        </a>
                        
                        <a href="{{ route('settings.grade-schemes.create') }}" class="inline-flex justify-center items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-800 focus:outline-none focus:border-primary-800 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                            <i class="fas fa-plus-circle mr-2"></i> New Grade Scheme
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Reports') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Report Types -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                <!-- GPA Distribution -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center mb-4">
                            <div class="rounded-full p-3 bg-blue-100 mr-4">
                                <i class="fas fa-chart-bar text-blue-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">GPA Distribution</h3>
                        </div>
                        <p class="text-gray-600 mb-4">View GPA distribution across programmes, semesters, or academic years.</p>
                        <a href="{{ route('reports.gpa-distribution') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-800 focus:outline-none focus:border-primary-800 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                            View Report
                        </a>
                    </div>
                </div>
                
                <!-- Course Performance -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center mb-4">
                            <div class="rounded-full p-3 bg-green-100 mr-4">
                                <i class="fas fa-book text-green-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">Course Performance</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Analyze student performance across different courses.</p>
                        <a href="{{ route('reports.course-performance') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-800 focus:outline-none focus:border-primary-800 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                            View Report
                        </a>
                    </div>
                </div>
                
                <!-- Student Performance -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center mb-4">
                            <div class="rounded-full p-3 bg-purple-100 mr-4">
                                <i class="fas fa-user-graduate text-purple-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">Student Performance</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Track individual student performance over time.</p>
                        <a href="{{ route('reports.student-performance') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-800 focus:outline-none focus:border-primary-800 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                            View Report
                        </a>
                    </div>
                </div>
                
                <!-- Programme Statistics -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center mb-4">
                            <div class="rounded-full p-3 bg-yellow-100 mr-4">
                                <i class="fas fa-graduation-cap text-yellow-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">Programme Statistics</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Compare performance metrics across different programmes.</p>
                        <a href="{{ route('reports.programme-statistics') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-800 focus:outline-none focus:border-primary-800 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                            View Report
                        </a>
                    </div>
                </div>
                
                <!-- Classification Distribution -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center mb-4">
                            <div class="rounded-full p-3 bg-red-100 mr-4">
                                <i class="fas fa-award text-red-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">Classification Distribution</h3>
                        </div>
                        <p class="text-gray-600 mb-4">View degree classification distribution across programmes.</p>
                        <a href="{{ route('reports.classification-distribution') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-800 focus:outline-none focus:border-primary-800 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                            View Report
                        </a>
                    </div>
                </div>
                
                <!-- Semester Comparison -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center mb-4">
                            <div class="rounded-full p-3 bg-indigo-100 mr-4">
                                <i class="fas fa-chart-line text-indigo-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">Semester Comparison</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Compare performance metrics across different semesters.</p>
                        <a href="{{ route('reports.semester-comparison') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-800 focus:outline-none focus:border-primary-800 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                            View Report
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Export Options -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Export Reports</h3>
                    
                    <form action="{{ route('reports.export-excel') }}" method="GET" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="report_type" class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                                <select id="report_type" name="report_type" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                                    <option value="gpa_distribution">GPA Distribution</option>
                                    <option value="course_performance">Course Performance</option>
                                    <option value="student_performance">Student Performance</option>
                                    <option value="programme_statistics">Programme Statistics</option>
                                    <option value="classification_distribution">Classification Distribution</option>
                                    <option value="semester_comparison">Semester Comparison</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                                <select id="academic_year_id" name="academic_year_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                                    <option value="">All Academic Years</option>
                                    @foreach($academicYears ?? [] as $year)
                                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <label for="format" class="block text-sm font-medium text-gray-700 mb-1">Format</label>
                                <select id="format" name="format" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                                    <option value="excel">Excel</option>
                                    <option value="pdf">PDF</option>
                                    <option value="csv">CSV</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-800 focus:outline-none focus:border-primary-800 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                                <i class="fas fa-download mr-2"></i> Export Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

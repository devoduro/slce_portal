@extends('components.app-layout')

@section('title', 'Bulk Upload Results')
@section('subtitle', 'Upload multiple student results via CSV file')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <!-- Header Section -->
        <div class="p-6 bg-gradient-to-r from-primary-600 to-primary-700">
            <h2 class="text-2xl font-bold text-white flex items-center">
                <i class="fas fa-cloud-upload-alt mr-3"></i>
                Upload Results
            </h2>
            <p class="mt-2 text-primary-100">
                Easily upload multiple student results using our bulk upload feature
            </p>
        </div>

        <!-- Main Content -->
        <div class="p-6">
            <!-- File Format Guide -->
            <div class="mb-6 bg-blue-50 rounded-lg p-4 border border-blue-100">
                <h3 class="text-sm font-semibold text-blue-800 flex items-center">
                    <i class="fas fa-info-circle mr-2"></i>
                    File Format Requirements
                </h3>
                <div class="mt-2 text-sm text-blue-700 space-y-1">
                    <p>• File must be in Excel (.xlsx, .xls) or CSV format</p>
                    <p>• Required columns: <code class="bg-blue-100 px-2 py-0.5 rounded">student_id</code>, <code class="bg-blue-100 px-2 py-0.5 rounded">grade</code></p>
                    <p>• Grade must be a valid grade from the grade scheme (e.g., A, B, C, etc.)
                </div>
            </div>

            <!-- Alert Messages -->
            @if(session('success'))
                <div class="mb-6 rounded-lg bg-green-50 p-4 border border-green-200">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 rounded-lg bg-red-50 p-4 border border-red-200">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('warning'))
                <div class="mb-6 rounded-lg bg-yellow-50 p-4 border border-yellow-200">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-yellow-800">{{ session('warning') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Upload Form -->
            <form id="uploadForm" action="{{ route('bulkresults.process') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                
                <!-- Academic Year Selection -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <label for="academic_year_id" class="block text-sm font-medium text-gray-700">
                        <i class="fas fa-calendar-alt mr-2 text-gray-400"></i>Academic Year
                    </label>
                    <select id="academic_year_id" name="academic_year_id" 
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 
                               focus:outline-none focus:ring-primary-500 focus:border-primary-500 
                               sm:text-sm rounded-md transition duration-150 ease-in-out" required>
                        <option value="">Select Academic Year</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}">{{ $year->name }}</option>
                        @endforeach
                    </select>
                    @error('academic_year_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Semester Selection -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <label for="semester_id" class="block text-sm font-medium text-gray-700">
                        <i class="fas fa-clock mr-2 text-gray-400"></i>Semester
                    </label>
                    <div class="relative mt-1">
                        <select id="semester_id" name="semester_id" 
                            class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 
                                   focus:outline-none focus:ring-primary-500 focus:border-primary-500 
                                   sm:text-sm rounded-md transition duration-150 ease-in-out" required disabled>
                            <option value="">Select Semester</option>
                        </select>
                        <div id="semester-loading" class="hidden absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-circle-notch fa-spin text-gray-400"></i>
                        </div>
                    </div>
                    @error('semester_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Course Selection -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <label for="course_id" class="block text-sm font-medium text-gray-700">
                        <i class="fas fa-book mr-2 text-gray-400"></i>Course
                    </label>
                    <div class="relative mt-1">
                        <select id="course_id" name="course_id" 
                            class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 
                                   focus:outline-none focus:ring-primary-500 focus:border-primary-500 
                                   sm:text-sm rounded-md transition duration-150 ease-in-out" required disabled>
                            <option value="">Select Course</option>
                        </select>
                        <div id="course-loading" class="hidden absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-circle-notch fa-spin text-gray-400"></i>
                        </div>
                    </div>
                    @error('course_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- File Upload -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <label for="result_file" class="block text-sm font-medium text-gray-700">
                        <i class="fas fa-file-csv mr-2 text-gray-400"></i>Result File (CSV)
                    </label>
                    <div class="mt-2">
                        <div class="flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-primary-500 transition-colors duration-200">
                            <div class="space-y-1 text-center">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                                <div class="flex text-sm text-gray-600">
                                    <label for="result_file" class="relative cursor-pointer rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500">
                                        <span>Upload a file</span>
                                        <input id="result_file" name="result_file" type="file" class="sr-only" accept=".csv,.txt,.xlsx,.xls" required>
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">Excel or CSV file up to 2MB</p>
                            </div>
                        </div>
                        <div id="file-name" class="mt-2 text-sm text-gray-500 hidden">
                            Selected file: <span class="font-medium"></span>
                        </div>
                    </div>
                    @error('result_file')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-4">
                    <button type="submit" id="submit-btn"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-upload mr-2"></i>
                        Upload and Process Results
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sample CSV Template -->
    <div class="mt-8 bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 bg-gray-50 border-b">
            <h3 class="text-lg font-medium text-gray-900">Sample CSV Template</h3>
        </div>
        <div class="p-4">
            <div class="bg-gray-800 rounded-lg p-4 font-mono text-sm">
                <pre class="text-green-400">student_id,grade
1001,A
1002,B+
1003,B</pre>
            </div>
            <div class="mt-4 flex justify-end space-x-4">
                <button type="button" onclick="downloadTemplate('csv')" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <i class="fas fa-file-csv mr-2"></i>
                    Download CSV Template
                </button>
                <button type="button" onclick="downloadTemplate('excel')" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <i class="fas fa-file-excel mr-2"></i>
                    Download Excel Template
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('uploadForm');
    const fileInput = document.getElementById('result_file');
    const fileNameDiv = document.getElementById('file-name');
    const submitBtn = document.getElementById('submit-btn');
    const academicYearSelect = document.getElementById('academic_year_id');
    const semesterSelect = document.getElementById('semester_id');
    const courseSelect = document.getElementById('course_id');
    const semesterLoading = document.getElementById('semester-loading');
    const courseLoading = document.getElementById('course-loading');

    // File input handling
    fileInput.addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name;
        if (fileName) {
            fileNameDiv.classList.remove('hidden');
            fileNameDiv.querySelector('span').textContent = fileName;
        } else {
            fileNameDiv.classList.add('hidden');
        }
    });

    // Drag and drop handling
    const dropZone = fileInput.closest('div');
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults (e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        dropZone.classList.add('border-primary-500', 'bg-primary-50');
    }

    function unhighlight(e) {
        dropZone.classList.remove('border-primary-500', 'bg-primary-50');
    }

    dropZone.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        
        if (files[0]) {
            fileNameDiv.classList.remove('hidden');
            fileNameDiv.querySelector('span').textContent = files[0].name;
        }
    }

    // When academic year changes, fetch semesters
    academicYearSelect.addEventListener('change', function() {
        semesterSelect.disabled = true;
        courseSelect.disabled = true;
        semesterSelect.innerHTML = '<option value="">Select Semester</option>';
        courseSelect.innerHTML = '<option value="">Select Course</option>';
        
        if (this.value) {
            semesterLoading.classList.remove('hidden');
            fetch(`/bulkresults/semesters?academic_year_id=${this.value}`)
                .then(response => response.json())
                .then(semesters => {
                    semesters.forEach(semester => {
                        const option = new Option(semester.name, semester.id);
                        semesterSelect.add(option);
                    });
                    semesterSelect.disabled = false;
                })
                .finally(() => {
                    semesterLoading.classList.add('hidden');
                });
        }
    });

    // When semester changes, fetch courses
    semesterSelect.addEventListener('change', function() {
        courseSelect.disabled = true;
        courseSelect.innerHTML = '<option value="">Select Course</option>';

        if (this.value) {
            courseLoading.classList.remove('hidden');
            fetch(`/bulkresults/courses?semester_id=${this.value}`)
                .then(response => response.json())
                .then(courses => {
                    courses.forEach(course => {
                        const option = new Option(`${course.code} - ${course.title}`, course.id);
                        courseSelect.add(option);
                    });
                    courseSelect.disabled = false;
                })
                .finally(() => {
                    courseLoading.classList.add('hidden');
                });
        }
    });

    // Form submission
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i>Processing...';
    });
});

// Download sample template
function downloadTemplate(type = 'csv') {
    if (type === 'csv') {
        const csv = 'student_id,grade\n1001,A\n1002,B+\n1003,B';
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.setAttribute('href', url);
        a.setAttribute('download', 'results_template.csv');
        a.click();
        window.URL.revokeObjectURL(url);
    } else {
        // For Excel template, redirect to a controller endpoint that generates Excel file
        window.location.href = '{{ route("bulkresults.template", ["type" => "excel"]) }}';
    }
}
</script>
@endpush
@endsection

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Bulk Upload Results') }}
            </h2>
            <div class="flex space-x-2">
                <x-button href="{{ route('results.index') }}" variant="secondary" icon="fas fa-arrow-left">
                    {{ __('Back to Results') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Step Progress Indicator -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div class="w-full flex items-center">
                        <div class="relative flex items-center justify-center">
                            <div class="rounded-full h-12 w-12 bg-blue-600 text-white flex items-center justify-center font-bold text-xl z-10">
                                1
                            </div>
                            <div class="absolute top-0 text-center mt-14 w-32 -ml-10 text-sm font-medium text-blue-600">Download Template</div>
                        </div>
                        <div class="flex-1 h-1 bg-blue-600"></div>
                        <div class="relative flex items-center justify-center">
                            <div class="rounded-full h-12 w-12 bg-blue-600 text-white flex items-center justify-center font-bold text-xl z-10">
                                2
                            </div>
                            <div class="absolute top-0 text-center mt-14 w-32 -ml-10 text-sm font-medium text-blue-600">Fill Template</div>
                        </div>
                        <div class="flex-1 h-1 bg-blue-600"></div>
                        <div class="relative flex items-center justify-center">
                            <div class="rounded-full h-12 w-12 bg-blue-600 text-white flex items-center justify-center font-bold text-xl z-10">
                                3
                            </div>
                            <div class="absolute top-0 text-center mt-14 w-32 -ml-10 text-sm font-medium text-blue-600">Upload & Process</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-2">Bulk Upload Results</h2>
                        <p class="text-gray-600">Follow the steps below to upload multiple student results at once.</p>
                    </div>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-md shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">Please fix the following errors:</p>
                    <ul class="mt-2 text-sm list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-md shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif
    
    <!-- Step 1: Download Template -->
    <div class="mb-8 bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-blue-50 px-6 py-4 border-b border-blue-100">
            <div class="flex items-center">
                <div class="rounded-full h-8 w-8 bg-blue-600 text-white flex items-center justify-center font-bold mr-3">
                    1
                </div>
                <h3 class="text-lg font-medium text-blue-800">Download Excel Template</h3>
            </div>
        </div>
        <div class="p-6">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0 mt-1">
                    <i class="fas fa-file-excel text-green-600 text-3xl"></i>
                </div>
                <div class="flex-grow">
                    <p class="mb-4">Download our Excel template with the correct format for uploading student results. The template includes:</p>
                    <ul class="mb-4 list-disc list-inside text-gray-700 space-y-1">
                        <li>Required columns: <span class="font-semibold">index_number</span>, <span class="font-semibold">grade</span> or <span class="font-semibold">score</span></li>
                        <li>You can provide either a letter grade (A, B+, etc.) or a numeric score (0-100)</li>
                        <li>Sample data to guide you</li>
                        <li>Instructions for filling the template</li>
                    </ul>
                    <a href="{{ route('results.download-template') }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-800 focus:outline-none focus:border-green-800 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <i class="fas fa-download mr-2"></i> Download Excel Template
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Fill Template Instructions -->
    <div class="mb-8 bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-blue-50 px-6 py-4 border-b border-blue-100">
            <div class="flex items-center">
                <div class="rounded-full h-8 w-8 bg-blue-600 text-white flex items-center justify-center font-bold mr-3">
                    2
                </div>
                <h3 class="text-lg font-medium text-blue-800">Fill the Excel Template</h3>
            </div>
        </div>
        <div class="p-6">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0 mt-1">
                    <i class="fas fa-edit text-yellow-600 text-3xl"></i>
                </div>
                <div class="flex-grow">
                    <p class="mb-4">After downloading the template, fill it with your student results data:</p>
                    <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-yellow-50 p-4 rounded-md border border-yellow-200">
                            <h4 class="font-bold text-yellow-800 mb-2">Do's</h4>
                            <ul class="list-disc list-inside text-gray-700 space-y-1">
                                <li>Use correct student index numbers</li>
                                <li>Enter either letter grades (A, B+, C) or numeric scores (0-100)</li>
                                <li>Keep the header row intact</li>
                                <li>Save as CSV format</li>
                            </ul>
                        </div>
                        <div class="bg-red-50 p-4 rounded-md border border-red-200">
                            <h4 class="font-bold text-red-800 mb-2">Don'ts</h4>
                            <ul class="list-disc list-inside text-gray-700 space-y-1">
                                <li>Don't modify column headers</li>
                                <li>Don't add extra columns</li>
                                <li>Don't leave index_number or grade empty</li>
                                <li>Don't use invalid grade formats</li>
                            </ul>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-md border border-gray-200 mb-4">
                        <h4 class="font-medium text-gray-800 mb-2">Sample Data Format</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white border border-gray-300">
                                <thead>
                                    <tr>
                                        <th class="py-2 px-4 border-b border-gray-300 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase">index_number</th>
                                        <th class="py-2 px-4 border-b border-gray-300 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase">grade</th>
                                        <th class="py-2 px-4 border-b border-gray-300 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase">score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="py-2 px-4 border-b border-gray-300">STU12345</td>
                                        <td class="py-2 px-4 border-b border-gray-300">A</td>
                                        <td class="py-2 px-4 border-b border-gray-300">90</td>
                                    </tr>
                                    <tr>
                                        <td class="py-2 px-4 border-b border-gray-300">STU67890</td>
                                        <td class="py-2 px-4 border-b border-gray-300">B+</td>
                                        <td class="py-2 px-4 border-b border-gray-300">78</td>
                                    </tr>
                                    <tr>
                                        <td class="py-2 px-4 border-b border-gray-300">STU24680</td>
                                        <td class="py-2 px-4 border-b border-gray-300"></td>
                                        <td class="py-2 px-4 border-b border-gray-300">75</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3: Upload and Process -->
    <div class="mb-8 bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-blue-50 px-6 py-4 border-b border-blue-100">
            <div class="flex items-center">
                <div class="rounded-full h-8 w-8 bg-blue-600 text-white flex items-center justify-center font-bold mr-3">
                    3
                </div>
                <h3 class="text-lg font-medium text-blue-800">Upload & Process Results</h3>
            </div>
        </div>
        <div class="p-6">
            <form action="{{ route('results.bulk-store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-1">Academic Year*</label>
                        <select name="academic_year_id" id="academic_year_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                            <option value="">Select Academic Year</option>
                            @foreach ($academicYears as $academicYear)
                                <option value="{{ $academicYear->id }}" {{ old('academic_year_id') == $academicYear->id ? 'selected' : '' }}>
                                    {{ $academicYear->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="semester_id" class="block text-sm font-medium text-gray-700 mb-1">Semester*</label>
                        <select name="semester_id" id="semester_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                            <option value="">Select Semester</option>
                            @foreach ($semesters as $semester)
                                <option value="{{ $semester->id }}" {{ old('semester_id') == $semester->id ? 'selected' : '' }}>
                                    {{ $semester->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="course_id" class="block text-sm font-medium text-gray-700 mb-1">Course*</label>
                        <select name="course_id" id="course_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                            <option value="">Select Course</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
                                    {{ $course->code }} - {{ $course->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-6">
                    <label for="file" class="block text-sm font-medium text-gray-700 mb-2">Upload Completed Excel File*</label>
                    <div class="mt-1 flex justify-center px-6 py-6 border-2 border-blue-300 border-dashed rounded-md bg-blue-50 hover:bg-blue-100 transition-colors duration-200">
                        <div class="space-y-2 text-center">
                            <i class="fas fa-file-upload text-blue-500 text-4xl mb-2"></i>
                            <div class="flex flex-col items-center text-sm text-gray-600">
                                <label for="file" class="relative cursor-pointer rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                    <span class="text-base">Click to upload your CSV file</span>
                                    <input id="file" name="file" type="file" class="sr-only" accept=".csv,.xlsx,.xls" required>
                                </label>
                                <p class="text-gray-500 mt-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">
                                CSV or Excel file up to 10MB
                            </p>
                            <p id="selected-file" class="text-sm text-blue-700 font-medium mt-2 hidden"></p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-6">
                    <a href="{{ route('results.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 flex items-center">
                        <i class="fas fa-upload mr-2"></i> Upload and Process Results
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Summary and Help Section -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-800">Need Help?</h3>
        </div>
        <div class="p-6">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0 mt-1">
                    <i class="fas fa-question-circle text-gray-500 text-3xl"></i>
                </div>
                <div class="flex-grow">
                    <p class="mb-4">If you encounter any issues during the bulk upload process:</p>
                    <ul class="list-disc list-inside text-gray-700 space-y-2">
                        <li>Ensure all required columns are present in your CSV file</li>
                        <li>Verify that all student index numbers exist in the system</li>
                        <li>Check that grade formats follow the system's grading scheme</li>
                        <li>For large files, the upload may take a few moments to process</li>
                    </ul>
                    <div class="mt-4 p-4 bg-blue-50 rounded-md">
                        <p class="text-blue-800">For detailed instructions or assistance, please contact the system administrator.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    // File upload feedback
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('file');
        const selectedFileElement = document.getElementById('selected-file');
        
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const fileName = e.target.files[0].name;
                const fileSize = e.target.files[0].size;
                const fileSizeInMB = (fileSize / (1024 * 1024)).toFixed(2);
                
                selectedFileElement.textContent = `Selected file: ${fileName} (${fileSizeInMB} MB)`;
                selectedFileElement.classList.remove('hidden');
                
                // Change the upload area styling to indicate selection
                const uploadArea = this.closest('div.border-dashed');
                if (uploadArea) {
                    uploadArea.classList.add('border-blue-500');
                    uploadArea.classList.add('bg-blue-100');
                }
            }
        });
        
        // Form validation enhancement
        document.querySelector('form').addEventListener('submit', function(e) {
            const academicYear = document.getElementById('academic_year_id').value;
            const semester = document.getElementById('semester_id').value;
            const course = document.getElementById('course_id').value;
            const file = fileInput.files.length;
            
            let hasError = false;
            let errorMessage = '';
            
            if (!academicYear) {
                hasError = true;
                errorMessage += 'Please select an academic year. ';
            }
            
            if (!semester) {
                hasError = true;
                errorMessage += 'Please select a semester. ';
            }
            
            if (!course) {
                hasError = true;
                errorMessage += 'Please select a course. ';
            }
            
            if (!file) {
                hasError = true;
                errorMessage += 'Please select a file to upload. ';
            }
            
            if (hasError) {
                e.preventDefault();
                alert('Form validation failed: ' + errorMessage);
            } else {
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
            }
        });
    });
</script>

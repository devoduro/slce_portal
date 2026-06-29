<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Upload Excel File') }}
            </h2>
            <x-button href="{{ route('uploads.index') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to Uploads') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form action="{{ route('uploads.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        
                        <!-- File Format Instructions -->
                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-info-circle text-blue-500"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        The Excel file should contain the following columns: <strong>Index Number</strong>, <strong>Course Code</strong>, and <strong>Grade</strong>.
                                        <a href="{{ route('uploads.template') }}" class="font-medium underline">Download a template</a> to ensure your file is in the correct format.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Academic Year -->
                            <div>
                                <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                                <select id="academic_year_id" name="academic_year_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                                    <option value="">Select Academic Year</option>
                                    @foreach($academicYears ?? [] as $year)
                                        <option value="{{ $year->id }}" {{ old('academic_year_id') == $year->id ? 'selected' : '' }}>
                                            {{ $year->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('academic_year_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- Semester -->
                            <div>
                                <label for="semester_id" class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
                                <select id="semester_id" name="semester_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                                    <option value="">Select Semester</option>
                                    @foreach($semesters ?? [] as $semester)
                                        <option value="{{ $semester->id }}" {{ old('semester_id') == $semester->id ? 'selected' : '' }}>
                                            {{ $semester->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('semester_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- Excel File Upload -->
                        <div class="mt-6">
                            <label for="excel_file" class="block text-sm font-medium text-gray-700 mb-1">Excel File</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                <div class="space-y-1 text-center">
                                    <i class="fas fa-file-excel text-gray-400 text-3xl mb-3"></i>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="excel_file" class="relative cursor-pointer bg-white rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500">
                                            <span>Upload a file</span>
                                            <input id="excel_file" name="excel_file" type="file" accept=".xlsx,.xls,.csv" class="sr-only">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        XLSX, XLS, or CSV up to 10MB
                                    </p>
                                </div>
                            </div>
                            <div id="file-selected" class="mt-2 text-sm text-gray-500 hidden">
                                Selected file: <span id="file-name" class="font-medium"></span>
                            </div>
                            @error('excel_file')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <!-- Options Section -->
                        <div class="mt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Upload Options</h3>
                            
                            <div class="space-y-4">
                                <!-- Update Existing Records -->
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="update_existing" name="update_existing" type="checkbox" class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded" checked>
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="update_existing" class="font-medium text-gray-700">Update existing records</label>
                                        <p class="text-gray-500">If a record already exists for a student and course, update it with the new grade.</p>
                                    </div>
                                </div>
                                
                                <!-- Skip Header Row -->
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="skip_header" name="skip_header" type="checkbox" class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded" checked>
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="skip_header" class="font-medium text-gray-700">Skip header row</label>
                                        <p class="text-gray-500">Skip the first row of the Excel file (usually contains column headers).</p>
                                    </div>
                                </div>
                                
                                <!-- Calculate GPA -->
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="calculate_gpa" name="calculate_gpa" type="checkbox" class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded" checked>
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="calculate_gpa" class="font-medium text-gray-700">Calculate GPA</label>
                                        <p class="text-gray-500">Automatically calculate and update student GPA after upload.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end mt-6">
                            <x-button type="submit" icon="fas fa-upload">
                                {{ __('Upload and Process') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        document.getElementById('excel_file').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                document.getElementById('file-name').textContent = fileName;
                document.getElementById('file-selected').classList.remove('hidden');
            } else {
                document.getElementById('file-selected').classList.add('hidden');
            }
        });
    </script>
    @endpush
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Transcripts') }}
            </h2>
          
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Search and Filter -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form action="{{ route('transcripts.index') }}" method="GET" class="space-y-4">
                        <div class="flex flex-col md:flex-row gap-4">
                            <div class="flex-1">
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search by index number, name or email..." class="pl-10 shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                </div>
                            </div>
                            
                            <div class="w-full md:w-48">
                                <label for="programme" class="block text-sm font-medium text-gray-700 mb-1">Programme</label>
                                <select id="programme" name="programme" class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    <option value="">All Programmes</option>
                                    @foreach($programmes ?? [] as $programme)
                                        <option value="{{ $programme->id }}" {{ request('programme') == $programme->id ? 'selected' : '' }}>
                                            {{ $programme->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="w-full md:w-48">
                                <label for="academic_year" class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                                <select id="academic_year" name="academic_year" class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    <option value="">All Years</option>
                                    @foreach($academicYears ?? [] as $year)
                                        <option value="{{ $year->id }}" {{ request('academic_year') == $year->id ? 'selected' : '' }}>
                                            {{ $year->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="w-full md:w-48">
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select id="status" name="status" class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    <option value="">All Status</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="graduated" {{ request('status') == 'graduated' ? 'selected' : '' }}>Graduated</option>
                                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                    <option value="withdrawn" {{ request('status') == 'withdrawn' ? 'selected' : '' }}>Withdrawn</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-500">
                                Found <span class="font-medium">{{ $students->total() }}</span> students matching your criteria
                            </div>
                            <div class="flex gap-2">
                                @if(request()->anyFilled(['search', 'programme', 'academic_year', 'status']))
                                <a href="{{ route('transcripts.index') }}" class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    <i class="fas fa-times mr-2"></i> Clear Filters
                                </a>
                                @endif
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    <i class="fas fa-filter mr-2"></i> Apply Filters
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Students List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Student Transcripts</h3>
                    
                    @if(count($students ?? []) > 0)
                    <form id="bulk-action-form" action="{{ route('transcripts.bulk-generate') }}" method="POST">
                        @csrf
                        <div class="mb-4 flex justify-between items-center">
                            <div class="flex items-center space-x-2">
                                <button type="button" id="select-all" class="text-sm text-primary-600 hover:text-primary-900 font-medium">Select All</button>
                                <button type="button" id="deselect-all" class="text-sm text-primary-600 hover:text-primary-900 font-medium">Deselect All</button>
                            </div>
                            <div class="flex items-center space-x-2">
                                <select name="academic_year_id" class="shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm border-gray-300 rounded-md">
                                    <option value="">All Academic Years</option>
                                    @foreach($academicYears ?? [] as $year)
                                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50" id="bulk-generate-btn" disabled>
                                    <i class="fas fa-file-pdf mr-2"></i> Generate Selected
                                </button>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <span class="sr-only">Select</span>
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Index Number
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Student Name
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Programme
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            CGPA
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($students as $student)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="student-checkbox focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $student->index_number }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $student->full_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $student->programme->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ number_format($student->calculateCGPA(), 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end space-x-2">
                                                <a href="{{ route('transcripts.generate', $student) }}" class="text-primary-600 hover:text-primary-900" title="Generate Transcript">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                                <a href="{{ route('transcripts.preview', $student) }}" class="text-primary-600 hover:text-primary-900" title="Preview Transcript">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('students.show', $student->id) }}" class="text-primary-600 hover:text-primary-900" title="View Student">
                                                    <i class="fas fa-user"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $students->links() }}
                    </div>
                    @else
                    <div class="bg-gray-50 p-4 rounded-lg text-center">
                        <p class="text-sm text-gray-500">No students found.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            const selectAllBtn = document.getElementById('select-all');
            const deselectAllBtn = document.getElementById('deselect-all');
            const bulkGenerateBtn = document.getElementById('bulk-generate-btn');
            
            // Function to update the bulk generate button state
            function updateBulkButtonState() {
                const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
                bulkGenerateBtn.disabled = checkedCount === 0;
                
                // Update the button text to show count
                if (checkedCount > 0) {
                    bulkGenerateBtn.innerHTML = `<i class="fas fa-file-pdf mr-2"></i> Generate ${checkedCount} Selected`;
                } else {
                    bulkGenerateBtn.innerHTML = `<i class="fas fa-file-pdf mr-2"></i> Generate Selected`;
                }
            }
            
            // Add event listeners to all checkboxes
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateBulkButtonState);
            });
            
            // Select all functionality
            selectAllBtn.addEventListener('click', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = true;
                });
                updateBulkButtonState();
            });
            
            // Deselect all functionality
            deselectAllBtn.addEventListener('click', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                updateBulkButtonState();
            });
            
            // Initial button state
            updateBulkButtonState();
        });
    </script>
</x-app-layout>

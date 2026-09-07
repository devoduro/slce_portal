<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Student Details') }}
            </h2>
            <div class="flex space-x-2">
                <x-button href="{{ route('students.edit', $student) }}" icon="fas fa-edit">
                    {{ __('Edit') }}
                </x-button>
                @if(!$student->user)
                <x-button href="{{ route('students.create-account', $student->id) }}" variant="success" icon="fas fa-user-plus">
                    {{ __('Create Login Account') }}
                </x-button>
                @else
                <x-button disabled variant="success" icon="fas fa-user-check">
                    {{ __('Has Login Account') }}
                </x-button>
                @endif
                <x-button href="{{ route('students.index') }}" variant="secondary" icon="fas fa-arrow-left">
                    {{ __('Back to Students') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Student Profile -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row">
                        <!-- Student Photo and Basic Info -->
                        <div class="md:w-1/3 flex flex-col items-center md:border-r md:pr-6">
                            <div class="w-32 h-32 rounded-full overflow-hidden mb-4">
                                @if($student->profile_photo)
                                    <img src="{{ asset('storage/' . $student->profile_photo) }}" alt="{{ $student->full_name }}" class="w-full h-full object-cover">
                                @else
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($student->full_name) }}&color=7F9CF5&background=EBF4FF&size=128" alt="{{ $student->full_name }}" class="w-full h-full object-cover">
                                @endif
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-1">{{ $student->full_name }}</h3>
                            <p class="text-sm text-gray-500 mb-2">{{ $student->index_number }}</p>
                            
                            <div class="flex items-center mb-4">
                                @if($student->status === 'active')
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Active
                                    </span>
                                @elseif($student->status === 'graduated')
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        Graduated
                                    </span>
                                @elseif($student->status === 'inactive')
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        Inactive
                                    </span>
                                @elseif($student->status === 'suspended')
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Suspended
                                    </span>
                                @endif
                            </div>
                            
                            <div class="w-full mt-4 space-y-3">
                                <div class="flex items-center">
                                    <i class="fas fa-envelope text-gray-400 w-5"></i>
                                    <span class="ml-2 text-sm text-gray-600">{{ $student->email }}</span>
                                </div>
                                @if($student->phone)
                                <div class="flex items-center">
                                    <i class="fas fa-phone text-gray-400 w-5"></i>
                                    <span class="ml-2 text-sm text-gray-600">{{ $student->phone }}</span>
                                </div>
                                @endif
                                @if($student->date_of_birth)
                                <div class="flex items-center">
                                    <i class="fas fa-birthday-cake text-gray-400 w-5"></i>
                                    <span class="ml-2 text-sm text-gray-600">{{ date('M d, Y', strtotime($student->date_of_birth)) }}</span>
                                </div>
                                @endif
                                @if($student->address)
                                <div class="flex items-start">
                                    <i class="fas fa-map-marker-alt text-gray-400 w-5 mt-0.5"></i>
                                    <span class="ml-2 text-sm text-gray-600">{{ $student->address }}</span>
                                </div>
                                @endif
                            </div>
                            
                            <div class="mt-6 w-full">
                                <x-button href="{{ route('transcripts.generate', $student) }}" class="w-full justify-center" icon="fas fa-file-alt">
                                    {{ __('Generate Transcript') }}
                                </x-button>
                            </div>
                        </div>
                        
                        <!-- Student Academic Info -->
                        <div class="md:w-2/3 md:pl-6 mt-6 md:mt-0">
                            <div class="mb-6">
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Academic Information</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <p class="text-sm text-gray-500">Programme</p>
                                        <p class="font-medium">{{ $student->programme->name ?? 'Not Assigned' }}</p>
                                    </div>
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <p class="text-sm text-gray-500">Gender</p>
                                        <p class="font-medium">{{ $student->gender ?? 'Not Specified' }}</p>
                                    </div>
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <p class="text-sm text-gray-500">Level</p>
                                        <p class="font-medium">{{ $student->levelLabel() }}</p>
                                    </div>
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <p class="text-sm text-gray-500">Class</p>
                                        <p class="font-medium">{{ $student->classGroup->name ?? 'Not Assigned' }}</p>
                                    </div>
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <p class="text-sm text-gray-500">CGPA</p>
                                        <p class="font-medium">{{ number_format($student->calculateCGPA() ?? 0, 2) }}</p>
                                    </div>
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <p class="text-sm text-gray-500">Classification</p>
                                        <p class="font-medium">{{ $student->getClassification() ?? 'Not Available' }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            @if($student->notes)
                            <div class="mb-6">
                                <h4 class="text-lg font-medium text-gray-900 mb-2">Notes</h4>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-600">{{ $student->notes }}</p>
                                </div>
                            </div>
                            @endif
                            
                            <!-- Recent Courses -->
                            <div>
                                <div class="flex justify-between items-center mb-4">
                                    <h4 class="text-lg font-medium text-gray-900">Recent Courses</h4>
                                    <a href="{{ route('students.results', $student) }}" class="text-sm text-primary-600 hover:text-primary-500">View All</a>
                                </div>
                                
                                @if(count($student->recentCourses ?? []) > 0)
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Course
                                                </th>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Code
                                                </th>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Semester
                                                </th>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Grade
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($student->recentCourses ?? [] as $course)
                                            <tr>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {{ $course->name }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $course->code }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $course->semester }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                        @if($course->grade >= 'A') bg-green-100 text-green-800
                                                        @elseif($course->grade >= 'B') bg-blue-100 text-blue-800
                                                        @elseif($course->grade >= 'C') bg-yellow-100 text-yellow-800
                                                        @elseif($course->grade >= 'D') bg-orange-100 text-orange-800
                                                        @else bg-red-100 text-red-800 @endif">
                                                        {{ $course->grade }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @else
                                <div class="bg-gray-50 p-4 rounded-lg text-center">
                                    <p class="text-sm text-gray-500">No courses found for this student.</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- STS / Internship Placement History -->
            @if($stsPlacements->isNotEmpty())
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-1">STS / Internship Placements</h3>
                    <p class="text-sm text-gray-500 mb-4">Partner schools this student has been placed at. A student is never placed at the same school twice.</p>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Term</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Partner School</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supervisor(s)</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selected On</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($stsPlacements as $placement)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-900">
                                            {{ $placement->stsTerm->name ?? '-' }}
                                            <div class="text-xs text-gray-500">{{ $placement->stsTerm->semester->academicYear->name ?? '' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500">{{ $placement->type === 'internship' ? 'Internship' : 'STS' }}</td>
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $placement->partnerSchool->name ?? 'Not selected' }}</td>
                                        <td class="px-4 py-3 text-gray-500">
                                            {{ $placement->lecturer->name ?? 'Not assigned' }}
                                            @if($placement->secondLecturer)
                                                <div class="text-xs text-gray-500">{{ $placement->secondLecturer->name }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                {{ $placement->statusLabel() === 'Ready' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ $placement->statusLabel() }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500">{{ $placement->selected_at?->format('M d, Y') ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3 justify-end">
                <x-button href="{{ route('results.create', ['student_id' => $student->id]) }}" variant="secondary" icon="fas fa-plus-circle">
                    {{ __('Add Result') }}
                </x-button>
                <x-button href="{{ route('students.edit', $student) }}" variant="secondary" icon="fas fa-edit">
                    {{ __('Edit Student') }}
                </x-button>
                @if(auth()->user()->role === 'admin')
                    @php
                        // Try to find a user with the same email as the student
                        $userByEmail = null;
                        if ($student->email) {
                            $userByEmail = \App\Models\User::where('email', $student->email)->first();
                        }
                    @endphp
                    
                    @if($userByEmail)
                        <x-button href="{{ route('users.reset-password', $userByEmail) }}" variant="secondary" icon="fas fa-key">
                            {{ __('Reset Password') }}
                        </x-button>
                    @else
                        <!-- Show a disabled button with tooltip if no user account exists -->
                        <span title="No user account found for this student">
                            <x-button disabled variant="secondary" icon="fas fa-key">
                                {{ __('No User Account') }}
                            </x-button>
                        </span>
                    @endif
                @endif
                <form method="POST" action="{{ route('students.destroy', $student) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this student?');">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" icon="fas fa-trash">
                        {{ __('Delete Student') }}
                    </x-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

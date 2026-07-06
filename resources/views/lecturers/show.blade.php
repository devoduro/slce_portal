<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $lecturer->name }}
            </h2>
            <x-button href="{{ route('lecturers.index') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to Lecturers') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Profile -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center gap-4 mb-6">
                    @if($lecturer->profile_photo)
                        <img src="{{ asset('storage/' . $lecturer->profile_photo) }}" alt="{{ $lecturer->name }}" class="w-20 h-20 rounded-full object-cover">
                    @else
                        <div class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center text-gray-400">
                            <i class="fas fa-user text-3xl"></i>
                        </div>
                    @endif
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $lecturer->name }}</h3>
                        <p class="text-sm text-gray-500">{{ $lecturer->department->name ?? 'No department' }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Email</p>
                        <p class="font-medium">{{ $lecturer->email ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Phone</p>
                        <p class="font-medium">{{ $lecturer->phone ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Staff ID</p>
                        <p class="font-medium">{{ $lecturer->staff_id ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Department</p>
                        <p class="font-medium">{{ $lecturer->department->name ?? 'Not set' }}</p>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Portal Account</p>
                        @if($lecturer->user)
                            <p class="font-medium text-green-600"><i class="fas fa-check-circle mr-1"></i> Active ({{ $lecturer->user->email }})</p>
                        @else
                            <p class="font-medium text-gray-500"><i class="fas fa-circle-notch mr-1"></i> No login account yet</p>
                        @endif
                    </div>
                    <div class="flex space-x-2">
                        @if(!$lecturer->user)
                            <form action="{{ route('lecturers.create-account', $lecturer) }}" method="POST" class="inline-block">
                                @csrf
                                <x-button type="submit" variant="success" icon="fas fa-user-plus">
                                    {{ __('Create Login Account') }}
                                </x-button>
                            </form>
                        @endif
                        <x-button href="{{ route('lecturers.edit', $lecturer) }}" variant="secondary" icon="fas fa-edit">
                            {{ __('Edit') }}
                        </x-button>
                    </div>
                </div>
            </div>

            <!-- Assigned Courses -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Assigned Courses</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Semester</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($lecturer->courses as $course)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $course->code }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $course->title }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $course->semester->name ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-8 text-center text-gray-400">No courses assigned yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Workload Summary -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-1">Workload Summary</h3>
                    <p class="text-sm text-gray-500 mb-4">Each class contributes its course's credit hours to the total (e.g. a 3-credit-hour course taught to 5 classes = 3 &times; 5 = 15 workload).</p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Semester</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Classes Taught</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Workload</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($workloadBySemester as $row)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row->semester_name }} - {{ $row->academic_year_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->classes }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-primary-700">{{ rtrim(rtrim(number_format($row->workload, 2), '0'), '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-8 text-center text-gray-400">No timetable slots assigned yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Timetable Slots -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Timetable Slots</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Venue</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Semester</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($lecturer->timetableEntries as $entry)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ \App\Http\Controllers\TimetableController::DAYS[$entry->day_of_week] ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ substr($entry->start_time, 0, 5) }} - {{ substr($entry->end_time, 0, 5) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $entry->classGroup->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if($entry->is_virtual)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"><i class="fas fa-laptop"></i> Online</span>
                                            @else
                                                {{ $entry->venue->name ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $entry->semester->name ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-gray-400">No timetable slots assigned yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                View Result
            </h2>
            <div class="flex space-x-2">
                <x-button href="{{ route('results.edit', $result) }}" icon="fas fa-edit">
                    Edit Result
                </x-button>
                <x-button href="{{ route('results.index') }}" variant="secondary" icon="fas fa-arrow-left">
                    Back to List
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Student Information -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Student Information</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Name</label>
                                    <div class="mt-1 text-sm text-gray-900">{{ $result->student->full_name }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Index Number</label>
                                    <div class="mt-1 text-sm text-gray-900">{{ $result->student->index_number }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Programme</label>
                                    <div class="mt-1 text-sm text-gray-900">{{ $result->student->programme->name }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Result Information -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Result Information</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Course</label>
                                    <div class="mt-1">
                                        <div class="text-sm text-gray-900">{{ $result->course->code }}</div>
                                        <div class="text-sm text-gray-500">{{ $result->course->title }}</div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Academic Year</label>
                                    <div class="mt-1 text-sm text-gray-900">{{ $result->academicYear->name }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Semester</label>
                                    <div class="mt-1 text-sm text-gray-900">{{ $result->semester->name }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Grade</label>
                                    <div class="mt-1">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            @if($result->grade == 'A' || $result->grade == 'A+') bg-green-100 text-green-800
                                            @elseif($result->grade == 'B+' || $result->grade == 'B') bg-blue-100 text-blue-800
                                            @elseif($result->grade == 'C+' || $result->grade == 'C') bg-yellow-100 text-yellow-800
                                            @elseif($result->grade == 'D+' || $result->grade == 'D') bg-orange-100 text-orange-800
                                            @else bg-red-100 text-red-800 @endif">
                                            {{ $result->grade }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Grade Point</label>
                                    <div class="mt-1 text-sm text-gray-900">{{ number_format($result->grade_point, 2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

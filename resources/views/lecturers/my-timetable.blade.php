<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Timetable') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Profile summary -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex items-center gap-4">
                @if($lecturer->profile_photo)
                    <img src="{{ asset('storage/' . $lecturer->profile_photo) }}" alt="{{ $lecturer->name }}" class="w-16 h-16 rounded-full object-cover">
                @else
                    <div class="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center text-gray-400">
                        <i class="fas fa-user text-2xl"></i>
                    </div>
                @endif
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ $lecturer->name }}</h3>
                    <p class="text-sm text-gray-500">
                        {{ $lecturer->department->name ?? 'No department' }}
                        @if($lecturer->phone)
                            &bull; <i class="fas fa-phone-alt text-xs"></i> {{ $lecturer->phone }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="GET" action="{{ route('lecturer.timetable') }}" class="flex flex-wrap gap-4 items-end mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Semester</label>
                            <select name="semester_id" class="mt-1 block w-64 pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500" onchange="this.form.submit()">
                                @foreach($semesters as $option)
                                    <option value="{{ $option->id }}" {{ $semester && $semester->id === $option->id ? 'selected' : '' }}>{{ $option->name }} - {{ $option->academicYear->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if($semester)
                            <a href="{{ route('lecturer.timetable.print', ['semester_id' => $semester->id]) }}" target="_blank" class="bg-gray-800 text-white rounded-md px-4 py-2 text-sm hover:bg-gray-900">
                                <i class="fas fa-print mr-1"></i> Print My Timetable
                            </a>
                        @endif
                    </form>

                    @if(!$semester)
                        <div class="text-center py-8 text-gray-400">
                            No semesters have been set up yet.
                        </div>
                    @else
                        <div class="mb-4 flex flex-wrap gap-3">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-primary-50 text-primary-700">
                                <i class="fas fa-chalkboard-teacher"></i> {{ $workload['classes'] }} class{{ $workload['classes'] === 1 ? '' : 'es' }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-amber-50 text-amber-700">
                                <i class="fas fa-weight-hanging"></i> Workload: {{ rtrim(rtrim(number_format($workload['workload'], 2), '0'), '.') }}
                            </span>
                        </div>
                        @include('timetable._grid', ['entries' => $entries, 'slotLabels' => $slotLabels, 'showActions' => false])
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

@extends('components.student-app-layout')

@section('header')
    <div class="bg-white rounded-2xl p-6 mb-6 shadow-sm border border-gray-100">
        <h2 class="text-2xl font-bold text-gray-800">My Timetable</h2>
        <p class="text-gray-500 mt-1">
            @if($student->classGroup)
                {{ $student->classGroup->name }} &bull; {{ $student->classGroup->programme->name ?? '' }}
            @else
                Weekly class schedule
            @endif
        </p>
    </div>
@endsection

@section('content')
<div class="max-w-[1600px] mx-auto space-y-6">
    @if(!$student->class_group_id)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center text-gray-500">
            <i class="fas fa-users-slash text-gray-300 text-4xl mb-3"></i>
            <p>You have not been assigned to a class yet. Please contact the registrar's office.</p>
        </div>
    @else
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <form method="GET" action="{{ route('student.timetable') }}" class="flex flex-wrap gap-4 items-end mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Semester</label>
                    <select name="semester_id" class="mt-1 block w-64 pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500" onchange="this.form.submit()">
                        @foreach($semesters as $option)
                            <option value="{{ $option->id }}" {{ $semester && $semester->id === $option->id ? 'selected' : '' }}>{{ $option->name }} - {{ $option->academicYear->name ?? '' }}</option>
                        @endforeach
                    </select>
                </div>
                @if($semester)
                    <a href="{{ route('student.timetable.print', ['semester_id' => $semester->id]) }}" target="_blank" class="bg-gray-800 text-white rounded-md px-4 py-2 text-sm hover:bg-gray-900">
                        <i class="fas fa-print mr-1"></i> Print My Timetable
                    </a>
                @endif
            </form>

            @if(!$semester)
                <div class="text-center py-8 text-gray-400">
                    No semesters have been set up yet.
                </div>
            @else
                @if($classSummary)
                    <div class="mb-4 flex flex-wrap gap-3">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-emerald-50 text-emerald-700">
                            <i class="fas fa-book"></i> Total Courses: {{ $classSummary['courses'] }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-indigo-50 text-indigo-700">
                            <i class="fas fa-award"></i> Total Credit: {{ rtrim(rtrim(number_format($classSummary['credit'], 2), '0'), '.') }}
                        </span>
                    </div>
                @endif
                @include('timetable._grid', ['entries' => $entries, 'slotLabels' => $slotLabels, 'showActions' => false])
            @endif
        </div>
    @endif
</div>
@endsection

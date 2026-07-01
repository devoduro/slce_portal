@extends('components.student-app-layout')

@section('header')
    <div class="bg-white rounded-2xl p-6 mb-6 shadow-sm border border-gray-100">
        <h2 class="text-2xl font-bold text-gray-800">Continuous Assessment</h2>
        <p class="text-gray-500 mt-1">Your Attendance, Project, Assignment and Mid-Semester scores per course.</p>
    </div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Semester</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attendance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mid-Semester</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($rows as $row)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $row['course']->code }}</div>
                                <div class="text-sm text-gray-500">{{ $row['course']->title }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row['semester']->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($row['attendance_score'], 2) }}
                                @if($row['setting'])
                                    <span class="text-xs text-gray-400">/ {{ $row['setting']->attendance_max }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $row['ca']->project_score ?? '-' }}
                                @if($row['setting'])
                                    <span class="text-xs text-gray-400">/ {{ $row['setting']->project_max }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $row['ca']->assignment_score ?? '-' }}
                                @if($row['setting'])
                                    <span class="text-xs text-gray-400">/ {{ $row['setting']->assignment_max }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $row['ca']->mid_semester_score ?? '-' }}
                                @if($row['setting'])
                                    <span class="text-xs text-gray-400">/ {{ $row['setting']->mid_semester_max }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ number_format($row['total'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-400">No registered courses with Continuous Assessment data yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

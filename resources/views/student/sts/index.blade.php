@extends('components.student-app-layout')

@section('header')
    <div class="bg-white rounded-2xl p-6 mb-6 shadow-sm border border-gray-100">
        <h2 class="text-2xl font-bold text-gray-800">STS / Internship</h2>
        <p class="text-gray-500 mt-1">
            @if($term)
                {{ $term->name }} &bull; {{ $term->semester->academicYear->name ?? '' }}
            @else
                No active STS term
            @endif
        </p>
    </div>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    @if(session('success'))
        <div class="p-4 bg-green-50 border-l-4 border-green-400 text-green-700 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded">{{ session('error') }}</div>
    @endif

    @if(!$term)
        <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 rounded">
            There is no active STS/Internship term yet. Please check back once the school opens one.
        </div>
    @elseif(!$placement)
        <div class="p-4 bg-gray-100 border-l-4 border-gray-400 text-gray-700 rounded">
            You have not been included in this STS term yet. Please contact the STS Unit if you believe this is an error.
        </div>
    @elseif(!$isBiometricVerified)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Biometric Check-In</h3>
            <div class="flex items-center gap-4">
                <span class="px-3 py-1 inline-flex items-center gap-2 text-sm font-semibold rounded-full bg-red-100 text-red-800">
                    <i class="fas fa-fingerprint"></i> Not Done
                </span>
                <p class="text-sm text-gray-500">Please visit the biometric station on campus to confirm your presence for this semester.</p>
            </div>
        </div>
        <div class="p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded">
            You must complete biometric check-in before STS/Internship is available to you.
        </div>
    @else
        <!-- Eligibility -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Fee Payment Status</h3>
            <div class="flex items-center gap-4">
                @if($eligible)
                    <span class="px-3 py-1 inline-flex items-center gap-2 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                        <i class="fas fa-check-circle"></i> Threshold Met
                    </span>
                @else
                    <span class="px-3 py-1 inline-flex items-center gap-2 text-sm font-semibold rounded-full bg-red-100 text-red-800">
                        <i class="fas fa-times-circle"></i> Not Met
                    </span>
                @endif
                <p class="text-sm text-gray-500">You have paid {{ number_format($percentage, 1) }}% of your fees.</p>
            </div>
        </div>

        <!-- Placement Summary -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Your Placement</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500">Type</p>
                    <p class="text-lg font-semibold text-gray-900">{{ ucfirst($placement->type) }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500">Partner School</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $placement->partnerSchool->name ?? 'Not selected yet' }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500">Supervisor{{ $placement->second_lecturer_id ? 's' : '' }}</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $placement->lecturer->name ?? 'Not assigned yet' }}</p>
                    @if($placement->secondLecturer)
                        <p class="text-sm text-gray-600">{{ $placement->secondLecturer->name }}</p>
                    @endif
                </div>
            </div>

            @if(!$placement->partner_school_id)
                @if(!$canSelectSchool)
                    <p class="text-sm text-gray-500">
                        STS placement is only available to Level 100&ndash;{{ $term->internship_level_cutoff }} students during the First Semester.
                    </p>
                @elseif($eligible)
                    <a href="{{ route('student.sts.schools') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        <i class="fas fa-school"></i> Select a Partner School
                    </a>
                @else
                    <p class="text-sm text-red-600">You must meet the fee payment threshold before you can select a school.</p>
                @endif
            @elseif(!$placement->lecturer_id)
                <p class="text-sm text-gray-500">Your school has been selected. Waiting for a supervisor to be assigned by the STS Unit.</p>
            @else
                <a href="{{ route('student.sts.letter') }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                    <i class="fas fa-print"></i> Print Placement Letter
                </a>
            @endif
        </div>

        <!-- Assessment: the supervisor's marks, under the labels the STS Unit set up -->
        @if($scoreCriteria->isNotEmpty())
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-wrap justify-between items-start gap-3 mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Your Assessment</h3>
                        <p class="text-sm text-gray-500">
                            How your supervisor is marking this placement, out of
                            {{ number_format($scoreSummary['total'], 2) }}.
                        </p>
                    </div>

                    @if($scoreSummary['scored'] > 0)
                        <div class="text-right">
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($scoreSummary['awarded'], 2) }}</p>
                            <p class="text-xs text-gray-500">of {{ number_format($scoreSummary['total'], 2) }} so far</p>
                        </div>
                    @endif
                </div>

                @if($scoreSummary['scored'] === 0)
                    <div class="bg-gray-50 border-l-4 border-gray-300 p-4 text-sm text-gray-600">
                        Your supervisor has not entered any marks yet. They will appear here as they are recorded.
                    </div>
                @else
                    @if($scoreSummary['scored'] < $scoreSummary['criteria'])
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-3 mb-4 text-sm text-blue-800">
                            {{ $scoreSummary['scored'] }} of {{ $scoreSummary['criteria'] }} criteria have been marked so far —
                            your total will change as your supervisor completes the rest.
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Criterion</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-28">Mark</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Out Of</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($scoreCriteria as $criterion)
                                    @php $mark = optional($scoresByCriterion->get($criterion->id))->score; @endphp
                                    <tr>
                                        <td class="px-4 py-3 text-gray-900">{{ $criterion->label }}</td>
                                        <td class="px-4 py-3 text-right {{ $mark === null ? 'text-gray-400' : 'font-medium text-gray-900' }}">
                                            {{ $mark === null ? 'Not yet marked' : number_format((float) $mark, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-500">{{ number_format((float) $criterion->max_mark, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-700">Total</td>
                                    <td class="px-4 py-3 text-right font-bold text-gray-900">{{ number_format($scoreSummary['awarded'], 2) }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-700">{{ number_format($scoreSummary['total'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    @endif

    @if($placementHistory->isNotEmpty())
        <!-- Past Placements -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-1">Your Previous Placements</h3>
            <p class="text-sm text-gray-500 mb-4">You cannot be placed at the same school twice, so these schools are no longer offered to you.</p>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Term</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Partner School</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supervisor</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selected On</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($placementHistory as $past)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">
                                    {{ $past->stsTerm->name ?? '-' }}
                                    <div class="text-xs text-gray-500">{{ $past->stsTerm->semester->academicYear->name ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ $past->type === 'internship' ? 'Internship' : 'STS' }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $past->partnerSchool->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $past->lecturer->name ?? 'Not assigned' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $past->selected_at?->format('M d, Y') ?? '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    @php $pastScore = $past->scoreSummary(); @endphp
                                    @if($pastScore['scored'] > 0)
                                        <span class="font-medium text-gray-900">{{ number_format($pastScore['awarded'], 2) }}</span>
                                        <span class="text-gray-400">/ {{ number_format($pastScore['total'], 2) }}</span>
                                    @else
                                        <span class="text-gray-400">Not marked</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

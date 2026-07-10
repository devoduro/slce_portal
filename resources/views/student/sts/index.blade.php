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
                    <p class="text-sm text-gray-500">Supervisor</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $placement->lecturer->name ?? 'Not assigned yet' }}</p>
                </div>
            </div>

            @if(!$placement->partner_school_id)
                @if($eligible)
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
    @endif
</div>
@endsection

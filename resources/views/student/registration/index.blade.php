@extends('components.student-app-layout')

@section('header')
    <div class="bg-white rounded-2xl p-6 mb-6 shadow-sm border border-gray-100">
        <h2 class="text-2xl font-bold text-gray-800">Course Registration</h2>
        <p class="text-gray-500 mt-1">
            @if($currentSemester)
                {{ $currentSemester->name }} &bull; {{ $currentSemester->academicYear->name ?? '' }}
            @else
                No active semester
            @endif
        </p>
    </div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    

    @if(!$currentSemester)
        <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 rounded">
            There is no current semester set up yet. Please check back once the school opens a semester.
        </div>
    @else
        <!-- Biometric Status -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Biometric Check-In</h3>
            <div class="flex items-center gap-4">
                @if($isBiometricVerified)
                    <span class="px-3 py-1 inline-flex items-center gap-2 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                        <i class="fas fa-fingerprint"></i> Verified
                    </span>
                    <p class="text-sm text-gray-500">You have completed biometric check-in for this semester.</p>
                @else
                    <span class="px-3 py-1 inline-flex items-center gap-2 text-sm font-semibold rounded-full bg-red-100 text-red-800">
                        <i class="fas fa-fingerprint"></i> Not Done
                    </span>
                    <p class="text-sm text-gray-500">
                        @if($currentSemester->biometric_window_open)
                            Please visit the biometric station on campus to confirm your presence for this semester.
                        @else
                            The biometric check-in window has not been opened yet.
                        @endif
                    </p>
                @endif
            </div>
        </div>

       

        @if(!$currentSemester->isRegistrationOpen())
            <div class="p-4 bg-gray-100 border-l-4 border-gray-400 text-gray-700 rounded">
                Course registration is currently closed for this semester.
            </div>
        @elseif(!$isBiometricVerified)
            <div class="p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded">
                You must complete biometric check-in on campus before you can register courses this semester.
            </div>
        @elseif(!$meetsThreshold)
            <div class="p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded">
                You have not paid enough of your fees to register courses this semester. Please clear the balance shown above (or enough of it) and check back.
            </div>
        @else
            <div class="flex justify-end">
                <a href="{{ route('student.registration.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                    <i class="fas fa-{{ $registrations->isNotEmpty() ? 'pen' : 'plus' }}"></i> {{ $registrations->isNotEmpty() ? 'Edit Registration' : 'Register Courses' }}
                </a>
            </div>
        @endif

        <!-- Registered Courses -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Registered Courses</h3>
                @if($registrations->isNotEmpty())
                    <a href="{{ route('student.registration.print') }}" target="_blank" class="text-sm text-primary-600 hover:underline">
                        <i class="fas fa-print"></i> Print Registration Slip
                    </a>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Credit Hours</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($registrations as $registration)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $registration->course->code ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $registration->course->title ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $registration->course->credit_hours ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if($currentSemester->isRegistrationOpen())
                                        <form action="{{ route('student.registration.destroy', $registration) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Drop this course?')">
                                                <i class="fas fa-trash"></i> Drop
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400">No courses registered yet for this semester.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

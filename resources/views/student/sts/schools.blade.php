@extends('components.student-app-layout')

@section('header')
    <div class="bg-white rounded-2xl p-6 mb-6 shadow-sm border border-gray-100">
        <h2 class="text-2xl font-bold text-gray-800">Select a Partner School</h2>
        <p class="text-gray-500 mt-1">{{ $term->name }} &bull; Level {{ $placement->level }}</p>
    </div>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    @if(session('error'))
        <div class="p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded">{{ session('error') }}</div>
    @endif

    <div class="p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700 rounded text-sm">
        Schools are offered first-come-first-serve. Once a school's quota for your level is full, it will no longer be available.
    </div>

    @if($schools->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center text-gray-400">
            No partner schools are available for your category yet.
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($schools as $row)
                @php $school = $row['school']; $available = $row['available']; @endphp
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">{{ $school->name }}</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ $school->location ?? 'Location not set' }}</p>
                        <p class="text-sm mt-2">
                            @if($available > 0)
                                <span class="text-green-600 font-medium">{{ $available }} slot(s) available</span>
                            @else
                                <span class="text-red-600 font-medium">Quota full</span>
                            @endif
                        </p>
                    </div>
                    <form action="{{ route('student.sts.select', $school) }}" method="POST" class="mt-4">
                        @csrf
                        <button type="submit" @disabled($available <= 0) class="w-full inline-flex justify-center items-center gap-2 px-4 py-2 rounded-lg text-white {{ $available > 0 ? 'bg-primary-600 hover:bg-primary-700' : 'bg-gray-300 cursor-not-allowed' }}" onclick="return confirm('Select {{ $school->name }} as your partner school? This cannot be undone by you afterward.')">
                            <i class="fas fa-check"></i> Select This School
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

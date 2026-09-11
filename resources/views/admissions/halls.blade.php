@extends('components.app-layout')

@section('title', 'Admissions by Hall')
@section('subtitle', 'Hall assignments across all non-withdrawn admissions')

@section('content')
<div class="py-4 space-y-4">

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('admissions.halls') }}" class="bg-white rounded-lg shadow-sm p-4 hover:shadow-md {{ !request('hall') && request('status') !== 'unassigned' ? 'ring-2 ring-primary-500' : '' }}">
            <p class="text-xs uppercase text-gray-500">All</p>
            <p class="text-xl font-semibold">{{ $hallCounts->sum('total') + $unassignedCount }}</p>
        </a>
        <a href="{{ route('admissions.halls', ['status' => 'unassigned']) }}" class="bg-white rounded-lg shadow-sm p-4 hover:shadow-md {{ request('status') === 'unassigned' ? 'ring-2 ring-primary-500' : '' }}">
            <p class="text-xs uppercase text-gray-500">Unassigned</p>
            <p class="text-xl font-semibold text-yellow-600">{{ $unassignedCount }}</p>
        </a>
        @foreach($hallCounts as $hc)
            <a href="{{ route('admissions.halls', ['hall' => $hc->hall]) }}" class="bg-white rounded-lg shadow-sm p-4 hover:shadow-md {{ request('hall') === $hc->hall ? 'ring-2 ring-primary-500' : '' }}">
                <p class="text-xs uppercase text-gray-500">{{ $hc->hall }}</p>
                <p class="text-xl font-semibold">{{ $hc->total }}</p>
            </a>
        @endforeach
    </div>

    <div class="flex justify-between items-center">
        <form method="GET" class="flex gap-2">
            @if(request('hall'))<input type="hidden" name="hall" value="{{ request('hall') }}">@endif
            @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or applicant no."
                   class="rounded-md border-gray-300 text-sm w-72">
            <button type="submit" class="px-3 py-2 bg-gray-100 rounded-md text-sm hover:bg-gray-200">Search</button>
        </form>

        <a href="{{ route('admissions.halls.export', request()->query()) }}" class="px-3 py-2 bg-gray-100 rounded-md text-sm hover:bg-gray-200">
            <i class="fas fa-file-excel mr-1"></i> Export
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-left">Applicant No.</th>
                    <th class="px-4 py-3 text-left">Name</th>
                    <th class="px-4 py-3 text-left">Programme</th>
                    <th class="px-4 py-3 text-left">Hall</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($admissions as $admission)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $admission->applicant_number }}</td>
                        <td class="px-4 py-3">{{ $admission->full_name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $admission->programme->name ?? '' }}</td>
                        <td class="px-4 py-3">{{ $admission->hall ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admissions.show', $admission) }}" class="text-primary-600 hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No admissions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $admissions->links() }}
</div>
@endsection

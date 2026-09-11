@extends('components.app-layout')

@section('title', 'Admissions by Hall')
@section('subtitle', 'Hall assignments across all non-withdrawn admissions')

@section('content')
<div class="py-4 space-y-4">

    @php
        $hallLinkParams = fn ($overrides) => array_merge(request()->except(['hall', 'status', 'page']), $overrides);
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('admissions.halls', $hallLinkParams([])) }}" class="bg-white rounded-lg shadow-sm p-4 hover:shadow-md {{ !request('hall') && request('status') !== 'unassigned' ? 'ring-2 ring-primary-500' : '' }}">
            <p class="text-xs uppercase text-gray-500">All</p>
            <p class="text-xl font-semibold">{{ $hallCounts->sum('total') + $unassignedCount }}</p>
        </a>
        <a href="{{ route('admissions.halls', $hallLinkParams(['status' => 'unassigned'])) }}" class="bg-white rounded-lg shadow-sm p-4 hover:shadow-md {{ request('status') === 'unassigned' ? 'ring-2 ring-primary-500' : '' }}">
            <p class="text-xs uppercase text-gray-500">Unassigned</p>
            <p class="text-xl font-semibold text-yellow-600">{{ $unassignedCount }}</p>
        </a>
        @foreach($hallCounts as $hc)
            <a href="{{ route('admissions.halls', $hallLinkParams(['hall' => $hc->hall])) }}" class="bg-white rounded-lg shadow-sm p-4 hover:shadow-md {{ request('hall') === $hc->hall ? 'ring-2 ring-primary-500' : '' }}">
                <p class="text-xs uppercase text-gray-500">{{ $hc->hall }}</p>
                <p class="text-xl font-semibold">{{ $hc->total }}</p>
            </a>
        @endforeach
    </div>

    <div class="flex flex-wrap justify-between items-center gap-2">
        <form method="GET" class="flex flex-wrap gap-2 items-center">
            @if(request('hall'))<input type="hidden" name="hall" value="{{ request('hall') }}">@endif
            @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif

            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or applicant no."
                   class="rounded-md border-gray-300 text-sm w-64">

            <select name="admission_status" class="rounded-md border-gray-300 text-sm">
                <option value="">All statuses</option>
                @foreach(['offered', 'processing', 'reported', 'approved', 'migrated'] as $adStatus)
                    <option value="{{ $adStatus }}" @selected(request('admission_status') === $adStatus)>{{ ucfirst($adStatus) }}</option>
                @endforeach
            </select>

            <select name="payment_status" class="rounded-md border-gray-300 text-sm">
                <option value="">All payment statuses</option>
                @foreach(['not_billed' => 'Not Billed', 'billed' => 'Billed', 'partially_paid' => 'Partially Paid', 'paid_pending_verification' => 'Pending Verification', 'confirmed' => 'Confirmed', 'rejected' => 'Rejected', 'reversed' => 'Reversed'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('payment_status') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="programme_id" class="rounded-md border-gray-300 text-sm">
                <option value="">All programmes</option>
                @foreach($programmes as $programme)
                    <option value="{{ $programme->id }}" @selected((string) request('programme_id') === (string) $programme->id)>{{ $programme->name }}</option>
                @endforeach
            </select>

            <select name="academic_year_id" class="rounded-md border-gray-300 text-sm">
                <option value="">All academic years</option>
                @foreach($academicYears as $year)
                    <option value="{{ $year->id }}" @selected((string) request('academic_year_id') === (string) $year->id)>{{ $year->name }}</option>
                @endforeach
            </select>

            <select name="per_page" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm">
                @foreach(\App\Http\Controllers\AdmissionController::PER_PAGE_OPTIONS as $option)
                    <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} / page</option>
                @endforeach
            </select>

            <button type="submit" class="px-3 py-2 bg-gray-100 rounded-md text-sm hover:bg-gray-200">Filter</button>
            @if(request()->anyFilled(['search', 'admission_status', 'payment_status', 'programme_id', 'academic_year_id']))
                <a href="{{ route('admissions.halls', $hallLinkParams([])) }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
            @endif
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

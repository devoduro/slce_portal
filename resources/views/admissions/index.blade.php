@extends('components.app-layout')

@section('title', 'Admissions')
@section('subtitle', 'Applicants imported into the admission workflow')

@section('content')
<div class="py-4 space-y-4">
    <div class="flex flex-wrap justify-between items-center gap-2">
        <form method="GET" class="flex flex-wrap gap-2 items-center">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, applicant no., reference no."
                   class="rounded-md border-gray-300 text-sm w-64">
            <select name="status" class="rounded-md border-gray-300 text-sm">
                <option value="">All statuses</option>
                @foreach(['offered', 'processing', 'reported', 'approved', 'migrated', 'withdrawn'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
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
            <select name="hall" class="rounded-md border-gray-300 text-sm">
                <option value="">All halls</option>
                @foreach($halls as $hall)
                    <option value="{{ $hall }}" @selected(request('hall') === $hall)>{{ $hall }}</option>
                @endforeach
            </select>
            <select name="per_page" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm">
                @foreach(\App\Http\Controllers\AdmissionController::PER_PAGE_OPTIONS as $option)
                    <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} / page</option>
                @endforeach
            </select>
            <button type="submit" class="px-3 py-2 bg-gray-100 rounded-md text-sm hover:bg-gray-200">Filter</button>
            @if(request()->anyFilled(['search', 'status', 'payment_status', 'programme_id', 'academic_year_id', 'hall']))
                <a href="{{ route('admissions.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
            @endif
        </form>

        <div class="flex gap-2">
            <a href="{{ route('admissions.halls') }}" class="px-3 py-2 bg-gray-100 rounded-md text-sm hover:bg-gray-200">
                <i class="fas fa-building mr-1"></i> Halls
            </a>
            <a href="{{ route('admissions.export.excel', request()->query()) }}" class="px-3 py-2 bg-gray-100 rounded-md text-sm hover:bg-gray-200">
                <i class="fas fa-file-excel mr-1"></i> Excel
            </a>
            <a href="{{ route('admissions.export.pdf', request()->query()) }}" class="px-3 py-2 bg-gray-100 rounded-md text-sm hover:bg-gray-200">
                <i class="fas fa-file-pdf mr-1"></i> PDF
            </a>
            <a href="{{ route('admissions.import.form') }}" class="px-3 py-2 bg-gray-100 rounded-md text-sm hover:bg-gray-200">
                <i class="fas fa-upload mr-1"></i> Import
            </a>
            <a href="{{ route('admissions.create') }}" class="px-4 py-2 bg-primary-600 text-white rounded-md text-sm hover:bg-primary-700">
                <i class="fas fa-plus mr-1"></i> Import Applicant
            </a>
        </div>
    </div>

    <form id="bulk-form" action="{{ route('admissions.bulk-destroy') }}" method="POST" onsubmit="return confirm('Delete the selected admission(s)? This cannot be undone.');">
        @csrf

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="flex justify-between items-center px-4 py-2 border-b border-gray-100">
                <span class="text-xs text-gray-500">{{ $admissions->total() }} total &middot; showing {{ $admissions->count() }}</span>
                <button type="submit" class="px-3 py-1.5 bg-red-50 text-red-700 rounded-md text-xs hover:bg-red-100">
                    <i class="fas fa-trash mr-1"></i> Delete Selected
                </button>
            </div>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3"><input type="checkbox" onclick="document.querySelectorAll('.admission-checkbox').forEach(c => c.checked = this.checked)"></th>
                        <th class="px-4 py-3 text-left">Applicant No.</th>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Programme</th>
                        <th class="px-4 py-3 text-left">Hall</th>
                        <th class="px-4 py-3 text-left">Admission Status</th>
                        <th class="px-4 py-3 text-left">Payment Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($admissions as $admission)
                        <tr>
                            <td class="px-4 py-3">
                                <input type="checkbox" name="admission_ids[]" value="{{ $admission->id }}" class="admission-checkbox">
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $admission->applicant_number }}</td>
                            <td class="px-4 py-3">{{ $admission->full_name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $admission->programme->name ?? '' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $admission->hall ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-700">{{ ucfirst($admission->admission_status) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded {{ $admission->payment_status === 'confirmed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                    {{ ucfirst(str_replace('_', ' ', $admission->payment_status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admissions.show', $admission) }}" class="text-primary-600 hover:underline">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">No admissions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    {{ $admissions->links() }}
</div>
@endsection

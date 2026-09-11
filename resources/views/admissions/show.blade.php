@extends('components.app-layout')

@section('title', $admission->full_name)
@section('subtitle', $admission->applicant_number . ' - ' . ($admission->programme->name ?? ''))

@section('content')
<div class="py-4 space-y-6">

    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admissions.letter', $admission) }}" class="px-3 py-2 bg-gray-100 rounded-md text-sm hover:bg-gray-200">
            <i class="fas fa-file-pdf mr-1"></i> Admission Letter
        </a>
        <a href="{{ route('admissions.acceptance-form', $admission) }}" class="px-3 py-2 bg-gray-100 rounded-md text-sm hover:bg-gray-200">
            <i class="fas fa-file-signature mr-1"></i> Acceptance Form
        </a>
        <a href="{{ route('admission-billing.show', $admission) }}" class="px-3 py-2 bg-gray-100 rounded-md text-sm hover:bg-gray-200">
            <i class="fas fa-money-bill-wave mr-1"></i> Billing &amp; Payments
        </a>
        @unless($admission->isWithdrawn() || $admission->isMigrated())
            <form action="{{ route('admissions.withdraw', $admission) }}" method="POST" onsubmit="return confirm('Withdraw this admission?');">
                @csrf
                <button type="submit" class="px-3 py-2 bg-red-50 text-red-700 rounded-md text-sm hover:bg-red-100">
                    <i class="fas fa-ban mr-1"></i> Withdraw
                </button>
            </form>
        @endunless
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white rounded-lg shadow-sm p-6 text-center">
                @if($admission->passport_photo)
                    <img src="{{ route('admissions.photo.view', $admission) }}" alt="Passport photo" class="w-32 h-32 object-cover rounded-lg mx-auto border border-gray-200">
                @else
                    <div class="w-32 h-32 rounded-lg mx-auto bg-gray-100 flex items-center justify-center text-gray-400">
                        <i class="fas fa-user text-4xl"></i>
                    </div>
                @endif
                <h3 class="mt-4 font-bold text-gray-800">{{ $admission->full_name }}</h3>
                <p class="text-sm text-gray-500">{{ $admission->applicant_number }}</p>
                <a href="{{ route('admissions.photo.edit', $admission) }}" class="inline-block mt-3 text-sm text-primary-600 hover:underline">
                    {{ $admission->passport_photo ? 'Replace photo' : 'Upload photo' }}
                </a>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 space-y-2 text-sm">
                <h4 class="font-semibold text-gray-700 mb-2">Details</h4>
                <p><span class="text-gray-500">Programme:</span> {{ $admission->programme->name ?? '' }}</p>
                <p><span class="text-gray-500">Academic Year:</span> {{ $admission->academicYear->name ?? '' }}</p>
                <p><span class="text-gray-500">Level:</span> {{ $admission->level }}</p>
                <p><span class="text-gray-500">Gender:</span> {{ $admission->gender }}</p>
                <p><span class="text-gray-500">Date of Birth:</span> {{ optional($admission->date_of_birth)->format('M j, Y') }}</p>
                <p><span class="text-gray-500">Phone:</span> {{ $admission->phone }}</p>
                <p><span class="text-gray-500">Email:</span> {{ $admission->email }}</p>
                <p><span class="text-gray-500">Hall:</span> {{ $admission->hall ?? '-' }}</p>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 text-sm">
                <h4 class="font-semibold text-gray-700 mb-2">Institutional Reference Number</h4>
                <p class="text-xs text-gray-500 mb-2">Entered by staff - the institution's own externally-assigned number. Required before migration; becomes the student's initial index number.</p>
                <form action="{{ route('admissions.reference-number', $admission) }}" method="POST" class="flex gap-2">
                    @csrf
                    @method('PUT')
                    <input type="text" name="reference_number" value="{{ $admission->reference_number }}" maxlength="7" placeholder="7-digit number" class="flex-1 rounded-md border-gray-300 text-sm">
                    <button type="submit" class="px-3 py-1.5 bg-gray-100 rounded-md text-sm hover:bg-gray-200">Save</button>
                </form>
                @error('reference_number')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 space-y-3 text-sm">
                <h4 class="font-semibold text-gray-700">Admission Processing</h4>

                <form action="{{ route('admissions.hall', $admission) }}" method="POST" onsubmit="return prepareHallSubmit(this);">
                    @csrf
                    <div class="flex gap-2">
                        <select id="hall-select" class="flex-1 rounded-md border-gray-300 text-sm">
                            <option value="">Select a hall...</option>
                            @foreach($halls as $hall)
                                <option value="{{ $hall }}" @selected($admission->hall === $hall)>{{ $hall }}</option>
                            @endforeach
                            <option value="__new__" @selected($admission->hall && !$halls->contains($admission->hall))>+ Add a new hall...</option>
                        </select>
                        <input type="hidden" name="hall" id="hall-input">
                        <button type="submit" class="px-3 py-1.5 bg-gray-100 rounded-md text-sm hover:bg-gray-200">Save</button>
                    </div>
                    <input type="text" id="hall-new-name" placeholder="New hall name"
                           value="{{ $admission->hall && !$halls->contains($admission->hall) ? $admission->hall : '' }}"
                           class="mt-2 w-full rounded-md border-gray-300 text-sm {{ $admission->hall && !$halls->contains($admission->hall) ? '' : 'hidden' }}">
                </form>

                <script>
                    (function () {
                        const select = document.getElementById('hall-select');
                        const newNameInput = document.getElementById('hall-new-name');

                        function toggleNewHallInput() {
                            newNameInput.classList.toggle('hidden', select.value !== '__new__');
                        }

                        select.addEventListener('change', toggleNewHallInput);
                        toggleNewHallInput();
                    })();

                    function prepareHallSubmit(form) {
                        const select = form.querySelector('#hall-select');
                        const newName = form.querySelector('#hall-new-name');
                        const hidden = form.querySelector('#hall-input');

                        hidden.value = select.value === '__new__' ? newName.value.trim() : select.value;

                        if (!hidden.value) {
                            alert('Select a hall, or enter a new hall name.');
                            return false;
                        }

                        return true;
                    }
                </script>

                <form action="{{ route('admissions.documents-verified', $admission) }}" method="POST">
                    @csrf
                    <button type="submit" {{ $admission->documents_verified_at ? 'disabled' : '' }}
                            class="w-full text-left px-3 py-2 rounded-md text-sm {{ $admission->documents_verified_at ? 'bg-green-50 text-green-700' : 'bg-gray-100 hover:bg-gray-200' }}">
                        <i class="fas {{ $admission->documents_verified_at ? 'fa-check-circle' : 'fa-file-circle-check' }} mr-1"></i>
                        {{ $admission->documents_verified_at ? 'Documents Verified' : 'Mark Documents Verified' }}
                    </button>
                </form>

                <form action="{{ route('admissions.approve', $admission) }}" method="POST">
                    @csrf
                    <button type="submit" {{ !$admission->reported_at || $admission->admission_status === 'approved' || $admission->isMigrated() ? 'disabled' : '' }}
                            class="w-full text-left px-3 py-2 rounded-md text-sm {{ $admission->admission_status === 'approved' || $admission->isMigrated() ? 'bg-green-50 text-green-700' : 'bg-gray-100 hover:bg-gray-200 disabled:opacity-50' }}">
                        <i class="fas fa-stamp mr-1"></i>
                        {{ $admission->admission_status === 'approved' || $admission->isMigrated() ? 'Approved' : 'Approve Admission' }}
                    </button>
                </form>

                <form action="{{ route('admissions.migrate', $admission) }}" method="POST" onsubmit="return confirm('Migrate this applicant into the Student register? This cannot be undone.');">
                    @csrf
                    <label class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                        <input type="checkbox" name="reuse_photo" value="1" checked> Reuse admission photo as official student photo
                    </label>
                    <button type="submit" {{ !$canMigrate ? 'disabled' : '' }}
                            class="w-full px-3 py-2 rounded-md text-sm font-medium {{ $canMigrate ? 'bg-primary-600 text-white hover:bg-primary-700' : 'bg-gray-100 text-gray-400 cursor-not-allowed' }}">
                        <i class="fas fa-arrow-right-to-bracket mr-1"></i> Migrate to Student
                    </button>
                    @unless($canMigrate)
                        <p class="text-xs text-gray-500 mt-1">Requires: reported, profile confirmed, payment confirmed, documents verified, approved, and a reference number that isn't already in use.</p>
                    @endunless
                </form>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="font-semibold text-gray-700">Billing Summary</h4>
                    <span class="px-2 py-1 text-xs rounded {{ $admission->payment_status === 'confirmed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ ucfirst(str_replace('_', ' ', $admission->payment_status)) }}
                    </span>
                </div>
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Total Billed</p>
                        <p class="text-lg font-semibold">GH&cent; {{ number_format($admission->totalBilled(), 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Total Paid</p>
                        <p class="text-lg font-semibold text-green-600">GH&cent; {{ number_format($admission->totalPaid(), 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Outstanding</p>
                        <p class="text-lg font-semibold {{ $admission->outstandingBalance() > 0 ? 'text-red-600' : 'text-green-600' }}">GH&cent; {{ number_format($admission->outstandingBalance(), 2) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <h4 class="font-semibold text-gray-700 mb-4">Audit Trail</h4>
                <div class="space-y-3 max-h-96 overflow-y-auto text-sm">
                    @forelse($admission->auditLogs as $log)
                        <div class="border-b border-gray-100 pb-2">
                            <p class="text-gray-800">{{ str_replace('admission.', '', $log->action) }}
                                @if($log->amount) - GH&cent;{{ number_format($log->amount, 2) }} @endif
                                @if($log->reference) ({{ $log->reference }}) @endif
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $log->performedBy->name ?? 'System' }} &middot; {{ $log->created_at->format('M j, Y g:i A') }}
                                @if($log->reason) &middot; {{ $log->reason }} @endif
                            </p>
                        </div>
                    @empty
                        <p class="text-gray-500">No activity recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

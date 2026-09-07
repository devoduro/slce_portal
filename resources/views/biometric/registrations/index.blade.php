<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Biometric Verifications') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="GET" action="{{ route('biometric-verifications.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
                        <div>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or index number" class="block w-full pl-3 pr-3 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <select name="programme_id" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                <option value="">All Programmes</option>
                                @foreach($programmes as $programme)
                                    <option value="{{ $programme->id }}" {{ request('programme_id') == $programme->id ? 'selected' : '' }}>{{ $programme->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <select name="status" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                <option value="">Any Status</option>
                                <option value="verified" {{ request('status') === 'verified' ? 'selected' : '' }}>Verified</option>
                                <option value="not_verified" {{ request('status') === 'not_verified' ? 'selected' : '' }}>Not Verified</option>
                            </select>
                        </div>
                        <div>
                            <select name="semester_id" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                @foreach($semesters as $sem)
                                    <option value="{{ $sem->id }}" {{ ($semester?->id) == $sem->id ? 'selected' : '' }}>{{ $sem->name }} ({{ $sem->academicYear->name ?? '' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <select name="registered" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                <option value="">All Students</option>
                                <option value="1" {{ request('registered') ? 'selected' : '' }}>Registered This Year</option>
                            </select>
                        </div>
                        <div>
                            <select name="per_page" onchange="this.form.submit()" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                @foreach([20, 50, 100, 200, 300, 500, 5000] as $option)
                                    <option value="{{ $option }}" {{ (int) request('per_page', 20) === $option ? 'selected' : '' }}>{{ $option }} per page</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="w-full bg-primary-600 text-white rounded-md px-4 py-2 text-sm hover:bg-primary-700">
                                <i class="fas fa-filter mr-1"></i> Filter
                            </button>
                        </div>
                    </form>

                    @if(!$semester)
                        <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 mb-4">
                            No semester selected or marked current.
                        </div>
                    @else
                        <form method="POST" action="{{ route('biometric-verifications.bulk-verify', request()->query()) }}">
                            @csrf
                            <input type="hidden" name="semester_id" value="{{ $semester->id }}">

                            <div class="flex items-center justify-between mb-3">
                                <label class="flex items-center gap-2 text-sm text-gray-600">
                                    <input type="checkbox" id="select-all" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    Select All
                                </label>
                                <button type="submit" id="bulk-verify-btn" disabled
                                    class="bg-green-600 text-white rounded-md px-4 py-2 text-sm hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                    onclick="return confirm('Mark the selected student(s) as biometrically verified for {{ $semester->name }}?')">
                                    <i class="fas fa-fingerprint mr-1"></i> Verify Selected (<span id="selected-count">0</span>)
                                </button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10"></th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Programme</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verified At</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @forelse($students as $student)
                                            @php $registration = $registrations->get($student->id); @endphp
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="verify-checkbox rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900">{{ $student->full_name }}</div>
                                                    <div class="text-sm text-gray-500">{{ $student->index_number }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->programme->name ?? 'N/A' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    @if($registration)
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Verified</span>
                                                    @else
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Not Verified</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $registration?->verified_at?->format('M d, Y H:i') ?? '-' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $registration ? ucfirst($registration->source) : '-' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <a href="{{ route('biometric-verifications.show', $student) }}" class="text-primary-600 hover:text-primary-900">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="px-6 py-8 text-center text-gray-400">No students found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    @endif

                    <div class="mt-4">
                        {{ $students->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const selectAll = document.getElementById('select-all');
        const checkboxes = document.querySelectorAll('.verify-checkbox');
        const bulkVerifyBtn = document.getElementById('bulk-verify-btn');
        const selectedCount = document.getElementById('selected-count');

        function updateBulkVerifyState() {
            const checked = document.querySelectorAll('.verify-checkbox:checked').length;
            bulkVerifyBtn.disabled = checked === 0;
            selectedCount.textContent = checked;
        }

        selectAll?.addEventListener('change', () => {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBulkVerifyState();
        });

        checkboxes.forEach(cb => cb.addEventListener('change', updateBulkVerifyState));
    </script>
    @endpush
</x-app-layout>

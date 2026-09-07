<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Student Fees') }}
            </h2>
            <div class="flex gap-2">
                <x-button href="{{ route('fees.payments.upload') }}" icon="fas fa-upload">
                    {{ __('Upload Payments') }}
                </x-button>
                <x-button href="{{ route('fees.export.excel', request()->query()) }}" variant="secondary" icon="fas fa-file-excel">
                    {{ __('Export Excel') }}
                </x-button>
                <x-button href="{{ route('fees.export.pdf', request()->query()) }}" variant="secondary" icon="fas fa-file-pdf" target="_blank">
                    {{ __('Export PDF') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="GET" action="{{ route('fees.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
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
                            <select name="level" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                <option value="">All Levels</option>
                                @foreach($levels as $level)
                                    <option value="{{ $level }}" {{ (string) request('level') === (string) $level ? 'selected' : '' }}>Level {{ $level }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <select name="status" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                <option value="">All Students</option>
                                <option value="debtors" {{ request('status') === 'debtors' ? 'selected' : '' }}>Debtors (owe fees)</option>
                                <option value="creditors" {{ request('status') === 'creditors' ? 'selected' : '' }}>Creditors (overpaid)</option>
                            </select>
                        </div>
                        <div>
                            <select name="academic_year_id" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}" {{ ($academicYear?->id) == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="w-full bg-primary-600 text-white rounded-md px-4 py-2 text-sm hover:bg-primary-700">
                                <i class="fas fa-filter mr-1"></i> Filter
                            </button>
                        </div>
                    </form>

                    @if(!$academicYear)
                        <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 mb-4">
                            No academic year selected or marked current. Select one above to view fee status.
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Programme</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Arrears</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">%</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($students as $row)
                                    @php $student = $row['student']; @endphp
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $student->full_name }}</div>
                                            <div class="text-sm text-gray-500">{{ $student->index_number }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->programme->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if($student->status === 'graduated')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                                    {{ $row['level_label'] }}
                                                </span>
                                            @else
                                                {{ $row['level_label'] }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($row['arrears'] > 0)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    {{ number_format($row['arrears'], 2) }}
                                                </span>
                                            @elseif($row['arrears'] < 0)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    {{ number_format($row['arrears'], 2) }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $row['fee_amount'] !== null ? number_format($row['fee_amount'], 2) : 'Not set' }}
                                            @if($row['fee_amount'] !== null && $row['arrears'] != 0)
                                                <div class="text-xs text-gray-400">after arrears</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($row['paid'], 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ $row['status'] === 'debtor' ? 'text-red-600' : ($row['status'] === 'creditor' ? 'text-green-600' : 'text-gray-900') }}">
                                            {{ number_format($row['balance'], 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($row['status'] === 'debtor')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Debtor</span>
                                            @elseif($row['status'] === 'creditor')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Creditor</span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Settled</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $row['percentage'] >= 100 ? 'bg-green-100 text-green-800' : ($row['percentage'] > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                                {{ number_format($row['percentage'], 1) }}%
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('fees.show', ['student' => $student->id, 'academic_year_id' => $academicYear?->id]) }}" class="text-primary-600 hover:text-primary-900">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="px-6 py-8 text-center text-gray-400">No students found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $students->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

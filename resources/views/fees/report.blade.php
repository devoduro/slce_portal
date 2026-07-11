<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Fees Report') }}
            </h2>
            <div class="flex gap-2">
                @if($academicYear)
                    <x-button href="{{ route('fees.report.print', ['academic_year_id' => $academicYear->id]) }}" variant="secondary" icon="fas fa-print" target="_blank">
                        {{ __('Print') }}
                    </x-button>
                @endif
                <x-button href="{{ route('fees.index') }}" variant="secondary" icon="fas fa-arrow-left">
                    {{ __('Back to Fees') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Academic Year Switcher -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('fees.report') }}" class="flex items-end gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Academic Year</label>
                        <select name="academic_year_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500" onchange="this.form.submit()">
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}" {{ ($academicYear?->id) == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            @if(!$academicYear)
                <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700">
                    No academic years have been created yet.
                </div>
            @else
                <!-- Grand Totals -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <p class="text-sm text-gray-500">Total Expected</p>
                        <p class="text-xl font-semibold text-gray-900">{{ number_format($grandExpected, 2) }}</p>
                    </div>
                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <p class="text-sm text-gray-500">Total Collected</p>
                        <p class="text-xl font-semibold text-green-600">{{ number_format($grandCollected, 2) }}</p>
                    </div>
                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <p class="text-sm text-gray-500">Outstanding Balance</p>
                        <p class="text-xl font-semibold text-red-600">{{ number_format($grandExpected - $grandCollected, 2) }}</p>
                    </div>
                </div>

                <!-- Breakdown by Programme / Level -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Breakdown by Programme &amp; Level - {{ $academicYear->name }}</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Programme</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Students</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Fee Amount</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Expected</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Collected</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">% Collected</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($rows as $row)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row['programme'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row['level'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">{{ $row['students'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">{{ $row['fee_amount'] !== null ? number_format($row['fee_amount'], 2) : 'Not set' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">{{ number_format($row['expected'], 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-green-600">{{ number_format($row['collected'], 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-red-600">{{ number_format($row['balance'], 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">{{ $row['percentage'] }}%</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-6 py-8 text-center text-gray-400">No students found for this academic year.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

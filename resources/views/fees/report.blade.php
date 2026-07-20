<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Fees Report') }}
            </h2>
            <div class="flex gap-2">
                @if($academicYear)
                    <x-button href="{{ route('fees.report.export', request()->query()) }}" variant="secondary" icon="fas fa-file-excel">
                        {{ __('Export Excel') }}
                    </x-button>
                    <x-button href="{{ route('fees.report.print', request()->query()) }}" variant="secondary" icon="fas fa-print" target="_blank">
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

            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('fees.report') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Academic Year</label>
                        <select name="academic_year_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}" {{ ($academicYear?->id) == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Programme</label>
                        <select name="programme_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                            <option value="">All Programmes</option>
                            @foreach($programmes as $programme)
                                <option value="{{ $programme->id }}" {{ $programmeId === $programme->id ? 'selected' : '' }}>{{ $programme->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Level</label>
                        <select name="level" class="mt-1 block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                            <option value="">All Levels</option>
                            @foreach([100, 200, 300, 400] as $lvl)
                                <option value="{{ $lvl }}" {{ $level === $lvl ? 'selected' : '' }}>{{ $lvl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fee Category</label>
                        <select name="category" class="mt-1 block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                            @foreach(\App\Models\FeeCategory::options() as $value => $label)
                                <option value="{{ $value }}" {{ $category === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-primary-600 text-white rounded-md px-4 py-2 text-sm hover:bg-primary-700">
                            <i class="fas fa-filter mr-1"></i> Filter
                        </button>
                        @if(request()->hasAny(['programme_id', 'level']) || $category !== 'tuition')
                            <a href="{{ route('fees.report', ['academic_year_id' => $academicYear?->id]) }}" class="inline-flex items-center px-3 py-2 text-sm text-gray-600 hover:text-primary-600">
                                Clear
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            @if(!$academicYear)
                <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700">
                    No academic years have been created yet.
                </div>
            @else
                <!-- Billing Totals (selected category) -->
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">{{ \App\Models\FeeCategory::options()[$category] ?? ucfirst($category) }} Billing - {{ $academicYear->name }}</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white shadow-sm rounded-lg p-4">
                            <p class="text-sm text-gray-500">Total Expected</p>
                            <p class="text-xl font-semibold text-gray-900">{{ number_format($grandExpected, 2) }}</p>
                        </div>
                        <div class="bg-white shadow-sm rounded-lg p-4">
                            <p class="text-sm text-gray-500">Total Collected</p>
                            <p class="text-xl font-semibold text-green-600">{{ number_format($grandCollected, 2) }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">All payments recorded this year (not tagged by category)</p>
                        </div>
                        <div class="bg-white shadow-sm rounded-lg p-4">
                            <p class="text-sm text-gray-500">{{ ($grandExpected - $grandCollected) >= 0 ? 'Outstanding Balance' : 'Collected Over Expected' }}</p>
                            <p class="text-xl font-semibold {{ ($grandExpected - $grandCollected) > 0 ? 'text-red-600' : 'text-green-600' }}">{{ number_format(abs($grandExpected - $grandCollected), 2) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Student Standing Metrics (always tuition-scoped, matches Finance Dashboard) -->
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">
                        Student Standing (Tuition)
                        @if($programmeId || $level) - filtered to current selection @endif
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div class="bg-blue-500 rounded-xl shadow-sm p-4 text-white">
                            <div class="text-2xl font-bold">{{ $metrics['collectionRate'] }}%</div>
                            <div class="text-sm text-white text-opacity-90">Collection Rate</div>
                        </div>
                        <a href="{{ route('fees.index', ['academic_year_id' => $academicYear->id, 'status' => 'debtors', 'programme_id' => $programmeId, 'level' => $level]) }}" class="bg-white rounded-xl shadow-sm p-4 border border-red-100 hover:shadow-md">
                            <div class="text-2xl font-bold text-red-600">{{ number_format($metrics['debtorCount']) }}</div>
                            <div class="text-sm text-gray-500">Debtors</div>
                        </a>
                        <a href="{{ route('fees.index', ['academic_year_id' => $academicYear->id, 'status' => 'creditors', 'programme_id' => $programmeId, 'level' => $level]) }}" class="bg-white rounded-xl shadow-sm p-4 border border-green-100 hover:shadow-md">
                            <div class="text-2xl font-bold text-green-600">{{ number_format($metrics['creditorCount']) }}</div>
                            <div class="text-sm text-gray-500">Creditors</div>
                        </a>
                        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
                            <div class="text-2xl font-bold text-gray-700">{{ number_format($metrics['settledCount']) }}</div>
                            <div class="text-sm text-gray-500">Settled</div>
                        </div>
                        <div class="bg-{{ $metrics['netArrears'] > 0 ? 'orange' : 'purple' }}-500 rounded-xl shadow-sm p-4 text-white">
                            <div class="text-2xl font-bold">{{ number_format($metrics['netArrears'], 2) }}</div>
                            <div class="text-sm text-white text-opacity-90">Net Arrears</div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2 bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Monthly Collections</h3>
                        <div class="h-64">
                            @if($metrics['monthlyCollections']->isEmpty())
                                <div class="flex items-center justify-center h-full text-gray-400 text-sm">No payments recorded yet.</div>
                            @else
                                <canvas id="monthlyChart"></canvas>
                            @endif
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Payment Methods</h3>
                        <div class="h-64">
                            @if($metrics['paymentMethodBreakdown']->isEmpty())
                                <div class="flex items-center justify-center h-full text-gray-400 text-sm">No payments recorded yet.</div>
                            @else
                                <canvas id="methodChart"></canvas>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Breakdown by Programme / Level -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Breakdown by Programme &amp; Level - {{ $academicYear->name }} ({{ \App\Models\FeeCategory::options()[$category] ?? ucfirst($category) }})</h3>
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
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm {{ $row['balance'] > 0 ? 'text-red-600' : ($row['balance'] < 0 ? 'text-green-600' : 'text-gray-500') }}">{{ number_format($row['balance'], 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">{{ $row['percentage'] }}%</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-6 py-8 text-center text-gray-400">No students found for this filter.</td>
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

@if($academicYear)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const monthlyCanvas = document.getElementById('monthlyChart');
    if (monthlyCanvas) {
        new Chart(monthlyCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($metrics['monthlyCollections']->pluck('month')) !!},
                datasets: [{
                    label: 'Collected',
                    data: {!! json_encode($metrics['monthlyCollections']->pluck('total')) !!},
                    backgroundColor: 'rgba(14, 165, 233, 0.6)',
                    borderColor: 'rgba(14, 165, 233, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } },
                plugins: { legend: { display: false } }
            }
        });
    }

    const methodCanvas = document.getElementById('methodChart');
    if (methodCanvas) {
        new Chart(methodCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($metrics['paymentMethodBreakdown']->keys()->map(fn($k) => ucwords(str_replace('_', ' ', $k)))->values()) !!},
                datasets: [{
                    data: {!! json_encode($metrics['paymentMethodBreakdown']->values()) !!},
                    backgroundColor: [
                        'rgba(14, 165, 233, 0.7)',
                        'rgba(34, 197, 94, 0.7)',
                        'rgba(249, 115, 22, 0.7)',
                        'rgba(168, 85, 247, 0.7)',
                        'rgba(239, 68, 68, 0.7)'
                    ]
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }
});
</script>
@endpush
@endif

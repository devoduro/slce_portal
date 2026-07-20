@extends('components.app-layout')

@section('title', 'Finance Dashboard')
@section('subtitle', 'College and student finances overview')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <!-- Welcome Banner -->
    <div class="col-span-1 md:col-span-4">
        <div class="gradient-bg rounded-lg shadow-lg p-6 text-white">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold mb-2">Finance Overview</h2>
                    <p class="opacity-90">
                        @if($currentAcademicYear)
                            {{ $currentAcademicYear->name }} &bull; college and student fee position at a glance.
                        @else
                            No current academic year is set - figures below cannot be calculated.
                        @endif
                    </p>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="{{ route('fees.index') }}" class="inline-flex items-center px-4 py-2 bg-white text-primary-600 rounded-lg font-medium shadow-sm hover:bg-gray-50 transition-colors">
                        <i class="fas fa-file-invoice-dollar mr-2"></i> View All Student Fees
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(!$currentAcademicYear)
        <div class="col-span-1 md:col-span-4 p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 rounded">
            No academic year is marked current. Set one in Settings to see finance figures here.
        </div>
    @else
        <!-- Stat Cards -->
        <div class="bg-green-600 rounded-2xl shadow-lg p-6 flex items-center justify-between">
            <div>
                <div class="text-3xl font-bold text-white">{{ number_format($totalCollected, 2) }}</div>
                <div class="text-white text-opacity-90 font-medium mt-1">Collected This Year</div>
            </div>
            <div class="w-14 h-14 rounded-full bg-white bg-opacity-20 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-money-bill-wave text-2xl text-white"></i>
            </div>
        </div>

        <div class="bg-{{ $totalOutstanding > 0 ? 'red' : 'green' }}-500 rounded-2xl shadow-lg p-6 flex items-center justify-between">
            <div>
                <div class="text-3xl font-bold text-white">{{ number_format(abs($totalOutstanding), 2) }}</div>
                <div class="text-white text-opacity-90 font-medium mt-1">{{ $totalOutstanding > 0 ? 'Outstanding This Year' : 'Collected Over Tuition Billed' }}</div>
                <div class="text-xs text-white text-opacity-80 mt-0.5">
                    @if($totalOutstanding > 0)
                        of {{ number_format($totalBilled, 2) }} billed (tuition)
                    @else
                        of {{ number_format($totalBilled, 2) }} billed - likely includes payments toward other charges or advance payments
                    @endif
                </div>
            </div>
            <div class="w-14 h-14 rounded-full bg-white bg-opacity-20 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-{{ $totalOutstanding > 0 ? 'exclamation-circle' : 'check-circle' }} text-2xl text-white"></i>
            </div>
        </div>

        <div class="bg-blue-500 rounded-2xl shadow-lg p-6 flex items-center justify-between">
            <div>
                <div class="text-3xl font-bold text-white">{{ $collectionRate }}%</div>
                <div class="text-white text-opacity-90 font-medium mt-1">Collection Rate</div>
            </div>
            <div class="w-14 h-14 rounded-full bg-white bg-opacity-20 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-chart-pie text-2xl text-white"></i>
            </div>
        </div>

        <div class="bg-{{ $totalArrearsNet > 0 ? 'orange' : 'purple' }}-500 rounded-2xl shadow-lg p-6 flex items-center justify-between">
            <div>
                <div class="text-3xl font-bold text-white">{{ number_format($totalArrearsNet, 2) }}</div>
                <div class="text-white text-opacity-90 font-medium mt-1">Net Arrears Balance</div>
                <div class="text-xs text-white text-opacity-80 mt-0.5">{{ $totalArrearsNet > 0 ? 'net owed to college' : ($totalArrearsNet < 0 ? 'net credit owed to students' : 'balanced') }}</div>
            </div>
            <div class="w-14 h-14 rounded-full bg-white bg-opacity-20 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-balance-scale text-2xl text-white"></i>
            </div>
        </div>

        <!-- Debtor / Creditor / Settled counts -->
        <div class="col-span-1 md:col-span-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('fees.index', ['status' => 'debtors']) }}" class="bg-white rounded-lg shadow-sm p-4 border border-red-100 hover:shadow-md transition-shadow flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-red-600">{{ number_format($debtorCount) }}</div>
                    <div class="text-sm text-gray-500">Debtors</div>
                </div>
                <i class="fas fa-arrow-right text-red-300"></i>
            </a>
            <a href="{{ route('fees.index', ['status' => 'creditors']) }}" class="bg-white rounded-lg shadow-sm p-4 border border-green-100 hover:shadow-md transition-shadow flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-green-600">{{ number_format($creditorCount) }}</div>
                    <div class="text-sm text-gray-500">Creditors (overpaid)</div>
                </div>
                <i class="fas fa-arrow-right text-green-300"></i>
            </a>
            <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100 flex items-center justify-between">
                <div>
                    <div class="text-2xl font-bold text-gray-700">{{ number_format($settledCount) }}</div>
                    <div class="text-sm text-gray-500">Settled</div>
                </div>
                <i class="fas fa-check-circle text-gray-300"></i>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-span-1 md:col-span-4 bg-white rounded-lg shadow-sm p-4 border border-gray-200">
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('fees.payments.upload') }}" class="px-4 py-2 bg-primary-50 text-primary-700 rounded-lg text-sm font-medium hover:bg-primary-100"><i class="fas fa-upload mr-1"></i> Upload Payments</a>
                <a href="{{ route('fees.arrears.upload') }}" class="px-4 py-2 bg-primary-50 text-primary-700 rounded-lg text-sm font-medium hover:bg-primary-100"><i class="fas fa-upload mr-1"></i> Upload Arrears</a>
                <a href="{{ route('fees.arrears.index') }}" class="px-4 py-2 bg-primary-50 text-primary-700 rounded-lg text-sm font-medium hover:bg-primary-100"><i class="fas fa-list mr-1"></i> Arrears / Debtors</a>
                <a href="{{ route('fees.charges.index') }}" class="px-4 py-2 bg-primary-50 text-primary-700 rounded-lg text-sm font-medium hover:bg-primary-100"><i class="fas fa-user-tag mr-1"></i> Specific Student Charges</a>
                <a href="{{ route('fee-structures.index') }}" class="px-4 py-2 bg-primary-50 text-primary-700 rounded-lg text-sm font-medium hover:bg-primary-100"><i class="fas fa-sitemap mr-1"></i> Fee Structures</a>
            </div>
        </div>

        <!-- Monthly Collections Trend -->
        <div class="col-span-1 md:col-span-3 bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <h3 class="text-lg font-medium text-gray-800 mb-4">Monthly Collections (Last 12 Months)</h3>
            <div class="h-72">
                <canvas id="collectionsChart"></canvas>
            </div>
        </div>

        <!-- Payment Method Breakdown -->
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <h3 class="text-lg font-medium text-gray-800 mb-4">Payment Methods (This Year)</h3>
            <div class="h-72">
                @if($paymentMethodBreakdown->isEmpty())
                    <div class="flex items-center justify-center h-full text-gray-400 text-sm">No payments recorded yet.</div>
                @else
                    <canvas id="paymentMethodChart"></canvas>
                @endif
            </div>
        </div>

        <!-- Programme-wise Collection -->
        <div class="col-span-1 md:col-span-4 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-lg font-medium text-gray-800">Collection by Programme (This Year)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Programme</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Students</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Billed</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Collected</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Outstanding</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($programmeSummary as $row)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $row['name'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row['students'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">{{ number_format($row['billed'], 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-green-600">{{ number_format($row['collected'], 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm {{ $row['outstanding'] > 0 ? 'text-red-600' : 'text-gray-500' }}">{{ number_format($row['outstanding'], 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $row['collection_rate'] >= 100 ? 'bg-green-100 text-green-800' : ($row['collection_rate'] > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">{{ $row['collection_rate'] }}%</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400">No programme fee data available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Debtors -->
        <div class="col-span-1 md:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-800">Top 10 Debtors</h3>
                <a href="{{ route('fees.index', ['status' => 'debtors']) }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">View all <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
            <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                @forelse($topDebtors as $entry)
                    <a href="{{ route('fees.show', ['student' => $entry['student']->id, 'academic_year_id' => $currentAcademicYear->id]) }}" class="flex items-center justify-between p-4 hover:bg-gray-50">
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $entry['student']->full_name }}</div>
                            <div class="text-xs text-gray-500">{{ $entry['student']->index_number }} &bull; {{ $entry['student']->programme->name ?? 'N/A' }}</div>
                        </div>
                        <div class="text-sm font-semibold text-red-600">{{ number_format($entry['balance'], 2) }}</div>
                    </a>
                @empty
                    <div class="p-8 text-center text-gray-400">No debtors - everyone is settled or in credit.</div>
                @endforelse
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="col-span-1 md:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-800">Recent Payments</h3>
                <a href="{{ route('fees.payments.upload') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">View all <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
            <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                @forelse($recentPayments as $payment)
                    <div class="flex items-center justify-between p-4">
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $payment->student->full_name ?? 'Unknown' }}</div>
                            <div class="text-xs text-gray-500">{{ $payment->academicYear->name ?? 'N/A' }} &bull; {{ \Illuminate\Support\Carbon::parse($payment->payment_date)->format('M d, Y') }} &bull; {{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</div>
                        </div>
                        <div class="text-sm font-semibold text-green-600">{{ number_format($payment->amount, 2) }}</div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-400">No payments recorded yet.</div>
                @endforelse
            </div>
        </div>

        @if($categoryBreakdown->isNotEmpty())
            <!-- Other Fee Charges (Graduation, Resit, etc.) -->
            <div class="col-span-1 md:col-span-4 bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                <h3 class="text-lg font-medium text-gray-800 mb-4">Additional Charges Billed This Year (by Category)</h3>
                <p class="text-sm text-gray-500 mb-4">One-off charges billed to specific students (graduation, resit, etc.) - separate from the tuition figures above.</p>
                <div class="flex flex-wrap gap-4">
                    @foreach($categoryBreakdown as $label => $amount)
                        <div class="bg-gray-50 rounded-lg px-4 py-3">
                            <div class="text-xs text-gray-500">{{ $label }}</div>
                            <div class="text-lg font-semibold text-gray-800">{{ number_format($amount, 2) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($missingStructures->isNotEmpty())
            <!-- Missing Fee Structures -->
            <div class="col-span-1 md:col-span-4 bg-yellow-50 border-l-4 border-yellow-400 rounded-lg p-6">
                <h3 class="text-lg font-medium text-yellow-800 mb-2"><i class="fas fa-triangle-exclamation mr-1"></i> Missing Tuition Fee Structures</h3>
                <p class="text-sm text-yellow-700 mb-3">These programme/level combinations have active students but no tuition fee structure set for {{ $currentAcademicYear->name }}, so their fee amounts show as "Not set".</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($missingStructures as $gap)
                        <span class="px-3 py-1 bg-white border border-yellow-300 rounded-full text-xs text-yellow-800">{{ $gap['programme'] }} &bull; Level {{ $gap['level'] }}</span>
                    @endforeach
                </div>
                <a href="{{ route('fee-structures.create') }}" class="inline-block mt-3 text-sm text-yellow-800 underline">Add a fee structure</a>
            </div>
        @endif
    @endif
</div>
@endsection

@if($currentAcademicYear)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const collectionsCanvas = document.getElementById('collectionsChart');
    if (collectionsCanvas) {
        new Chart(collectionsCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: {!! json_encode(array_column($monthlyCollections, 'month')) !!},
                datasets: [{
                    label: 'Collected',
                    data: {!! json_encode(array_column($monthlyCollections, 'total')) !!},
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

    const paymentMethodCanvas = document.getElementById('paymentMethodChart');
    if (paymentMethodCanvas) {
        new Chart(paymentMethodCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($paymentMethodBreakdown->keys()->map(fn($k) => ucwords(str_replace('_', ' ', $k)))->values()) !!},
                datasets: [{
                    data: {!! json_encode($paymentMethodBreakdown->values()) !!},
                    backgroundColor: [
                        'rgba(14, 165, 233, 0.7)',
                        'rgba(34, 197, 94, 0.7)',
                        'rgba(249, 115, 22, 0.7)',
                        'rgba(168, 85, 247, 0.7)',
                        'rgba(239, 68, 68, 0.7)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
});
</script>
@endpush
@endif

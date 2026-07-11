@extends('components.student-app-layout')

@section('header')
    <div class="bg-white rounded-2xl p-6 mb-6 shadow-sm border border-gray-100">
        <h2 class="text-2xl font-bold text-gray-800">Fees</h2>
        <p class="text-gray-500 mt-1">Fee schedule and statement of account</p>
    </div>
@endsection

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <!-- Balance Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-sm text-gray-500">Arrears (Previous Years)</p>
            <p class="text-xl font-semibold {{ $totalArrears > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($totalArrears, 2) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="text-sm text-gray-500">Current Academic Year Balance</p>
            <p class="text-xl font-semibold {{ $currentYearBalance > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($currentYearBalance, 2) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border-2 {{ $balanceDue > 0 ? 'border-red-300' : 'border-gray-100' }} p-4">
            <p class="text-sm text-gray-500">Total Balance Due</p>
            <p class="text-2xl font-bold {{ $balanceDue > 0 ? 'text-red-600' : 'text-green-600' }}">{{ number_format($balanceDue, 2) }}</p>
        </div>
    </div>

    @if($balanceDue > 0)
        <div class="p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded text-sm">
            You have an outstanding balance of <span class="font-semibold">{{ number_format($balanceDue, 2) }}</span> — this is your arrears plus any unpaid tuition across all academic years shown in the statement below.
        </div>
    @endif

    <!-- Fee Schedule -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800">Fee Schedule</h3>
            <p class="text-sm text-gray-500 mt-1">Fee amounts for {{ $student->programme->name ?? 'your programme' }} across every level and academic year.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Academic Year</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($schedule as $fee)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $fee->academicYear->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $fee->level ?? 'All Levels' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">{{ number_format($fee->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-gray-400">No fee schedule has been published for your programme yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Transactions -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800">Transactions</h3>
            <p class="text-sm text-gray-500 mt-1">Full statement of account, most recent first.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Academic Year</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bank</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Mode</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($ledger as $row)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Illuminate\Support\Carbon::parse($row['date'])->format('M d, Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row['academic_year'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row['description'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-red-600">{{ $row['debit'] ? number_format($row['debit'], 2) : '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-green-600">{{ $row['credit'] ? number_format($row['credit'], 2) : '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">{{ number_format($row['balance'], 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row['bank'] ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row['payment_mode'] ? ucwords(str_replace('_', ' ', $row['payment_mode'])) : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-400">No fee transactions recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

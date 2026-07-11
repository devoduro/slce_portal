<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Student Arrears') }}
            </h2>
            <x-button href="{{ route('fees.arrears.upload') }}" icon="fas fa-upload">
                {{ __('Upload Debtors List') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="GET" action="{{ route('fees.arrears.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or index number" class="block w-full pl-3 pr-3 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <select name="academic_year_id" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                <option value="">All Academic Years</option>
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="bg-primary-600 text-white rounded-md px-4 py-2 text-sm hover:bg-primary-700">
                                <i class="fas fa-filter mr-1"></i> Filter
                            </button>
                        </div>
                    </form>

                    @if($arrears->isEmpty())
                        <div class="text-center py-8">
                            <i class="fas fa-file-invoice text-gray-300 text-5xl mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-500">No arrears records found</h3>
                            <p class="text-gray-400 mt-1">Upload a debtors list to get started</p>
                            <div class="mt-6">
                                <x-button href="{{ route('fees.arrears.upload') }}" icon="fas fa-upload">
                                    {{ __('Upload Debtors List') }}
                                </x-button>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Academic Year</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Owed</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($arrears as $arrear)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $arrear->student->full_name ?? 'Unknown' }}</div>
                                                <div class="text-sm text-gray-500">{{ $arrear->student->index_number ?? '-' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $arrear->academicYear->name ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ $arrear->amount < 0 ? 'text-green-600' : 'text-gray-900' }}">
                                                {{ number_format($arrear->amount, 2) }}
                                                @if($arrear->amount < 0)
                                                    <span class="text-xs font-normal text-green-500">(credit - school owes student)</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ $arrear->notes ?? '-' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $arrear->recordedBy->name ?? 'System' }}<br>
                                                <span class="text-xs text-gray-400">{{ $arrear->created_at->format('M d, Y') }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <form action="{{ route('fees.arrears.destroy', $arrear) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Remove this arrears record?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $arrears->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

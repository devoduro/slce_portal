<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Student Fee Charges') }}
            </h2>
            <div class="flex gap-2">
                <x-button href="{{ route('fee-structures.index') }}" variant="secondary" icon="fas fa-arrow-left">
                    {{ __('Back to Fee Structures') }}
                </x-button>
                <x-modal id="manage-categories-modal" title="Manage Fee Categories" maxWidth="lg">
                    <x-slot name="trigger">
                        <span class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 cursor-pointer">
                            <i class="fas fa-tags"></i> Manage Categories
                        </span>
                    </x-slot>

                    <div class="space-y-2 mb-6 max-h-64 overflow-y-auto">
                        @foreach($categories as $cat)
                            <div class="flex items-center justify-between px-3 py-2 bg-gray-50 rounded-lg">
                                <div>
                                    <span class="text-sm font-medium text-gray-900">{{ $cat->name }}</span>
                                    @if($cat->is_protected)
                                        <span class="ml-2 text-xs text-gray-400">(protected)</span>
                                    @endif
                                </div>
                                @unless($cat->is_protected)
                                    <form action="{{ route('fees.categories.destroy', $cat) }}" method="POST" onsubmit="return confirm('Remove the \'{{ $cat->name }}\' category? This only works if no fee structure or charge currently uses it.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 text-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endunless
                            </div>
                        @endforeach
                    </div>

                    <form action="{{ route('fees.categories.store') }}" method="POST" class="flex items-end gap-2">
                        @csrf
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">New Category Name</label>
                            <input type="text" name="name" placeholder="e.g. Library Fee" class="w-full rounded-lg shadow-sm border-gray-300 focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50" required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <x-button type="submit" icon="fas fa-plus">
                            {{ __('Add') }}
                        </x-button>
                    </form>
                </x-modal>
                <x-button href="{{ route('fees.charges.upload') }}" icon="fas fa-upload">
                    {{ __('Upload Fee Charges') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <p class="text-sm text-gray-500 mb-4">Fees billed directly to specific students - graduation fees, resit fees, or any one-off charge - rather than the whole programme/level.</p>

                    <form method="GET" action="{{ route('fees.charges.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
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
                            <select name="category" class="block w-full pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                                <option value="">All Categories</option>
                                @foreach(\App\Models\FeeCategory::options() as $value => $label)
                                    <option value="{{ $value }}" {{ request('category') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 bg-primary-600 text-white rounded-md px-4 py-2 text-sm hover:bg-primary-700">
                                <i class="fas fa-filter mr-1"></i> Filter
                            </button>
                            @if(request()->hasAny(['search', 'academic_year_id', 'category']))
                                <a href="{{ route('fees.charges.index') }}" class="inline-flex items-center px-3 py-2 text-sm text-gray-600 hover:text-primary-600">
                                    Clear
                                </a>
                            @endif
                        </div>
                    </form>

                    @if($charges->isEmpty())
                        <div class="text-center py-8">
                            <i class="fas fa-user-tag text-gray-300 text-5xl mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-500">No fee charges found</h3>
                            <p class="text-gray-400 mt-1">Upload a list to bill specific students, e.g. a resit or graduation fee</p>
                            <div class="mt-6">
                                <x-button href="{{ route('fees.charges.upload') }}" icon="fas fa-upload">
                                    {{ __('Upload Fee Charges') }}
                                </x-button>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Academic Year</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($charges as $charge)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $charge->student->full_name ?? 'Unknown' }}</div>
                                                <div class="text-sm text-gray-500">{{ $charge->student->index_number ?? '-' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ $charge->categoryLabel() }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $charge->academicYear->name ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ number_format($charge->amount, 2) }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ $charge->notes ?? '-' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $charge->recordedBy->name ?? 'System' }}<br>
                                                <span class="text-xs text-gray-400">{{ $charge->created_at->format('M d, Y') }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <form action="{{ route('fees.charges.destroy', $charge) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Remove this fee charge?')">
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
                            {{ $charges->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

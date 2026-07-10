<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Partner Schools') }}
            </h2>
            <div class="flex gap-2">
                <x-button href="{{ route('partner-schools.import.form') }}" variant="secondary" icon="fas fa-upload">
                    {{ __('Import') }}
                </x-button>
                <x-button href="{{ route('partner-schools.create') }}" icon="fas fa-plus">
                    {{ __('Add School') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if(session('success'))
                        <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 text-sm">{{ session('success') }}</div>
                    @endif
                    @if(session('warning'))
                        <div class="mb-4 p-4 bg-amber-50 border-l-4 border-amber-500 text-amber-700 text-sm">{{ session('warning') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm">{{ session('error') }}</div>
                    @endif

                    <form method="GET" class="flex flex-wrap gap-3 mb-4">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or location..." class="rounded-md border-gray-300 shadow-sm text-sm">
                        <select name="category" class="rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="">All Categories</option>
                            @foreach(\App\Models\PartnerSchool::CATEGORY_LABELS as $value => $label)
                                <option value="{{ $value }}" {{ request('category') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-4 py-2 bg-gray-100 rounded-md text-sm text-gray-700 hover:bg-gray-200">Filter</button>
                    </form>

                    @if($schools->isEmpty())
                        <div class="text-center py-8">
                            <i class="fas fa-school text-gray-300 text-5xl mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-500">No partner schools found</h3>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quota (100/200/300/400)</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($schools as $school)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $school->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">{{ $school->categoryLabel() }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $school->location ?? '-' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $school->capacity_level_100 }} / {{ $school->capacity_level_200 }} / {{ $school->capacity_level_300 }} / {{ $school->capacity_level_400 }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex justify-end space-x-2">
                                                    <a href="{{ route('partner-schools.edit', $school) }}" class="text-indigo-600 hover:text-indigo-900">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('partner-schools.destroy', $school) }}" method="POST" class="inline-block">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Delete this partner school?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $schools->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

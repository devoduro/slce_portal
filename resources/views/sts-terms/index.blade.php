<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('STS Terms') }}
            </h2>
            <x-button href="{{ route('sts-terms.create') }}" icon="fas fa-plus">
                {{ __('Add STS Term') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if(session('success'))
                        <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 text-sm">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm">{{ session('error') }}</div>
                    @endif

                    @if($terms->isEmpty())
                        <div class="text-center py-8">
                            <i class="fas fa-school text-gray-300 text-5xl mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-500">No STS terms found</h3>
                            <p class="text-gray-400 mt-1">Get started by adding your first STS term</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Semester</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Window</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Internship Cutoff</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($terms as $term)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $term->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $term->semester->name ?? 'N/A' }} &bull; {{ $term->semester->academicYear->name ?? '' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $term->proposed_start_date->format('M d, Y') }} &ndash; {{ $term->proposed_end_date->format('M d, Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                &ge; Level {{ $term->internship_level_cutoff }} = Internship
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($term->is_current)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Current</span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex justify-end items-center space-x-2">
                                                    @if(!$term->is_current)
                                                        <form action="{{ route('sts-terms.activate', $term) }}" method="POST" class="inline-block">
                                                            @csrf
                                                            @method('PUT')
                                                            <button type="submit" class="text-xs text-primary-600 hover:text-primary-900 underline" onclick="return confirm('Activate this term and seed placements for all eligible students?')">
                                                                Activate
                                                            </button>
                                                        </form>
                                                    @else
                                                        <form action="{{ route('sts-terms.deactivate', $term) }}" method="POST" class="inline-block">
                                                            @csrf
                                                            @method('PUT')
                                                            <button type="submit" class="text-xs text-orange-600 hover:text-orange-900 underline" onclick="return confirm('Deactivate this term? Students will immediately lose access to STS/Internship for this term until it (or another) is activated again.')">
                                                                Deactivate
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <a href="{{ route('sts-terms.edit', $term) }}" class="text-indigo-600 hover:text-indigo-900">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('sts-terms.destroy', $term) }}" method="POST" class="inline-block">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Delete this STS term?')">
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
                            {{ $terms->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

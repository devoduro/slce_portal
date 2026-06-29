<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Grade Scheme') }}
            </h2>
            <div>
                <a href="{{ route('settings.grade-schemes') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-bold rounded-md transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    {{ __('Back to Grade Schemes') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @include('layouts.messages')

                    <form method="POST" action="{{ route('settings.grade-schemes.update', $gradeScheme->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700">Name <span class="text-red-600">*</span></label>
                            <input type="text" name="name" id="name" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('name') border-red-500 @enderror"
                                   value="{{ old('name', $gradeScheme->name) }}" required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" id="description" rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('description') border-red-500 @enderror">{{ old('description', $gradeScheme->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Grade Scale</h3>
                            <div class="space-y-4">
                                @foreach($gradeScheme->grades ?? [] as $index => $grade)
                                    <div class="flex items-center space-x-4">
                                        <div class="w-1/4">
                                            <label for="grades[{{ $index }}][letter]" class="block text-sm font-medium text-gray-700">Letter Grade</label>
                                            <input type="text" name="grades[{{ $index }}][letter]" id="grades[{{ $index }}][letter]"
                                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                   value="{{ old("grades.$index.letter", $grade['letter']) }}" required>
                                        </div>
                                        <div class="w-1/4">
                                            <label for="grades[{{ $index }}][min_score]" class="block text-sm font-medium text-gray-700">Min Score</label>
                                            <input type="number" name="grades[{{ $index }}][min_score]" id="grades[{{ $index }}][min_score]"
                                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                   value="{{ old("grades.$index.min_score", $grade['min_score']) }}" min="0" max="100" step="0.01" required>
                                        </div>
                                        <div class="w-1/4">
                                            <label for="grades[{{ $index }}][max_score]" class="block text-sm font-medium text-gray-700">Max Score</label>
                                            <input type="number" name="grades[{{ $index }}][max_score]" id="grades[{{ $index }}][max_score]"
                                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                   value="{{ old("grades.$index.max_score", $grade['max_score']) }}" min="0" max="100" step="0.01" required>
                                        </div>
                                        <div class="w-1/4">
                                            <label for="grades[{{ $index }}][gpa]" class="block text-sm font-medium text-gray-700">GPA</label>
                                            <input type="number" name="grades[{{ $index }}][gpa]" id="grades[{{ $index }}][gpa]"
                                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                   value="{{ old("grades.$index.gpa", $grade['gpa']) }}" min="0" max="4" step="0.01" required>
                                        </div>
                                        @if($index > 0)
                                            <button type="button" onclick="this.parentElement.remove()" class="mt-6 text-red-600 hover:text-red-800">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" onclick="addGradeRow()" class="mt-4 inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-plus mr-2"></i> Add Grade
                            </button>
                        </div>

                        <div class="mt-6 flex items-center justify-end space-x-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-md transition-colors">
                                <i class="fas fa-save mr-2"></i>
                                {{ __('Update Grade Scheme') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function addGradeRow() {
            const container = document.querySelector('.space-y-4');
            const index = container.children.length;
            
            const template = `
                <div class="flex items-center space-x-4">
                    <div class="w-1/4">
                        <label for="grades[${index}][letter]" class="block text-sm font-medium text-gray-700">Letter Grade</label>
                        <input type="text" name="grades[${index}][letter]" id="grades[${index}][letter]"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                               required>
                    </div>
                    <div class="w-1/4">
                        <label for="grades[${index}][min_score]" class="block text-sm font-medium text-gray-700">Min Score</label>
                        <input type="number" name="grades[${index}][min_score]" id="grades[${index}][min_score]"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                               min="0" max="100" step="0.01" required>
                    </div>
                    <div class="w-1/4">
                        <label for="grades[${index}][max_score]" class="block text-sm font-medium text-gray-700">Max Score</label>
                        <input type="number" name="grades[${index}][max_score]" id="grades[${index}][max_score]"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                               min="0" max="100" step="0.01" required>
                    </div>
                    <div class="w-1/4">
                        <label for="grades[${index}][gpa]" class="block text-sm font-medium text-gray-700">GPA</label>
                        <input type="number" name="grades[${index}][gpa]" id="grades[${index}][gpa]"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                               min="0" max="4" step="0.01" required>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="mt-6 text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', template);
        }
    </script>
    @endpush
</x-app-layout>

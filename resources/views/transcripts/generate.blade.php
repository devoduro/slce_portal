<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Generate Transcript') }}
            </h2>
            <x-button href="{{ route('students.show', $student) }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to Student') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Student Info -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-16 w-16">
                            <img class="h-16 w-16 rounded-full object-cover" src="{{ $student->profile_photo ?? 'https://ui-avatars.com/api/?name='.urlencode($student->name).'&color=7F9CF5&background=EBF4FF' }}" alt="{{ $student->name }}">
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">{{ $student->name }}</h3>
                            <div class="flex flex-col sm:flex-row sm:space-x-6 text-sm text-gray-500">
                                <p><span class="font-medium">ID:</span> {{ $student->student_id }}</p>
                                <p><span class="font-medium">Programme:</span> {{ $student->programme }}</p>
                                <p><span class="font-medium">Year:</span> {{ $student->year }}</p>
                                <p><span class="font-medium">GPA:</span> {{ number_format($student->gpa ?? 0, 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transcript Options -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Transcript Options</h3>
                    
                    <form method="POST" action="{{ route('transcripts.preview', $student) }}">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left Column -->
                            <div class="space-y-6">
                                <!-- Transcript Type -->
                                <div>
                                    <label for="type" class="block text-sm font-medium text-gray-700">Transcript Type</label>
                                    <select id="type" name="type" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                                        <option value="official">Official Transcript</option>
                                        <option value="unofficial">Unofficial Transcript</option>
                                        <option value="progress">Progress Report</option>
                                    </select>
                                    @error('type')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Academic Period -->
                                <div>
                                    <label for="academic_period" class="block text-sm font-medium text-gray-700">Academic Period</label>
                                    <select id="academic_period" name="academic_period" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                                        <option value="all">All Periods</option>
                                        <option value="Fall 2023">Fall 2023</option>
                                        <option value="Spring 2023">Spring 2023</option>
                                        <option value="Fall 2022">Fall 2022</option>
                                        <option value="Spring 2022">Spring 2022</option>
                                    </select>
                                    @error('academic_period')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Include Comments -->
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="include_comments" name="include_comments" type="checkbox" class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="include_comments" class="font-medium text-gray-700">Include Comments</label>
                                        <p class="text-gray-500">Include instructor comments for each course</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-6">
                                <!-- Format -->
                                <div>
                                    <label for="format" class="block text-sm font-medium text-gray-700">Format</label>
                                    <select id="format" name="format" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                                        <option value="pdf">PDF</option>
                                        <option value="docx">Word Document</option>
                                    </select>
                                    @error('format')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Paper Size -->
                                <div>
                                    <label for="paper_size" class="block text-sm font-medium text-gray-700">Paper Size</label>
                                    <select id="paper_size" name="paper_size" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                                        <option value="a4">A4</option>
                                        <option value="letter">Letter</option>
                                        <option value="legal">Legal</option>
                                    </select>
                                    @error('paper_size')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Include Signature -->
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="include_signature" name="include_signature" type="checkbox" class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded" checked>
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="include_signature" class="font-medium text-gray-700">Include Digital Signature</label>
                                        <p class="text-gray-500">Add registrar's digital signature to the transcript</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Notes -->
                        <div class="mt-6">
                            <label for="notes" class="block text-sm font-medium text-gray-700">Additional Notes</label>
                            <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm" placeholder="Any special instructions or notes to include"></textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <x-button type="submit" name="action" value="preview" variant="secondary" icon="fas fa-eye">
                                {{ __('Preview') }}
                            </x-button>
                            <x-button type="submit" name="action" value="generate" icon="fas fa-file-download">
                                {{ __('Generate Transcript') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Recent Transcripts -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Transcripts</h3>
                    
                    @if(count($recentTranscripts ?? []) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date Generated
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Academic Period
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Format
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Generated By
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($recentTranscripts ?? [] as $transcript)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $transcript->created_at->format('M d, Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ ucfirst($transcript->type) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $transcript->academic_period }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            {{ strtoupper($transcript->format) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $transcript->generated_by }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <a href="{{ route('transcripts.download', $transcript) }}" class="text-primary-600 hover:text-primary-900" title="Download">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <a href="{{ route('transcripts.view', $transcript) }}" class="text-indigo-600 hover:text-indigo-900" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('transcripts.email', $transcript) }}" class="text-green-600 hover:text-green-900" title="Email">
                                                <i class="fas fa-envelope"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="bg-gray-50 p-4 rounded-lg text-center">
                        <p class="text-sm text-gray-500">No transcripts have been generated for this student yet.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Bulk SMS') }}
            </h2>
            <div class="flex space-x-2">
                <x-button href="{{ route('sms.history') }}" variant="secondary" icon="fas fa-history">
                    {{ __('Sent Messages') }}
                </x-button>
                <x-button href="{{ route('sms.templates.index') }}" variant="secondary" icon="fas fa-file-alt">
                    {{ __('Templates') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <ul class="mb-0 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Filter + Compose Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="GET" action="{{ route('sms.index') }}" class="mb-6">
                        <h3 class="text-lg font-medium text-gray-800 mb-3">Filter Recipients</h3>
                        <div class="flex flex-col md:flex-row gap-4">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, ID, phone..."
                                   class="flex-1 border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500">

                            <select name="programme_id" class="border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <option value="">All Programmes</option>
                                @foreach($programmes as $programme)
                                    <option value="{{ $programme->id }}" {{ request('programme_id') == $programme->id ? 'selected' : '' }}>
                                        {{ $programme->name }}
                                    </option>
                                @endforeach
                            </select>

                            <select name="gender" class="border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <option value="">All Genders</option>
                                <option value="Male" {{ request('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ request('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                                <option value="Other" {{ request('gender') == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>

                            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700">
                                <i class="fas fa-filter mr-2"></i>Apply
                            </button>

                            @if(request('search') || request('programme_id') || request('gender'))
                                <a href="{{ route('sms.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                                    <i class="fas fa-times mr-2"></i>Clear
                                </a>
                            @endif
                        </div>
                    </form>

                    <div class="bg-blue-50 border-l-4 border-blue-400 p-3 mb-6">
                        <p class="text-sm text-blue-700">
                            <i class="fas fa-users mr-1"></i>
                            <span class="font-semibold">{{ $recipientCount }}</span> student(s) with a phone number match the current filters. The message below will be sent to exactly these recipients.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('sms.send') }}" id="smsForm">
                        @csrf
                        <input type="hidden" name="programme_id" value="{{ request('programme_id') }}">
                        <input type="hidden" name="gender" value="{{ request('gender') }}">
                        <input type="hidden" name="search" value="{{ request('search') }}">

                        <div class="mb-4">
                            <label for="template_select" class="block text-sm font-medium text-gray-700 mb-2">Use a Template (optional)</label>
                            <select id="template_select" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <option value="">-- Select a template --</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->message }}">{{ $template->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-2">
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                            <textarea id="message" name="message" rows="5" required maxlength="459"
                                      class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500"
                                      placeholder="Type your SMS message here...">{{ old('message') }}</textarea>
                        </div>
                        <p class="text-xs text-gray-500 mb-4"><span id="charCount">0</span>/459 characters</p>

                        <div class="flex justify-end">
                            <button type="submit" id="sendBtn"
                                    onclick="return confirm('Send this SMS to {{ $recipientCount }} student(s)?')"
                                    class="px-6 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 disabled:opacity-50"
                                    {{ $recipientCount === 0 ? 'disabled' : '' }}>
                                <i class="fas fa-paper-plane mr-2"></i>Send to {{ $recipientCount }} Student(s)
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Recipient Preview -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Matching Students (Preview)</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Index Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Programme</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($students as $student)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $student->index_number }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $student->full_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->phone ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->programme->name ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No students match the current filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        @if($students->hasPages())
                            {{ $students->links() }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const messageField = document.getElementById('message');
        const charCount = document.getElementById('charCount');
        const templateSelect = document.getElementById('template_select');

        function updateCharCount() {
            charCount.textContent = messageField.value.length;
        }

        messageField.addEventListener('input', updateCharCount);
        updateCharCount();

        templateSelect.addEventListener('change', function () {
            if (this.value) {
                messageField.value = this.value;
                updateCharCount();
            }
        });
    </script>
    @endpush
</x-app-layout>

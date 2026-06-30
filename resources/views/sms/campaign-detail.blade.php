<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('SMS Campaign Details') }}
            </h2>
            <x-button href="{{ route('sms.history') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to Sent Messages') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-800 mb-2">Message</h3>
                    <p class="text-gray-700 bg-gray-50 p-4 rounded-md">{{ $campaign->message }}</p>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                        <div class="bg-blue-50 p-4 rounded-md text-center">
                            <p class="text-2xl font-bold text-blue-700">{{ $campaign->total_recipients }}</p>
                            <p class="text-sm text-blue-600">Total Recipients</p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-md text-center">
                            <p class="text-2xl font-bold text-green-700">{{ $campaign->success_count }}</p>
                            <p class="text-sm text-green-600">Successful</p>
                        </div>
                        <div class="bg-red-50 p-4 rounded-md text-center">
                            <p class="text-2xl font-bold text-red-700">{{ $campaign->failed_count }}</p>
                            <p class="text-sm text-red-600">Failed</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-md text-center">
                            <p class="text-sm font-semibold text-gray-700">{{ $campaign->created_at->format('Y-m-d H:i') }}</p>
                            <p class="text-sm text-gray-500">Sent By {{ $campaign->sender->name ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Delivery Log</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Response</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($logs as $log)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $log->student->full_name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $log->phone }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            @if($log->success)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Sent</span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ $log->response_message }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No delivery logs found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        @if($logs->hasPages())
                            {{ $logs->links() }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

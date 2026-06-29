<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Database Backup Settings') }}
            </h2>
            <div class="flex space-x-4">
                <form action="{{ route('settings.backup.create') }}" method="POST" class="inline-block">
                    @csrf
                    @method('POST')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-md transition-colors duration-150 ease-in-out shadow-sm">
                        <i class="fas fa-database mr-2"></i>
                        {{ __('Create New Backup') }}
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Status Messages -->
            @include('layouts.messages')

            <!-- Main Content -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Quick Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="text-blue-600 text-sm font-medium uppercase tracking-wide mb-1">Total Backups</div>
                            <div class="text-2xl font-bold text-blue-800">{{ count($backups) }}</div>
                        </div>
                        @if(count($backups) > 0)
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="text-green-600 text-sm font-medium uppercase tracking-wide mb-1">Latest Backup</div>
                            <div class="text-2xl font-bold text-green-800">{{ date('Y-m-d H:i', $backups[0]['created_at']) }}</div>
                        </div>
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <div class="text-purple-600 text-sm font-medium uppercase tracking-wide mb-1">Total Size</div>
                            <div class="text-2xl font-bold text-purple-800">
                                @php
                                    $totalSize = array_reduce($backups, function($carry, $backup) {
                                        return $carry + $backup['size'];
                                    }, 0);
                                    echo $totalSize > 1024 * 1024 
                                        ? round($totalSize / (1024 * 1024), 2) . ' MB'
                                        : round($totalSize / 1024, 2) . ' KB';
                                @endphp
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Backup Information -->
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Backup Management</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Database backups are essential for data protection. Regular backups help prevent data loss in case of system failures or other issues.
                            Create, download, and manage your backups below.
                        </p>
                    </div>

                    {{-- Debug Information --}}
                    @if(isset($debug))
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Debug Information</h3>
                        <div class="text-sm text-gray-600">
                            <p><strong>Backup Path:</strong> {{ $debug['backup_path'] }}</p>
                            <p><strong>Backup Count:</strong> {{ $debug['backup_count'] }}</p>
                            <p><strong>Directory Exists:</strong> {{ $debug['directory_exists'] ? 'Yes' : 'No' }}</p>
                            <p><strong>Directory Writable:</strong> {{ $debug['directory_writable'] ? 'Yes' : 'No' }}</p>
                            <p><strong>Directory Readable:</strong> {{ $debug['directory_readable'] ? 'Yes' : 'No' }}</p>
                            <p><strong>Directory Permissions:</strong> {{ $debug['directory_permissions'] }}</p>
                            <p><strong>Found Files:</strong> {{ $debug['found_files'] }}</p>
                            <p><strong>Raw Files:</strong> {{ $debug['raw_files'] }}</p>
                            <p><strong>PHP User:</strong> {{ $debug['php_user'] }}</p>
                            <p><strong>Storage Path:</strong> {{ $debug['storage_path'] }}</p>
                            <p><strong>Public Path:</strong> {{ $debug['public_path'] }}</p>
                            @if(isset($debug['error']))
                            <p class="text-red-600"><strong>Error:</strong> {{ $debug['error'] }}</p>
                            @endif
                            @if(isset($debug['trace']))
                            <pre class="mt-2 p-2 bg-gray-100 rounded text-xs overflow-auto">{{ $debug['trace'] }}</pre>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if(count($backups) > 0)
                        <div class="overflow-x-auto bg-gray-50 rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Filename</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Size</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Created At</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($backups as $backup)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $backup['filename'] }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-500">
                                                    @php
                                                        $size = $backup['size'];
                                                        if ($size < 1024) {
                                                            echo $size . ' B';
                                                        } elseif ($size < 1024 * 1024) {
                                                            echo round($size / 1024, 2) . ' KB';
                                                        } else {
                                                            echo round($size / (1024 * 1024), 2) . ' MB';
                                                        }
                                                    @endphp
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-500">
                                                    {{ date('Y-m-d H:i:s', $backup['created_at']) }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex justify-end space-x-4">
                                                    <a href="{{ route('settings.backup.download', $backup['filename']) }}" 
                                                       class="inline-flex items-center px-3 py-2 bg-blue-100 text-blue-700 hover:bg-blue-600 hover:text-white rounded-md transition-all duration-150 ease-in-out shadow-sm">
                                                        <i class="fas fa-download mr-2"></i> Download
                                                    </a>
                                                    <form action="{{ route('settings.backup.destroy', $backup['filename']) }}" method="POST" class="inline-block">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                class="inline-flex items-center px-3 py-2 bg-red-100 text-red-700 hover:bg-red-600 hover:text-white rounded-md transition-all duration-150 ease-in-out shadow-sm"
                                                                onclick="return confirm('Are you sure you want to delete this backup? This action cannot be undone.')">
                                                            <i class="fas fa-trash mr-2"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                            <i class="fas fa-database text-4xl text-gray-400 mb-3"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No Backups Available</h3>
                            <p class="text-sm text-gray-600 mb-4">Create your first database backup to protect your data.</p>
                            <form action="{{ route('settings.backup.create') }}" method="POST" class="inline-block">
                                @csrf
                                @method('POST')
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors duration-150 ease-in-out shadow-sm">
                                    <i class="fas fa-plus mr-2"></i>
                                    {{ __('Create First Backup') }}
                                </button>
                            </form>
                        </div>
                    @endif

                    <div class="mt-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Backup Best Practices</h3>
                        <ul class="list-disc pl-5 text-sm text-gray-600 space-y-1">
                            <li>Create regular backups, especially before major system changes</li>
                            <li>Download and store backups in multiple secure locations</li>
                            <li>Test backup restoration periodically to ensure data integrity</li>
                            <li>Keep at least 3 recent backups at all times</li>
                            <li>Delete old backups to save storage space, but maintain a good retention policy</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

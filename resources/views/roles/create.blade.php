<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Add Role') }}
            </h2>
            <x-button href="{{ route('roles.index') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to Roles') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('roles.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input
                                id="name"
                                name="name"
                                type="text"
                                label="Role Name"
                                :value="old('name')"
                                required
                                autofocus
                                placeholder="e.g. Exams Officer"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Permissions</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                @foreach($permissions as $permission)
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                            class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                                            {{ in_array($permission->name, old('permissions', [])) ? 'checked' : '' }}>
                                        <span class="text-sm text-gray-700">{{ \Illuminate\Support\Str::headline($permission->name) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('permissions')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <x-button type="button" variant="secondary" onclick="window.history.back()">
                                {{ __('Cancel') }}
                            </x-button>
                            <x-button type="submit">
                                {{ __('Create Role') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

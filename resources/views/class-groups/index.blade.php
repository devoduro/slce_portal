<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Classes') }}
            </h2>
            <div class="flex gap-2">
                <x-button href="{{ route('class-groups.import.form') }}" variant="secondary" icon="fas fa-file-import">
                    {{ __('Import Class List') }}
                </x-button>
                <x-button href="{{ route('class-groups.create') }}" icon="fas fa-plus">
                    {{ __('Add Class') }}
                </x-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if($classGroups->isEmpty())
                        <div class="text-center py-8">
                            <i class="fas fa-users text-gray-300 text-5xl mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-500">No classes found</h3>
                            <p class="text-gray-400 mt-1">Create a class to group students within a level for timetabling and attendance</p>
                            <div class="mt-6">
                                <x-button href="{{ route('class-groups.create') }}" icon="fas fa-plus">
                                    {{ __('Add Class') }}
                                </x-button>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Programme</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Students</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($classGroups as $classGroup)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $classGroup->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $classGroup->programme->name ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $classGroup->level }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $classGroup->students_count }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex justify-end space-x-2">
                                                    <a href="{{ route('class-groups.print', $classGroup) }}" target="_blank" class="text-gray-600 hover:text-gray-900" title="Print Class List">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                    @can('manage-timetable')
                                                        @if($currentSemesterId)
                                                            <a href="{{ route('timetable.print', ['class_group_id' => $classGroup->id, 'semester_id' => $currentSemesterId]) }}" target="_blank" class="text-gray-600 hover:text-gray-900" title="Print Timetable">
                                                                <i class="fas fa-calendar-alt"></i>
                                                            </a>
                                                        @else
                                                            <span class="text-gray-300 cursor-not-allowed" title="Set a current semester first">
                                                                <i class="fas fa-calendar-alt"></i>
                                                            </span>
                                                        @endif
                                                    @endcan
                                                    <a href="{{ route('class-groups.assign-students', $classGroup) }}" class="text-primary-600 hover:text-primary-900" title="Assign Students">
                                                        <i class="fas fa-user-plus"></i>
                                                    </a>
                                                    <a href="{{ route('class-groups.edit', $classGroup) }}" class="text-indigo-600 hover:text-indigo-900">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('class-groups.destroy', $classGroup) }}" method="POST" class="inline-block">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this class?')">
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
                            {{ $classGroups->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

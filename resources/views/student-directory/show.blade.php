<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Student Details') }}
            </h2>
            <x-button href="{{ route('student-directory.index') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back to Directory') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center gap-4 mb-6">
                    <x-student-photo :student="$student" class="w-20 h-20 rounded-full object-cover border border-gray-200" />
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $student->full_name }}</h3>
                        <p class="text-sm text-gray-500">{{ $student->index_number }} @if($student->reference_number) &bull; Ref: {{ $student->reference_number }} @endif</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">Academic Placement</h4>
                        <dl class="space-y-2 text-sm">
                            <div><dt class="text-gray-500 inline">Programme:</dt> <dd class="inline font-medium text-gray-900">{{ $student->programme->name ?? 'N/A' }}</dd></div>
                            <div><dt class="text-gray-500 inline">Level:</dt> <dd class="inline font-medium text-gray-900">{{ $student->levelLabel() }}</dd></div>
                            <div><dt class="text-gray-500 inline">Class:</dt> <dd class="inline font-medium text-gray-900">{{ $student->classGroup->name ?? '-' }}</dd></div>
                            <div><dt class="text-gray-500 inline">Hall:</dt> <dd class="inline font-medium text-gray-900">{{ $student->hall ?? '-' }}</dd></div>
                            <div><dt class="text-gray-500 inline">Status:</dt> <dd class="inline font-medium text-gray-900">{{ ucfirst($student->status ?? '-') }}</dd></div>
                        </dl>
                    </div>

                    <div>
                        <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">Personal Details</h4>
                        <dl class="space-y-2 text-sm">
                            <div><dt class="text-gray-500 inline">Gender:</dt> <dd class="inline font-medium text-gray-900">{{ $student->gender ?? '-' }}</dd></div>
                            <div><dt class="text-gray-500 inline">Date of Birth:</dt> <dd class="inline font-medium text-gray-900">{{ $student->date_of_birth ? \Illuminate\Support\Carbon::parse($student->date_of_birth)->format('M d, Y') : '-' }}</dd></div>
                            <div><dt class="text-gray-500 inline">Hometown:</dt> <dd class="inline font-medium text-gray-900">{{ $student->hometown ?? '-' }}</dd></div>
                            <div><dt class="text-gray-500 inline">Address:</dt> <dd class="inline font-medium text-gray-900">{{ $student->address ?? '-' }}</dd></div>
                            <div><dt class="text-gray-500 inline">GPS Address:</dt> <dd class="inline font-medium text-gray-900">{{ $student->gps_address ?? '-' }}</dd></div>
                        </dl>
                    </div>

                    <div>
                        <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">Contact Details</h4>
                        <dl class="space-y-2 text-sm">
                            <div><dt class="text-gray-500 inline">Phone:</dt> <dd class="inline font-medium text-gray-900">{{ $student->phone ?? '-' }}</dd></div>
                            <div><dt class="text-gray-500 inline">Email:</dt> <dd class="inline font-medium text-gray-900">{{ $student->email ?? '-' }}</dd></div>
                        </dl>

                        <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3 mt-5">Emergency Contact</h4>
                        <dl class="space-y-2 text-sm">
                            <div><dt class="text-gray-500 inline">Name:</dt> <dd class="inline font-medium text-gray-900">{{ $student->emergency_contact_name ?? '-' }}</dd></div>
                            <div><dt class="text-gray-500 inline">Phone:</dt> <dd class="inline font-medium text-gray-900">{{ $student->emergency_contact_phone ?? '-' }}</dd></div>
                            <div><dt class="text-gray-500 inline">Relationship:</dt> <dd class="inline font-medium text-gray-900">{{ $student->emergency_contact_relationship ?? '-' }}</dd></div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

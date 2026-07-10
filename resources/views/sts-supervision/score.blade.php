<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Score') }} &mdash; {{ $stsPlacement->student->full_name ?? '' }}
            </h2>
            <x-button href="{{ route('sts-supervision.index') }}" variant="secondary" icon="fas fa-arrow-left">
                {{ __('Back') }}
            </x-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if($errors->any())
                        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 text-sm">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
                        <div><span class="text-gray-500">Level:</span> <span class="font-medium">{{ $stsPlacement->level }}</span></div>
                        <div><span class="text-gray-500">Type:</span> <span class="font-medium">{{ ucfirst($stsPlacement->type) }}</span></div>
                        <div><span class="text-gray-500">Partner School:</span> <span class="font-medium">{{ $stsPlacement->partnerSchool->name ?? 'Not selected yet' }}</span></div>
                    </div>

                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-500">Attendance (Mentor) Score &mdash; computed automatically from biometric attendance</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $attendanceScore }} @if($setting) / {{ $setting->attendance_max }} @endif</p>
                    </div>

                    @if(!$setting)
                        <div class="mb-6 p-4 bg-amber-50 border-l-4 border-amber-400 text-amber-700 text-sm">
                            No STS score setting has been configured for Level {{ $stsPlacement->level }} yet. Ask the STS Coordinator to add one before scoring.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('sts-supervision.score.store', $stsPlacement) }}" class="space-y-6">
                        @csrf

                        <x-input id="project_score" name="scores[project]" type="number" step="0.01" min="0" :max="$setting?->project_max" label="Project Work Score" :value="old('scores.project', $ca->project_score ?? '')" :helper="$setting ? 'Max: ' . $setting->project_max : null" />

                        <x-input id="assignment_score" name="scores[assignment]" type="number" step="0.01" min="0" :max="$setting?->assignment_max" label="Portfolio Development Score" :value="old('scores.assignment', $ca->assignment_score ?? '')" :helper="$setting ? 'Max: ' . $setting->assignment_max : null" />

                        <x-input id="mid_semester_score" name="scores[mid_semester]" type="number" step="0.01" min="0" :max="$setting?->mid_semester_max" label="Supervisor Visit / Mid-Term Score" :value="old('scores.mid_semester', $ca->mid_semester_score ?? '')" :helper="$setting ? 'Max: ' . $setting->mid_semester_max : null" />

                        <div class="mt-6 flex justify-end space-x-3">
                            <x-button type="button" variant="secondary" onclick="window.history.back()">
                                {{ __('Cancel') }}
                            </x-button>
                            <x-button type="submit">
                                {{ __('Save Scores') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

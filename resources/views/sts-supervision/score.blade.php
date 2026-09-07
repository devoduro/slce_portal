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
                        <div><span class="text-gray-500">Marked out of:</span> <span class="font-medium">{{ number_format((float) $criteria->sum('max_mark'), 2) }}</span></div>
                    </div>

                    @if($criteria->isEmpty())
                        <div class="p-4 bg-amber-50 border-l-4 border-amber-400 text-amber-700 text-sm">
                            No score sheet has been set up for Level {{ $stsPlacement->level }} yet.
                            Ask the STS Coordinator to define its scoring criteria before marking.
                        </div>
                    @else
                        <form method="POST" action="{{ route('sts-supervision.score.store', $stsPlacement) }}"
                              x-data="{
                                  marks: {{ Js::from($criteria->mapWithKeys(fn ($c) => [$c->id => old('scores.' . $c->id, optional($existing->get($c->id))->score)])->all()) }},
                                  get awarded() {
                                      return Object.values(this.marks).reduce((sum, v) => sum + (parseFloat(v) || 0), 0);
                                  }
                              }">
                            @csrf

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Criterion</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-28">Max</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-40">Mark</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($criteria as $criterion)
                                            <tr>
                                                <td class="px-4 py-3 text-sm text-gray-800">{{ $criterion->label }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-500 text-right">{{ number_format((float) $criterion->max_mark, 2) }}</td>
                                                <td class="px-4 py-3">
                                                    <input type="number"
                                                           name="scores[{{ $criterion->id }}]"
                                                           x-model="marks[{{ $criterion->id }}]"
                                                           step="0.01" min="0" max="{{ $criterion->max_mark }}"
                                                           placeholder="—"
                                                           class="block w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 {{ $errors->has('scores.' . $criterion->id) ? 'border-red-400' : 'border-gray-300' }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-gray-50">
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-700">Total</td>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-700 text-right">{{ number_format((float) $criteria->sum('max_mark'), 2) }}</td>
                                            <td class="px-4 py-3 text-sm font-bold text-gray-900" x-text="awarded.toFixed(2)"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <p class="mt-3 text-xs text-gray-500">
                                Leave a mark blank if you have not assessed it yet — partly completed sheets save fine.
                            </p>

                            <div class="mt-6 flex justify-end space-x-3">
                                <x-button href="{{ route('sts-supervision.index') }}" variant="secondary">
                                    {{ __('Cancel') }}
                                </x-button>
                                <x-button type="submit">
                                    {{ __('Save Scores') }}
                                </x-button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

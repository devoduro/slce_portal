@php
    $showActions = $showActions ?? false;
    $courses = $entries->pluck('course')->filter()->unique('id')->sortBy('code');
@endphp

@if($entries->isEmpty())
    <div class="text-center py-8 text-gray-400">
        No timetable entries found.
    </div>
@else
    <div class="timetable-grid-wrapper overflow-x-auto pb-4">
        <div class="timetable-grid grid gap-px bg-gray-200 border border-gray-300 rounded-lg overflow-hidden text-[11px] shadow-sm"
             style="grid-template-columns: var(--tt-time-col, 64px) repeat(7, minmax(var(--tt-day-col, 160px), 1fr)); grid-template-rows: var(--tt-header-h, 48px) repeat({{ count($slotLabels) }}, var(--tt-slot-h, 34px));">

            <div class="bg-gradient-to-br from-primary-600 to-primary-700"></div>
            @foreach(\App\Http\Controllers\TimetableController::GRID_DAY_ORDER as $day)
                <div class="bg-gradient-to-br from-primary-600 to-primary-700 flex items-center justify-center font-bold text-white uppercase tracking-wide text-xs" style="grid-column: {{ $loop->index + 2 }}; grid-row: 1;">
                    {{ \App\Http\Controllers\TimetableController::DAYS[$day] }}
                </div>
            @endforeach

            @foreach($slotLabels as $i => $label)
                @php $stripe = intdiv($i, 2) % 2 === 0; @endphp
                <div class="{{ $stripe ? 'bg-gray-50' : 'bg-white' }} text-right pr-1 text-gray-400 font-medium" style="grid-column: 1; grid-row: {{ $i + 2 }};">
                    {{ str_ends_with($label, ':00') ? $label : '' }}
                </div>
                @for($col = 0; $col < 7; $col++)
                    <div class="{{ $stripe ? 'bg-gray-50' : 'bg-white' }}" style="grid-column: {{ $col + 2 }}; grid-row: {{ $i + 2 }};"></div>
                @endfor
            @endforeach

            @foreach($entries as $entry)
                @php $color = \App\Http\Controllers\TimetableController::colorForCourse($entry->course_id); @endphp
                <div class="tt-card relative {{ $color['bg'] }} text-white rounded-lg m-0.5 px-2 py-1.5 shadow-md overflow-hidden group ring-1 ring-black/10"
                     style="grid-column: {{ $entry->grid_column }}; grid-row: {{ $entry->grid_row_start }} / {{ $entry->grid_row_end }}; z-index: 10;"
                     title="{{ $entry->course->title ?? '' }} — {{ $entry->classGroup->name ?? 'No class' }} — {{ $entry->lecturer->name ?? 'Unassigned lecturer' }} — {{ $entry->is_virtual ? 'Virtual/Online' : ($entry->venue->name ?? 'No venue') }}">
                    @if($showActions)
                        <div class="absolute top-0.5 right-0.5 flex gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity print:hidden bg-black/20 rounded px-1">
                            <a href="{{ route('timetable.edit', $entry) }}" class="text-white/90 hover:text-white"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('timetable.destroy', $entry) }}" method="POST" onsubmit="return confirm('Delete this timetable entry?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-white/90 hover:text-white"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    @endif
                    <p class="font-bold truncate leading-tight">
                        {{ $entry->course->code ?? 'N/A' }}
                        @if($entry->classGroup)
                            <span class="font-normal opacity-80">&middot; {{ $entry->classGroup->name }}</span>
                        @endif
                    </p>
                    <p class="truncate opacity-90 leading-tight">{{ substr($entry->start_time, 0, 5) }}&ndash;{{ substr($entry->end_time, 0, 5) }}</p>
                    <p class="truncate opacity-90 leading-tight flex items-center gap-1">
                        @if($entry->is_virtual)
                            <span class="inline-flex items-center gap-1 rounded bg-white/25 px-1.5 py-0 text-[10px] font-semibold uppercase tracking-wide"><i class="fas fa-laptop"></i> Online</span>
                        @else
                            <span>{{ $entry->venue->name ?? 'N/A' }}</span>
                        @endif
                        @if($entry->lecturer)
                            <span class="inline-flex items-center justify-center rounded-full bg-white/25 px-1.5 py-0 text-[10px] font-semibold">{{ $entry->lecturer->initials }}</span>
                        @endif
                    </p>
                </div>
            @endforeach
        </div>
    </div>

    @if($courses->isNotEmpty())
        <div class="tt-legend mt-4 flex flex-wrap gap-2">
            @foreach($courses as $course)
                @php $color = \App\Http\Controllers\TimetableController::colorForCourse($course->id); @endphp
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $color['chip'] }}">
                    <span class="w-2 h-2 rounded-full {{ $color['bg'] }}"></span>
                    {{ $course->code }} &mdash; {{ $course->title }}
                </span>
            @endforeach
        </div>
    @endif
@endif

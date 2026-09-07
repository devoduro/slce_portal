@props(['label', 'icon' => 'fa-folder', 'active' => false])

{{--
    Collapsible sidebar group, opened by default when one of its children is the current page.
    Structure is load-bearing for the collapsed-sidebar CSS: it hides `nav button > span > span`
    and the chevron, and hides the `.pl-8` child list outright.
--}}
<div x-data="{ open: {{ $active ? 'true' : 'false' }} }">
    <button @click="open = !open"
            class="w-full flex items-center justify-between gap-3 px-4 py-3 transition-all duration-200 rounded-lg {{ $active ? 'bg-slate-800 text-white font-medium' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
        <span class="flex items-center gap-3">
            <i class="fas {{ $icon }} w-5 {{ $active ? 'text-primary-400' : 'text-slate-400' }}"></i>
            <span>{{ $label }}</span>
        </span>
        <i class="fas fa-chevron-down text-xs transition-transform" :class="open ? 'rotate-180' : ''"></i>
    </button>
    <div x-show="open" x-transition class="pl-8 space-y-1">
        {{ $slot }}
    </div>
</div>

@props(['href', 'icon' => 'fa-circle', 'active' => false])

{{--
    Top-level sidebar link. The icon/span structure matters: the collapsed-sidebar CSS hides
    `nav a > span` to leave an icon-only rail, so the label must stay a direct child span.
--}}
<a href="{{ $href }}"
   class="flex items-center gap-3 px-4 py-3 transition-all duration-200 rounded-lg {{ $active ? 'bg-primary-600 text-white font-medium shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
    <i class="fas {{ $icon }} w-5 {{ $active ? '' : 'text-slate-400' }}"></i>
    <span>{{ $slot }}</span>
</a>

@props(['href', 'active' => false])

{{-- A child link inside an <x-nav-group>. --}}
<a href="{{ $href }}"
   class="block px-4 py-2 text-sm rounded-lg transition-colors {{ $active ? 'bg-slate-800 text-primary-400 font-medium' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
    {{ $slot }}
</a>

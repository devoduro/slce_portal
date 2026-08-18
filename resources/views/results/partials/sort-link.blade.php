@php
    $isActive = request('sort') === $field;
    $nextDirection = $isActive && request('direction') !== 'desc' ? 'desc' : 'asc';
    $params = array_merge(request()->except(['sort', 'direction', 'page']), [
        'sort' => $field,
        'direction' => $nextDirection,
    ]);
@endphp
<a href="{{ route('results.index', $params) }}" class="inline-flex items-center gap-1 hover:text-gray-700">
    {{ $label }}
    @if($isActive)
        <i class="fas fa-sort-{{ request('direction') === 'desc' ? 'down' : 'up' }} text-gray-600"></i>
    @else
        <i class="fas fa-sort text-gray-300"></i>
    @endif
</a>

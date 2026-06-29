@props([
    'title' => null,
    'subtitle' => null,
    'footer' => null,
    'padding' => 'p-6',
    'hover' => false,
    'gradient' => false,
    'borderLeft' => false,
    'borderLeftColor' => 'primary',
])

@php
    $baseClasses = 'bg-white rounded-lg shadow-sm border border-gray-200';
    $hoverClasses = $hover ? 'transition-shadow hover:shadow-md' : '';
    $gradientClasses = $gradient ? 'gradient-bg text-white' : '';
    
    $borderLeftClasses = '';
    if ($borderLeft) {
        $borderColorMap = [
            'primary' => 'border-l-4 border-l-primary-500',
            'secondary' => 'border-l-4 border-l-gray-500',
            'success' => 'border-l-4 border-l-green-500',
            'danger' => 'border-l-4 border-l-red-500',
            'warning' => 'border-l-4 border-l-yellow-500',
            'info' => 'border-l-4 border-l-blue-500',
        ];
        $borderLeftClasses = $borderColorMap[$borderLeftColor] ?? $borderColorMap['primary'];
    }
    
    $classes = $baseClasses . ' ' . $hoverClasses . ' ' . $gradientClasses . ' ' . $borderLeftClasses;
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    @if ($title || $subtitle)
        <div class="{{ $padding }} @if(!$gradient) border-b border-gray-200 @endif">
            @if ($title)
                <h3 class="text-lg font-medium @if(!$gradient) text-gray-800 @endif">{{ $title }}</h3>
            @endif
            
            @if ($subtitle)
                <p class="mt-1 text-sm @if(!$gradient) text-gray-500 @else text-white opacity-90 @endif">{{ $subtitle }}</p>
            @endif
        </div>
    @endif
    
    <div class="{{ $padding }}">
        {{ $slot }}
    </div>
    
    @if ($footer)
        <div class="{{ $padding }} @if(!$gradient) border-t border-gray-200 @endif">
            {{ $footer }}
        </div>
    @endif
</div>

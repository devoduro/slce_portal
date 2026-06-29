@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'disabled' => false,
    'icon' => null,
    'iconPosition' => 'left',
    'fullWidth' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium transition-colors duration-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2';
    
    $variantClasses = [
        'primary' => 'bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500',
        'secondary' => 'bg-gray-100 text-gray-800 hover:bg-gray-200 focus:ring-gray-500',
        'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
        'warning' => 'bg-yellow-500 text-white hover:bg-yellow-600 focus:ring-yellow-500',
        'info' => 'bg-blue-500 text-white hover:bg-blue-600 focus:ring-blue-500',
        'outline-primary' => 'border border-primary-600 text-primary-600 hover:bg-primary-50 focus:ring-primary-500',
        'outline-secondary' => 'border border-gray-300 text-gray-700 hover:bg-gray-50 focus:ring-gray-500',
        'outline-danger' => 'border border-red-600 text-red-600 hover:bg-red-50 focus:ring-red-500',
        'link' => 'text-primary-600 hover:text-primary-700 hover:underline',
        'gradient' => 'gradient-bg text-white hover:opacity-90 focus:ring-primary-500',
    ];
    
    $sizeClasses = [
        'xs' => 'text-xs px-2.5 py-1.5',
        'sm' => 'text-sm px-3 py-2',
        'md' => 'text-sm px-4 py-2',
        'lg' => 'text-base px-5 py-2.5',
        'xl' => 'text-base px-6 py-3',
    ];
    
    $disabledClasses = 'opacity-50 cursor-not-allowed';
    $fullWidthClasses = 'w-full';
    
    $classes = $baseClasses . ' ' . 
               $variantClasses[$variant] . ' ' . 
               $sizeClasses[$size] . ' ' . 
               ($disabled ? $disabledClasses : '') . ' ' . 
               ($fullWidth ? $fullWidthClasses : '');
@endphp

@if ($href && !$disabled)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon && $iconPosition === 'left')
            <i class="{{ $icon }} mr-2"></i>
        @endif
        
        {{ $slot }}
        
        @if ($icon && $iconPosition === 'right')
            <i class="{{ $icon }} ml-2"></i>
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }} @if($disabled) disabled @endif>
        @if ($icon && $iconPosition === 'left')
            <i class="{{ $icon }} mr-2"></i>
        @endif
        
        {{ $slot }}
        
        @if ($icon && $iconPosition === 'right')
            <i class="{{ $icon }} ml-2"></i>
        @endif
    </button>
@endif

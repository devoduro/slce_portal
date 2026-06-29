@props([
    'type' => 'text',
    'name',
    'id' => null,
    'label' => null,
    'placeholder' => null,
    'value' => null,
    'disabled' => false,
    'required' => false,
    'autofocus' => false,
    'helper' => null,
    'error' => null,
    'leadingIcon' => null,
    'trailingIcon' => null,
])

@php
    $id = $id ?? $name;
    $hasError = $error || $errors->has($name);
    $inputClasses = 'w-full rounded-lg shadow-sm focus:ring focus:ring-opacity-50 ' . 
                   ($leadingIcon ? 'pl-10 ' : '') . 
                   ($trailingIcon ? 'pr-10 ' : '') . 
                   ($hasError 
                       ? 'border-red-300 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-200' 
                       : 'border-gray-300 focus:border-primary-500 focus:ring-primary-200');
@endphp

<div {{ $attributes }}>
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    
    <div class="relative">
        @if ($leadingIcon)
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="{{ $leadingIcon }} text-gray-400"></i>
            </div>
        @endif
        
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $id }}"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($value !== null) value="{{ $value }}" @endif
            @if ($disabled) disabled @endif
            @if ($required) required @endif
            @if ($autofocus) autofocus @endif
            {{ $attributes->merge(['class' => $inputClasses]) }}
        />
        
        @if ($trailingIcon)
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <i class="{{ $trailingIcon }} text-gray-400"></i>
            </div>
        @endif
    </div>
    
    @if ($helper && !$hasError)
        <p class="mt-1 text-sm text-gray-500">{{ $helper }}</p>
    @endif
    
    @if ($error)
        <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
    @elseif ($errors->has($name))
        <p class="mt-1 text-sm text-red-600">{{ $errors->first($name) }}</p>
    @endif
</div>

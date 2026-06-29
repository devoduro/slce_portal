@props([
    'id',
    'title' => null,
    'maxWidth' => 'md',
    'closeButton' => true,
    'staticBackdrop' => false,
    'footer' => null,
])

@php
    $maxWidthClasses = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
        '4xl' => 'max-w-4xl',
        '5xl' => 'max-w-5xl',
        'full' => 'max-w-full',
    ][$maxWidth];
@endphp

<div
    x-data="{ open: false }"
    x-init="$watch('open', value => {
        if (value) {
            document.body.classList.add('overflow-hidden');
        } else {
            document.body.classList.remove('overflow-hidden');
        }
    })"
    @keydown.escape.window="@if(!$staticBackdrop) open = false @endif"
    x-id="['modal-title']"
    id="{{ $id }}"
    class="relative"
    x-cloak
>
    <!-- Trigger -->
    <div @click="open = true">
        {{ $trigger }}
    </div>

    <!-- Modal -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900 bg-opacity-50"
    >
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            @click.outside="@if(!$staticBackdrop) open = false @endif"
            class="w-full {{ $maxWidthClasses }} bg-white rounded-lg shadow-xl overflow-hidden"
        >
            <!-- Header -->
            @if ($title)
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-800" :id="$id('modal-title')">
                        {{ $title }}
                    </h3>
                    
                    @if ($closeButton)
                        <button 
                            @click="open = false" 
                            class="text-gray-400 hover:text-gray-500 focus:outline-none focus:text-gray-500 transition-colors"
                        >
                            <span class="sr-only">Close</span>
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    @endif
                </div>
            @elseif ($closeButton)
                <div class="px-6 py-4 flex justify-end">
                    <button 
                        @click="open = false" 
                        class="text-gray-400 hover:text-gray-500 focus:outline-none focus:text-gray-500 transition-colors"
                    >
                        <span class="sr-only">Close</span>
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            @endif

            <!-- Body -->
            <div class="px-6 py-4">
                {{ $slot }}
            </div>

            <!-- Footer -->
            @if ($footer)
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Script to open modal from outside -->
<script>
    document.addEventListener('alpine:init', () => {
        window.openModal = (modalId) => {
            const modalElement = document.getElementById(modalId);
            if (modalElement) {
                const modalComponent = Alpine.evaluate(modalElement, 'open');
                if (modalComponent) {
                    modalComponent.open = true;
                }
            }
        };
        
        window.closeModal = (modalId) => {
            const modalElement = document.getElementById(modalId);
            if (modalElement) {
                const modalComponent = Alpine.evaluate(modalElement, 'open');
                if (modalComponent) {
                    modalComponent.open = false;
                }
            }
        };
    });
</script>

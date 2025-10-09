@props([
    'title',
    'description' => null,
    'icon' => null,
    'btnText' => null,
    'btnUrl' => null,
])

<div {{ $attributes->merge(['class' => 'box p-6']) }}>
    <div class="flex flex-col sm:flex-row items-start justify-between gap-8">
        <div class="flex-1">
            <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">
                {{ $title }}
            </h3>

            @if($description)
                <p class="mt-2 text-gray-500 dark:text-gray-400">
                    {!! $description !!}
                </p>
            @endif

            @if($btnText && $btnUrl)
                <div style="margin-top: 10px">
                    <x-moonshine::link-button :href="$btnUrl" target="_blank" :filled="true">
                        {{ $btnText }}
                    </x-moonshine::link-button>
                </div>
            @endif
        </div>
        @if($icon)
            <div class="shrink-0 self-center sm:self-end">
                <img src="{{ asset($icon) }}"
                     alt="{{ $title }}"
                     class="h-28 w-auto max-w-full object-contain">
            </div>
        @endif

    </div>
</div>

@props([
    'value',
])
<button @click.prevent="rotateToken('{{ $value }}')"
        :disabled="rotatingToken"
        {{ $attributes->class(['expansion']) }}
        type="button"
        title="Получить новый токен"
>
    <span :class="rotatingToken ? 'animate-spin' : ''" style="display: inline-flex">
        <x-moonshine::icon
            icon="arrow-path"
            size="4"
        />
    </span>
</button>

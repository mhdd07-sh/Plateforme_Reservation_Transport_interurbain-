@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-label-lg text-label-lg text-on-surface']) }}>
    {{ $value ?? $slot }}
</label>
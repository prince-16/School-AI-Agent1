@props(['variant' => 'primary'])

@php
    $classes = $variant === 'secondary'
        ? 'rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50'
        : 'rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800';
@endphp

<button {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }}>
    {{ $slot }}
</button>

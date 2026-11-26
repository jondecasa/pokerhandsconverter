@props(['label', 'model', 'suffix' => null, 'help' => null, 'step' => 'any', 'min' => 0, 'max' => null, 'placeholder' => null])

<label class="block">
    <span class="text-sm font-medium text-slate-700">{{ $label }}</span>
    <span class="relative mt-1 block">
        <input type="number" inputmode="decimal" step="{{ $step }}"
            @if (! is_null($min)) min="{{ $min }}" @endif
            @if (! is_null($max)) max="{{ $max }}" @endif
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            x-model.number="{{ $model }}"
            class="block w-full rounded-lg border-slate-300 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500 {{ $suffix ? 'pr-14' : '' }}">
        @if ($suffix)
            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-slate-400">{{ $suffix }}</span>
        @endif
    </span>
    @if ($help)
        <span class="mt-1 block text-xs text-slate-500">{{ $help }}</span>
    @endif
</label>

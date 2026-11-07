@props(['name', 'checked' => false, 'model' => null, 'label', 'hint' => null])
<label class="flex cursor-pointer items-start justify-between gap-4 py-3">
    <span>
        <span class="block font-semibold">{{ $label }}</span>
        @if ($hint)
            <span class="mt-0.5 block text-sm text-muted">{{ $hint }}</span>
        @endif
    </span>
    <span class="relative mt-0.5 shrink-0">
        <input type="checkbox" name="{{ $name }}" value="1" class="peer sr-only" @checked($checked) @if ($model) x-model="{{ $model }}" @endif {{ $attributes }}>
        <span class="block h-7 w-12 rounded-full bg-bulb-off transition-colors peer-checked:bg-glow peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-glow"></span>
        <span class="absolute left-1 top-1 size-5 rounded-full bg-ink transition-transform peer-checked:translate-x-5 peer-checked:bg-glow-ink"></span>
    </span>
</label>

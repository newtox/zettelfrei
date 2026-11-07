@props(['selected' => 'christmas', 'name' => 'theme', 'model' => null])
@php
    $swatches = [
        'christmas' => ['#12241c', '#f0b93f', '#c23a44'],
        'dark' => ['#1c1d20', '#ffe7b8', '#3a3e45'],
        'light' => ['#f3f5f4', '#1f5c45', '#b0303a'],
    ];
@endphp
<div class="grid grid-cols-3 gap-2">
    @foreach (\App\Models\Room::THEMES as $key => $label)
        <label class="cursor-pointer">
            <input data-form-type="other" type="radio" name="{{ $name }}" value="{{ $key }}" class="peer sr-only" @checked($selected === $key) @if ($model) x-model="{{ $model }}" @endif>
            <span class="block rounded-2xl p-2.5 ring-1 ring-line ring-inset peer-checked:ring-2 peer-checked:ring-glow peer-focus-visible:outline-3 peer-focus-visible:outline-glow">
                <span class="flex h-10 overflow-hidden rounded-lg">
                    <span class="flex-[3]" style="background: {{ $swatches[$key][0] }}"></span>
                    <span class="flex-1" style="background: {{ $swatches[$key][1] }}"></span>
                    <span class="flex-1" style="background: {{ $swatches[$key][2] }}"></span>
                </span>
                <span class="mt-2 block truncate text-center text-xs font-semibold sm:text-sm lg:text-xs">{{ $label }}</span>
            </span>
        </label>
    @endforeach
</div>

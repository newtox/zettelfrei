@props(['style' => 'lights', 'criterion', 'current' => 0])
@php
    $id = $criterion->id;
    $group = 'rating_'.$id;
    $label = fn ($n) => $n.' '.($n === 1 ? 'Punkt' : 'Punkte').' für '.$criterion->name;
    $grades = $style === 'grades';
    $shownCurrent = $current ? ($grades ? 7 - (int) $current : $current) : '–';
@endphp
<fieldset class="panel px-4 pb-4 pt-3.5" x-data="rating({{ (int) $current }})">
    <div class="flex items-baseline justify-between gap-3">
        <legend class="text-lg font-semibold">{{ $criterion->name }}</legend>
        <span class="font-display text-3xl tabular-nums" :class="value ? 'text-glow' : 'text-muted'" x-text="value ? {{ $grades ? '7 - value' : 'value' }} : '–'">{{ $shownCurrent }}</span>
    </div>
    <input type="hidden" name="scores[{{ $id }}]" :value="value || ''" value="{{ $current ?: '' }}" data-score data-form-type="other">

    @switch($style)
        @case('grades')
            <div class="mt-3 grid grid-cols-6 gap-2">
                @for ($g = 1; $g <= 6; $g++)
                    @php($n = 7 - $g)
                    <label class="cursor-pointer">
                        <input type="radio" name="{{ $group }}" value="{{ $n }}" class="peer sr-only" @checked((int) $current === $n) @change="pick({{ $n }})" aria-label="Note {{ $g }} für {{ $criterion->name }}" data-form-type="other">
                        <span class="grid aspect-square place-items-center rounded-full bg-bulb-off font-display text-2xl tabular-nums text-muted transition-colors peer-checked:bg-glow peer-checked:text-glow-ink peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-ink">{{ $g }}</span>
                    </label>
                @endfor
            </div>
            <div class="mt-1.5 flex justify-between text-xs text-muted" aria-hidden="true"><span>sehr gut</span><span>ungenügend</span></div>
            @break

        @case('numbers')
            <div class="mt-3 grid grid-cols-5 gap-2 sm:grid-cols-10">
                @for ($n = 1; $n <= 10; $n++)
                    <label class="cursor-pointer">
                        <input type="radio" name="{{ $group }}" value="{{ $n }}" class="peer sr-only" @checked((int) $current === $n) @change="pick({{ $n }})" aria-label="{{ $label($n) }}" data-form-type="other">
                        <span class="grid h-12 place-items-center rounded-xl bg-bulb-off text-lg font-bold tabular-nums text-muted transition-colors peer-checked:bg-glow peer-checked:text-glow-ink peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-ink">{{ $n }}</span>
                    </label>
                @endfor
            </div>
            @break

        @case('slider')
            <div class="mt-4">
                <input type="range" min="1" max="10" step="1" value="{{ $current ?: 5 }}" @input="pick(Number($event.target.value), false)" @change="pick(Number($event.target.value))" @pointerup="pick(Number($el.value))" @touchend.passive="pick(Number($el.value))" @keyup="pick(Number($el.value))" class="rating-slider w-full" :class="!value && 'is-untouched'" aria-label="Punkte für {{ $criterion->name }}" data-form-type="other">
                <div class="mt-1 grid grid-cols-10 text-center text-xs tabular-nums text-muted" aria-hidden="true">
                    @for ($n = 1; $n <= 10; $n++)
                        <span :class="value === {{ $n }} && 'font-bold text-ink'">{{ $n }}</span>
                    @endfor
                </div>
                <p x-show="!value" class="mt-1 text-center text-sm text-muted">Regler ziehen oder antippen</p>
            </div>
            @break

        @case('stars')
            <div class="mt-3 flex justify-between gap-1 sm:justify-start sm:gap-3" @mouseleave="preview = 0">
                @for ($s = 1; $s <= 5; $s++)
                    <div class="relative size-14 shrink-0 sm:size-16">
                        <svg viewBox="0 0 24 24" class="absolute inset-0 size-full text-bulb-off" fill="currentColor" aria-hidden="true"><path d="M12 2.5l2.9 6.1 6.6.8-4.9 4.6 1.3 6.6L12 17.3l-5.9 3.3 1.3-6.6-4.9-4.6 6.6-.8z"/></svg>
                        <div class="absolute inset-0 overflow-hidden" :style="'width: ' + (Math.max(0, Math.min(2, shown - {{ ($s - 1) * 2 }})) * 50) + '%'" aria-hidden="true">
                            <svg viewBox="0 0 24 24" class="size-14 text-glow drop-shadow-[0_0_6px_var(--glow)] sm:size-16" fill="currentColor"><path d="M12 2.5l2.9 6.1 6.6.8-4.9 4.6 1.3 6.6L12 17.3l-5.9 3.3 1.3-6.6-4.9-4.6 6.6-.8z"/></svg>
                        </div>
                        @foreach ([$s * 2 - 1, $s * 2] as $i => $n)
                            <label class="absolute inset-y-0 {{ $i === 0 ? 'left-0' : 'right-0' }} w-1/2 cursor-pointer" @mouseenter="preview = {{ $n }}">
                                <input type="radio" name="{{ $group }}" value="{{ $n }}" class="peer sr-only" @checked((int) $current === $n) @change="pick({{ $n }})" aria-label="{{ $label($n) }}" data-form-type="other">
                                <span class="block size-full rounded-md peer-focus-visible:outline-3 peer-focus-visible:outline-ink"></span>
                            </label>
                        @endforeach
                    </div>
                @endfor
            </div>
            @break

        @default
            <div class="lights mt-1" @mouseleave="preview = 0">
                @for ($n = 1; $n <= 10; $n++)
                    <label class="bulb" :class="{ 'is-lit': shown >= {{ $n }}, 'is-picked': value === {{ $n }} }" @mouseenter="preview = {{ $n }}">
                        <input type="radio" name="{{ $group }}" value="{{ $n }}" @checked((int) $current === $n) @change="pick({{ $n }})" aria-label="{{ $label($n) }}" data-form-type="other">
                        <span class="cap"></span>
                        <span class="glass">{{ $n }}</span>
                    </label>
                @endfor
            </div>
    @endswitch
</fieldset>

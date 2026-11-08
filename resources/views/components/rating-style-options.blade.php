@props(['selected' => 'lights', 'model' => null, 'compact' => false])
<div {{ $attributes->class(['grid gap-2', 'grid-cols-2' => $compact, 'grid-cols-2 sm:grid-cols-5' => ! $compact]) }}>
    @foreach (\App\Models\Room::RATING_STYLES as $key => $style)
        <label class="cursor-pointer">
            <input type="radio" name="rating_style" value="{{ $key }}" class="peer sr-only" @checked($selected === $key) @if ($model) x-model="{{ $model }}" @endif data-form-type="other">
            <span class="flex h-full flex-col rounded-2xl p-3 ring-1 ring-line ring-inset peer-checked:ring-2 peer-checked:ring-glow peer-focus-visible:outline-3 peer-focus-visible:outline-glow">
                <span class="flex h-8 items-center justify-center gap-1" aria-hidden="true">
                    @switch($key)
                        @case('lights')
                            @for ($i = 1; $i <= 5; $i++)
                                <span class="h-4 w-3 rounded-[45%_45%_50%_50%/35%_35%_65%_65%] {{ $i <= 3 ? 'bg-glow shadow-[0_0_6px_var(--glow)]' : 'bg-bulb-off' }}"></span>
                            @endfor
                            @break
                        @case('numbers')
                            @foreach ([6, 7, 8] as $n)
                                <span class="grid size-6 place-items-center rounded-md text-xs font-bold {{ $n === 7 ? 'bg-glow text-glow-ink' : 'bg-bulb-off text-muted' }}">{{ $n }}</span>
                            @endforeach
                            @break
                        @case('slider')
                            <span class="relative h-1.5 w-16 rounded-full bg-bulb-off"><span class="absolute -top-1.5 left-9 size-4.5 rounded-full bg-glow"></span></span>
                            @break
                        @case('stars')
                            @for ($i = 1; $i <= 4; $i++)
                                <svg viewBox="0 0 24 24" class="size-5 {{ $i <= 3 ? 'text-glow' : 'text-bulb-off' }}" fill="currentColor"><path d="M12 2.5l2.9 6.1 6.6.8-4.9 4.6 1.3 6.6L12 17.3l-5.9 3.3 1.3-6.6-4.9-4.6 6.6-.8z"/></svg>
                            @endfor
                            @break
                        @case('grades')
                            @foreach ([1, 2, 3] as $n)
                                <span class="grid size-6 place-items-center rounded-full text-xs font-bold {{ $n === 2 ? 'bg-glow text-glow-ink' : 'bg-bulb-off text-muted' }}">{{ $n }}</span>
                            @endforeach
                            @break
                    @endswitch
                </span>
                <span class="mt-2 block text-center text-sm font-semibold">{{ $style['label'] }}</span>
                @unless ($compact)
                    <span class="mt-0.5 block text-center text-xs text-muted">{{ $style['hint'] }}</span>
                @endunless
            </span>
        </label>
    @endforeach
</div>

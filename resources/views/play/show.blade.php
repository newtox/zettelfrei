<x-layouts.app :title="$room->name" :theme="$room->theme" :room="$room">
    <div x-data="poller('{{ route('play.state', [$room, 'station' => $station?->id]) }}', '{{ $stateKey }}', {{ config('zettelfrei.poll_interval') }})">
        <p class="text-sm text-muted">{{ $room->name }}, du bist als <span class="font-semibold text-ink">{{ $participant->name }}</span> dabei</p>

        @if ($station)
            @if ($openStations->count() > 1)
                <nav class="-mx-5 mt-4 flex gap-2 overflow-x-auto px-5 pb-1" aria-label="Offene {{ $room->items_label }}">
                    @foreach ($openStations as $s)
                        <a href="{{ route('play.show', [$room, 'station' => $s->id]) }}" @class([
                            'flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-2 text-sm font-semibold',
                            'bg-glow text-glow-ink' => $s->id === $station->id,
                            'bg-raised' => $s->id !== $station->id,
                        ]) @if ($s->id === $station->id) aria-current="true" @endif>
                            @if (in_array($s->id, $votedStations))
                                <svg viewBox="0 0 20 20" class="size-4" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-label="bewertet"><path d="M4.5 10.5l3.5 3.5 7.5-8"/></svg>
                            @endif
                            {{ $s->name }}
                        </a>
                    @endforeach
                </nav>
            @endif
            <section class="mt-4">
                @if ($openStations->count() === 1)
                    <p class="inline-flex items-center gap-2 rounded-full bg-live px-3 py-1 text-sm font-semibold text-live-ink">
                        <span class="size-2 rounded-full bg-current"></span>
                        Jetzt dran
                    </p>
                @endif
                <h1 class="mt-3 text-4xl">{{ $station->name }}</h1>
            </section>

            @if (! $participant->canRate($station))
                <div class="panel mt-6 p-5">
                    <p class="text-lg font-semibold">Hier gehörst du selbst dazu.</p>
                    <p class="mt-1 text-muted">Das bewerten die anderen.@if ($room->photos_enabled) Fotos kannst du trotzdem dazulegen.@endif</p>
                    <p class="mt-4 text-sm text-muted"><span x-text="data?.station?.votes ?? '…'"></span> von <span x-text="data?.station?.eligible ?? '…'"></span> haben schon abgestimmt.</p>
                </div>
            @else
                <form method="POST" action="{{ route('play.vote', [$room, $station]) }}" class="mt-6 space-y-3" x-data="ballot({{ $room->criteria->count() }})" @change="update()">
                    @csrf
                    <x-errors />
                    @foreach ($room->criteria as $criterion)
                        @php $current = old('scores.'.$criterion->id, $scores[$criterion->id] ?? 0); @endphp
                        <fieldset class="panel px-4 pb-4 pt-3.5" x-data="lights({{ (int) $current }})">
                            <div class="flex items-baseline justify-between gap-3">
                                <legend class="text-lg font-semibold">{{ $criterion->name }}</legend>
                                <span class="font-display text-3xl tabular-nums" :class="value ? 'text-glow' : 'text-muted'" x-text="value || '–'">{{ $current ?: '–' }}</span>
                            </div>
                            <div class="lights mt-1" @mouseleave="preview = 0">
                                @for ($n = 1; $n <= 10; $n++)
                                    <label class="bulb" :class="{ 'is-lit': shown >= {{ $n }}, 'is-picked': value === {{ $n }} }" @mouseenter="preview = {{ $n }}">
                                        <input type="radio" name="scores[{{ $criterion->id }}]" value="{{ $n }}" @checked((int) $current === $n) @change="pick({{ $n }})" aria-label="{{ $n }} {{ $n === 1 ? 'Punkt' : 'Punkte' }} für {{ $criterion->name }}">
                                        <span class="cap"></span>
                                        <span class="glass">{{ $n }}</span>
                                    </label>
                                @endfor
                            </div>
                        </fieldset>
                    @endforeach

                    <div class="sticky bottom-3 pt-2">
                        <button class="btn btn-primary w-full text-lg shadow-[0_10px_30px_-10px_rgba(0,0,0,.6)]" :disabled="filled < count" :class="filled < count && 'opacity-50'">
                            <span x-show="filled >= count">{{ $ballot ? 'Bewertung ändern' : 'Bewertung abgeben' }}</span>
                            <span x-show="filled < count" x-cloak>Noch <span x-text="count - filled"></span> offen</span>
                        </button>
                    </div>
                    <p class="text-center text-sm text-muted">
                        @if ($ballot)
                            Du hast schon abgestimmt.
                        @endif
                        <span x-text="data?.station?.votes ?? '…'"></span> von <span x-text="data?.station?.eligible ?? '…'"></span> sind fertig.
                    </p>
                </form>
            @endif

            @if ($room->photos_enabled)
                <section class="mt-8">
                    <h2 class="text-xl">Fotos</h2>
                    <div class="mt-3">
                        <x-photo-strip :room="$room" :station="$station" :participant="$participant" />
                    </div>
                </section>
            @endif
        @else
            <section class="mt-6">
                <h1 class="text-4xl">Kurze Pause</h1>
                <p class="mt-2 text-lg text-muted">Sobald es weitergeht, erscheint die Bewertung hier von selbst.</p>
            </section>
        @endif

        <section class="mt-10">
            <h2 class="text-xl">{{ $room->one_at_a_time ? 'Der Rundgang' : 'Alle '.$room->items_label }}</h2>
            <ol class="mt-3 space-y-1.5">
                @foreach ($room->stations as $s)
                    <li class="flex items-center gap-3 rounded-xl px-3 py-2.5 {{ $station && $s->id === $station->id ? 'bg-raised' : '' }}">
                        <span @class([
                            'grid size-7 shrink-0 place-items-center rounded-full text-sm font-bold',
                            'bg-glow text-glow-ink' => in_array($s->id, $votedStations),
                            'bg-live text-live-ink' => $s->isOpen() && ! in_array($s->id, $votedStations),
                            'bg-bulb-off text-muted' => ! $s->isOpen() && ! in_array($s->id, $votedStations),
                        ])>
                            @if (in_array($s->id, $votedStations))
                                <svg viewBox="0 0 20 20" class="size-4" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 10.5l3.5 3.5 7.5-8"/></svg>
                            @else
                                {{ $loop->iteration }}
                            @endif
                        </span>
                        @if ($s->isOpen() && ! ($station && $s->id === $station->id))
                            <a href="{{ route('play.show', [$room, 'station' => $s->id]) }}" class="flex-1 underline decoration-line underline-offset-4">{{ $s->name }}</a>
                        @else
                            <span class="flex-1 {{ $s->status === 'closed' && ! in_array($s->id, $votedStations) ? 'text-muted' : '' }}">{{ $s->name }}</span>
                        @endif
                        <span class="text-sm text-muted">
                            @if ($participant->station_id === $s->id)
                                gehörst du dazu
                            @elseif ($s->isOpen())
                                {{ in_array($s->id, $votedStations) ? 'bewertet, offen' : 'offen' }}
                            @elseif ($s->status === 'closed')
                                {{ in_array($s->id, $votedStations) ? 'bewertet' : 'verpasst' }}
                            @endif
                        </span>
                    </li>
                @endforeach
            </ol>
            <div class="mt-6 flex flex-wrap gap-x-5 gap-y-2 text-sm">
                @if ($room->photos_enabled)
                    <a href="{{ route('play.gallery', $room) }}" class="font-semibold underline decoration-line underline-offset-4 hover:decoration-glow">Alle Fotos</a>
                @endif
                <form method="POST" action="{{ route('play.leave', $room) }}" onsubmit="return confirm('Wirklich austreten? Deine bisherigen Stimmen bleiben anonym erhalten.')">
                    @csrf
                    <button class="text-muted underline decoration-line underline-offset-4">Nicht {{ $participant->name }}? Austreten</button>
                </form>
            </div>
        </section>
    </div>
</x-layouts.app>

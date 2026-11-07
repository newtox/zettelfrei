@php
    $fmt = fn ($v) => $v === null ? '–' : number_format($v, 1, ',', '.');
    $count = $results->count();
    $choice = $room->isChoice();
    $revealed = min($room->reveal_step, $count);
@endphp
<x-layouts.app :title="'Ergebnis – '.$room->name" :theme="$room->theme" :room="$room">
    <div x-data="poller('{{ route('play.state', $room) }}', '{{ $stateKey }}', {{ config('zettelfrei.poll_interval') }})">
        <p class="text-sm text-muted">{{ $room->name }}</p>
        <h1 class="mt-1 text-4xl">{{ $full ? 'Das Ergebnis' : 'Die Auflösung läuft' }}</h1>
        @unless ($full)
            <p class="mt-2 text-muted">{{ $revealed }} von {{ $count }} Plätzen aufgedeckt. Der nächste erscheint hier von selbst.</p>
        @endunless

        <ol class="mt-8 space-y-3">
            @foreach ($results as $row)
                @php $photo = $row['station']->photos->first(); @endphp
                <li @class(['rank-card panel overflow-hidden', 'is-fresh' => $row['fresh'] && ! $full || ($full && $row['rank'] === 1 && $row['fresh']), 'ring-2 ring-glow' => $row['visible'] && $row['rank'] === 1])>
                    @if ($row['visible'])
                        <div class="flex items-center gap-4 p-4">
                            <span @class([
                                'font-display grid shrink-0 place-items-center rounded-full tabular-nums',
                                'size-16 bg-glow text-3xl text-glow-ink' => $row['rank'] === 1,
                                'size-12 bg-raised text-2xl' => $row['rank'] !== 1,
                            ])>{{ $row['rank'] }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xl font-semibold {{ $row['rank'] === 1 ? 'sm:text-2xl' : '' }}">{{ $row['station']->name }}</p>
                                @if ($choice)
                                    <p class="text-sm text-muted">{{ $row['share'] }} % der Teilnehmenden</p>
                                @else
                                    <p class="text-sm text-muted">{{ $row['ballots'] }} {{ $row['ballots'] === 1 ? 'Stimme' : 'Stimmen' }}</p>
                                @endif
                            </div>
                            <div class="text-right">
                                @if ($choice)
                                    <p class="font-display text-3xl tabular-nums {{ $row['rank'] === 1 ? 'text-glow' : '' }}">{{ $row['votes'] }}</p>
                                    <p class="text-xs text-muted">{{ $row['votes'] === 1 ? 'Stimme' : 'Stimmen' }}</p>
                                @else
                                    <p class="font-display text-3xl tabular-nums {{ $row['rank'] === 1 ? 'text-glow' : '' }}">{{ $fmt($row['average']) }}</p>
                                    <p class="text-xs text-muted">von 10</p>
                                @endif
                            </div>
                        </div>
                        @if ($choice)
                            <div class="mx-4 mb-4 h-2 overflow-hidden rounded-full bg-bulb-off">
                                <div class="h-full rounded-full bg-glow" style="width: {{ $row['share'] }}%"></div>
                            </div>
                        @endif
                        @if ($photo)
                            <img src="{{ route('photos.show', [$room, $photo]) }}" alt="Foto von {{ $row['station']->name }}" loading="lazy" class="max-h-72 w-full object-cover">
                        @endif
                        @if ($full && ! $choice)
                            <dl class="grid grid-cols-2 gap-x-6 gap-y-1.5 border-t border-line px-4 py-3 text-sm sm:grid-cols-3">
                                @foreach ($room->criteria as $criterion)
                                    <div class="flex justify-between gap-2">
                                        <dt class="truncate text-muted">{{ $criterion->name }}</dt>
                                        <dd class="font-semibold tabular-nums">{{ $fmt($row['criteria'][$criterion->id]) }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif
                    @else
                        <div class="flex items-center gap-4 p-4 opacity-60">
                            <span class="font-display grid size-12 shrink-0 place-items-center rounded-full bg-raised text-2xl">{{ $loop->iteration }}</span>
                            <p class="flex-1 text-lg text-muted">Noch geheim</p>
                            <div class="flex gap-1" aria-hidden="true">
                                @for ($i = 0; $i < 3; $i++)
                                    <span class="size-3 rounded-full bg-bulb-off"></span>
                                @endfor
                            </div>
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>

        @if ($full && ! $choice && $winners->isNotEmpty() && $room->criteria->count() > 1)
            <section class="mt-10">
                <h2 class="text-2xl">Die Besten je Kriterium</h2>
                <ul class="mt-4 divide-y divide-line">
                    @foreach ($winners as $w)
                        <li class="flex items-baseline justify-between gap-4 py-2.5">
                            <span class="text-muted">{{ $w['criterion']->name }}</span>
                            <span class="text-right"><span class="font-semibold">{{ $w['stations']->pluck('name')->join(' & ') }}</span> <span class="tabular-nums text-muted">{{ $fmt($w['score']) }}</span></span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($isOwner)
            <section class="panel mt-10 p-5">
                <h2 class="text-xl">Steuerung</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    @unless ($full)
                        <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.reveal', $room) }}">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                            <input data-form-type="other" type="hidden" name="action" value="next">
                            <button class="btn btn-primary">{{ $revealed === $count - 1 ? 'Sieger aufdecken' : 'Nächsten Platz aufdecken' }}</button>
                        </form>
                        <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.reveal', $room) }}">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                            <input data-form-type="other" type="hidden" name="action" value="all">
                            <button class="btn btn-quiet">Rest auf einmal</button>
                        </form>
                    @endunless
                    <a href="{{ route('rooms.manage', $room) }}" class="btn btn-quiet">Zur Raumverwaltung</a>
                </div>
            </section>
        @endif

        @if ($room->photos_enabled)
            <p class="mt-8 text-sm"><a href="{{ route('play.gallery', $room) }}" class="font-semibold underline decoration-line underline-offset-4 hover:decoration-glow">Alle Fotos ansehen</a></p>
        @endif
    </div>
</x-layouts.app>

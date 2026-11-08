@php
    $stationCount = $room->stations->count();
    $statusLabel = ['pending' => 'Noch nicht dran', 'open' => 'Wird gerade bewertet', 'closed' => 'Fertig'];
    $choice = $room->isChoice();
@endphp
<x-layouts.app :title="$room->name" :theme="$room->theme" :room="$room" wide>
    <div x-data="poller('{{ route('rooms.state', $room) }}', null, {{ config('zettelfrei.poll_interval') }})">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl sm:text-4xl">{{ $room->name }}</h1>
                <p class="mt-1 text-muted">
                    {{ \App\Models\Room::MODES[$room->mode] }},
                    <span x-text="data ? data.participants : {{ $room->participants->count() }}">{{ $room->participants->count() }}</span> Personen dabei
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($room->photos_enabled)
                    <a href="{{ route('play.gallery', $room) }}" class="btn btn-quiet">Fotos</a>
                @endif
                <a href="{{ route('play.show', $room) }}" class="btn btn-quiet">Selbst mitmachen</a>
            </div>
        </div>

        @if ($errors->any())
            <div class="mt-6"><x-errors /></div>
        @endif

        @if (session('created'))
            <section class="panel mt-6 p-5 ring-2 ring-glow" x-data="qr(@js($room->adminUrl()))">
                <h2 class="text-xl">Speicher dir diesen Admin-Link</h2>
                <p class="mt-1 max-w-2xl text-sm text-muted">Dieses Gerät merkt sich, dass du den Raum verwaltest. Auf einem anderen Gerät, oder falls du deine Browserdaten löschst, kommst du nur mit diesem Link wieder in die Verwaltung. Gib ihn nur weiter, wenn die Person mitverwalten soll.</p>
                <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                    <input data-form-type="other" readonly value="{{ $room->adminUrl() }}" class="field min-h-10 font-mono text-sm" @focus="$el.select()" aria-label="Admin-Link">
                    <button type="button" @click="copy()" class="btn btn-primary btn-sm shrink-0" x-text="copied ? 'Kopiert' : 'Link kopieren'">Link kopieren</button>
                </div>
            </section>
        @endif

        <div class="mt-8 grid gap-5 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="space-y-5">
                <section class="panel p-5">
                    <h2 class="text-2xl">{{ $room->items_label }}</h2>
                    @if ($choice)
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-2xl p-4 {{ $room->voting_open ? 'bg-raised ring-2 ring-live' : 'bg-page/40' }}">
                            <div>
                                <p class="font-semibold">{{ $room->voting_open ? 'Abstimmung läuft' : ($room->isRevealing() ? 'Abstimmung beendet' : 'Abstimmung noch nicht offen') }}</p>
                                <p class="text-sm text-muted"><span x-text="data ? data.voters : {{ $room->voterCount() }}">{{ $room->voterCount() }}</span> von <span x-text="data ? data.participants : {{ $room->participants->count() }}">{{ $room->participants->count() }}</span> haben abgestimmt</p>
                            </div>
                            @if ($room->voting_open)
                                <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.close-all', $room) }}">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                                    <button class="btn btn-live btn-sm">Abstimmung schließen</button>
                                </form>
                            @elseif (! $room->isRevealing())
                                <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.open-all', $room) }}">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                                    <button class="btn btn-primary btn-sm">Abstimmung öffnen</button>
                                </form>
                            @endif
                        </div>
                    @elseif ($room->one_at_a_time)
                        <p class="mt-1 text-sm text-muted">Gib immer den Eintrag frei, bei dem ihr gerade seid. Es ist immer nur einer offen.</p>
                    @else
                        <div class="mt-1 flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm text-muted">Alle können gleichzeitig bewertet werden.</p>
                            @unless ($room->isRevealing())
                                <div class="flex gap-2">
                                    <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.open-all', $room) }}">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                                        <button class="btn btn-primary btn-sm">Alle öffnen</button>
                                    </form>
                                    <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.close-all', $room) }}">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                                        <button class="btn btn-quiet btn-sm">Alle schließen</button>
                                    </form>
                                </div>
                            @endunless
                        </div>
                    @endif


                    <ol class="mt-5 space-y-2">
                        @foreach ($room->stations as $station)
                            @php
                                $votesInit = $station->ballots_count.' von '.$station->eligibleVoterCount().' abgestimmt';
                                $photosInit = $station->photos_count.' '.($station->photos_count === 1 ? 'Foto' : 'Fotos');
                                $statusText = $choice ? null : $statusLabel[$station->status];
                            @endphp
                            <li @class([
                                'rounded-2xl p-4',
                                'bg-raised ring-2 ring-live' => ! $choice && $station->isOpen(),
                                'bg-page/40' => $choice || ! $station->isOpen(),
                            ])>
                                <div class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-x-3 gap-y-3 sm:grid-cols-[auto_minmax(0,1fr)_auto_auto] sm:gap-x-4">
                                    <span class="font-display w-6 text-xl text-muted">{{ $loop->iteration }}</span>
                                    <div class="min-w-0">
                                        <p class="break-words text-lg font-semibold leading-snug">{{ $station->name }}</p>
                                        <p class="text-sm text-muted">
                                            @if ($statusText)<span @class(['font-semibold text-live' => $station->isOpen()])>{{ $statusText }}</span>, <span x-text="(() => { const s = data?.stations.find(s => s.id === {{ $station->id }}); return s ? s.votes + ' von ' + s.eligible + ' abgestimmt' : @js($votesInit) })()">{{ $votesInit }}</span>@endif{{ $statusText && $room->photos_enabled ? ', ' : '' }}@if ($room->photos_enabled)<span x-text="(() => { const s = data?.stations.find(s => s.id === {{ $station->id }}); return s ? s.photos + (s.photos === 1 ? ' Foto' : ' Fotos') : @js($photosInit) })()">{{ $photosInit }}</span>@endif
                                        </p>
                                    </div>
                                    <details class="relative order-3 sm:order-4">
                                        <summary class="grid size-10 cursor-pointer list-none place-items-center rounded-full text-muted hover:bg-raised" aria-label="Mehr für {{ $station->name }}">
                                            <svg viewBox="0 0 20 20" class="size-5" fill="currentColor"><circle cx="4" cy="10" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="16" cy="10" r="1.6"/></svg>
                                        </summary>
                                        <div class="absolute right-0 z-10 mt-2 w-72 max-w-[calc(100vw-3rem)] space-y-3 rounded-2xl bg-surface p-4 shadow-2xl ring-1 ring-line">
                                            <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.stations.update', [$room, $station]) }}" class="space-y-2">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other"> <input type="hidden" name="_method" value="PATCH" data-form-type="other">
                                                <input data-form-type="other" name="name" value="{{ $station->name }}" maxlength="60" class="field min-h-10 text-base" aria-label="Neuer Name">
                                                <button class="btn btn-quiet btn-sm w-full">Umbenennen</button>
                                            </form>
                                            <div class="flex gap-2">
                                                <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.stations.move', [$room, $station, 'up']) }}" class="flex-1">
                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                                                    <button class="btn btn-quiet btn-sm w-full" @disabled($loop->first)>Nach oben</button>
                                                </form>
                                                <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.stations.move', [$room, $station, 'down']) }}" class="flex-1">
                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                                                    <button class="btn btn-quiet btn-sm w-full" @disabled($loop->last)>Nach unten</button>
                                                </form>
                                            </div>
                                            <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.stations.destroy', [$room, $station]) }}" data-confirm="{{ ($station->name).' samt Stimmen und Fotos löschen?' }}" data-confirm-action="Löschen" data-confirm-danger>
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other"> <input type="hidden" name="_method" value="DELETE" data-form-type="other">
                                                <button class="text-sm font-semibold text-live">{{ $room->item_label }} löschen</button>
                                            </form>
                                        </div>
                                    </details>
                                    @if (! $choice && ($station->isOpen() || ! $room->isRevealing()))
                                        <div class="order-4 col-span-3 sm:order-3 sm:col-span-1">
                                            @if ($station->isOpen())
                                                <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.stations.close', [$room, $station]) }}">
                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                                                    <button class="btn btn-live btn-sm w-full sm:w-auto">Bewertung schließen</button>
                                                </form>
                                            @else
                                                <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.stations.open', [$room, $station]) }}">
                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                                                    <button class="btn btn-sm w-full sm:w-auto {{ $station->status === 'pending' ? 'btn-primary' : 'btn-quiet' }}">{{ $station->status === 'pending' ? 'Bewertung öffnen' : 'Wieder öffnen' }}</button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>

                    <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.stations.store', $room) }}" class="mt-4 flex gap-2">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                        <input data-form-type="other" name="name" placeholder="{{ $room->item_label }} hinzufügen" maxlength="60" class="field" required>
                        <button class="btn btn-quiet shrink-0">Hinzufügen</button>
                    </form>
                </section>

                <section class="panel p-5">
                    <h2 class="text-2xl">Auflösung</h2>
                    @if (! $room->isRevealing())
                        <p class="mt-1 text-muted">Bis hierhin sieht niemand ein Ergebnis, auch du nicht. Beim Aufdecken wird die Abstimmung geschlossen und alle Handys springen zum Ergebnis.</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.reveal', $room) }}">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                                <input data-form-type="other" type="hidden" name="action" value="next">
                                <button class="btn btn-primary">Platz für Platz aufdecken</button>
                            </form>
                            <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.reveal', $room) }}">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                                <input data-form-type="other" type="hidden" name="action" value="all">
                                <button class="btn btn-quiet">Alles auf einmal</button>
                            </form>
                        </div>
                    @else
                        <p class="mt-1 text-muted">{{ min($room->reveal_step, $stationCount) }} von {{ $stationCount }} Plätzen aufgedeckt.</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('play.results', $room) }}" class="btn btn-primary">Zum Ergebnis</a>
                            <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.reveal', $room) }}" data-confirm="Ergebnis für alle wieder verbergen?" data-confirm-action="Verbergen">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                                <input data-form-type="other" type="hidden" name="action" value="hide">
                                <button class="btn btn-quiet">Wieder verbergen</button>
                            </form>
                        </div>
                    @endif
                </section>
            </div>

            <aside class="space-y-5">
                <section class="panel p-5" x-data="qr(@js($room->joinUrl()))">
                    <h2 class="text-xl">Einladen</h2>
                    <button type="button" @click="big = true" class="mt-4 block w-full rounded-2xl bg-white p-3" aria-label="QR-Code groß anzeigen">
                        <canvas data-qr="260" class="mx-auto h-auto max-w-full"></canvas>
                    </button>
                    <p class="mt-3 text-center text-sm text-muted">Code</p>
                    <p class="text-center font-display text-3xl tracking-[0.18em]">{{ $room->code }}</p>
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <button type="button" @click="copy()" class="btn btn-quiet btn-sm" x-text="copied ? 'Kopiert' : 'Link kopieren'">Link kopieren</button>
                        <button type="button" @click="share()" class="btn btn-quiet btn-sm">Teilen</button>
                    </div>
                    <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.update', $room) }}" class="mt-4">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other"> <input type="hidden" name="_method" value="PATCH" data-form-type="other">
                        <input data-form-type="other" type="hidden" name="join_locked" value="{{ $room->join_locked ? 0 : 1 }}">
                        <button class="text-sm text-muted underline underline-offset-4 hover:text-ink">{{ $room->join_locked ? 'Beitritt wieder erlauben' : 'Keine neuen Leute mehr zulassen' }}</button>
                    </form>

                    <div x-show="big" x-cloak x-transition.opacity class="fixed inset-0 z-50 grid place-items-center bg-black/80 p-6" @click="big = false" @keydown.escape.window="big = false">
                        <div class="w-full max-w-md rounded-3xl bg-white p-6 text-center text-[#14221b]">
                            <canvas data-qr="600" class="mx-auto h-auto w-full"></canvas>
                            <p class="mt-4 font-display text-4xl tracking-[0.18em]">{{ $room->code }}</p>
                            <p class="mt-1 text-sm">{{ $room->joinUrl() }}</p>
                        </div>
                    </div>
                </section>

                <section class="panel p-5">
                    <h2 class="text-xl">Dabei</h2>
                    @if ($room->participants->isEmpty())
                        <p class="mt-2 text-sm text-muted">Noch niemand. Sobald jemand den QR-Code scannt und einen Namen eingibt, erscheint er hier.</p>
                    @else
                        <ul class="mt-3 divide-y divide-line">
                            @foreach ($room->participants as $p)
                                <li class="flex items-center justify-between gap-3 py-2">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold">{{ $p->name }}</p>
                                        @if ($room->exclude_own)
                                            <p class="truncate text-xs text-muted">{{ $p->station?->name ?? 'Gehört zu keinem Eintrag' }}</p>
                                        @endif
                                    </div>
                                    <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.participants.destroy', [$room, $p->id]) }}" data-confirm="{{ ($p->name).' entfernen? Die Person kann danach neu beitreten.' }}" data-confirm-action="Entfernen" data-confirm-danger>
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other"> <input type="hidden" name="_method" value="DELETE" data-form-type="other">
                                        <button class="rounded-full px-2 py-1 text-xs text-muted hover:bg-raised hover:text-live">Entfernen</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                @if (! $choice)
                <section class="panel p-5">
                    <h2 class="text-xl">Kriterien</h2>
                    @if ($locked)
                        <p class="mt-1 text-sm text-muted">Es gibt schon Bewertungen. Neue Kriterien gelten nur für Stimmen, die ab jetzt abgegeben werden, und beim Löschen gehen die Punkte dafür verloren.</p>
                    @endif
                    <ul class="mt-3 divide-y divide-line">
                        @foreach ($room->criteria as $criterion)
                            <li class="flex items-center gap-1 py-1.5">
                                <span class="flex-1 truncate">{{ $criterion->name }}</span>
                                <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.criteria.move', [$room, $criterion->id, 'up']) }}">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                                    <button class="grid size-8 place-items-center rounded-full text-muted hover:bg-raised disabled:opacity-30" @disabled($loop->first) aria-label="Nach oben"><svg viewBox="0 0 20 20" class="size-3.5" fill="currentColor"><path d="M10 5l6 7H4z"/></svg></button>
                                </form>
                                <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.criteria.move', [$room, $criterion->id, 'down']) }}">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                                    <button class="grid size-8 place-items-center rounded-full text-muted hover:bg-raised disabled:opacity-30" @disabled($loop->last) aria-label="Nach unten"><svg viewBox="0 0 20 20" class="size-3.5" fill="currentColor"><path d="M10 15l6-7H4z"/></svg></button>
                                </form>
                                @if ($room->criteria->count() > 1)
                                    <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.criteria.destroy', [$room, $criterion->id]) }}" data-confirm="{{ 'Kriterium „'.($criterion->name).'“ löschen?' }}" data-confirm-action="Löschen" data-confirm-danger>
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other"> <input type="hidden" name="_method" value="DELETE" data-form-type="other">
                                        <button class="grid size-8 place-items-center rounded-full text-muted hover:bg-raised hover:text-live" aria-label="Löschen"><svg viewBox="0 0 20 20" class="size-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 5l10 10M15 5L5 15"/></svg></button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.criteria.store', $room) }}" class="mt-3 flex gap-2">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                        <input data-form-type="other" name="name" placeholder="Neues Kriterium" maxlength="60" class="field min-h-10 text-base" required>
                        <button class="btn btn-quiet btn-sm shrink-0">Hinzufügen</button>
                    </form>
                </section>
                @endif

                <section class="panel p-5">
                    <h2 class="text-xl">Ablauf</h2>
                    <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.settings', $room) }}" class="mt-2">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other"> <input type="hidden" name="_method" value="PATCH" data-form-type="other">
                        <p class="mb-1.5 text-sm font-semibold">Bezeichnung der Einträge</p>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="item_label" class="mb-1 block text-sm text-muted">Einzahl</label>
                                <input data-form-type="other" id="item_label" name="item_label" value="{{ $room->item_label }}" maxlength="30" class="field min-h-10 text-base" required>
                            </div>
                            <div>
                                <label for="items_label" class="mb-1 block text-sm text-muted">Mehrzahl</label>
                                <input data-form-type="other" id="items_label" name="items_label" value="{{ $room->items_label }}" maxlength="30" class="field min-h-10 text-base" required>
                            </div>
                        </div>
                        <div class="mt-2 divide-y divide-line" x-data="{ exclude_own: @js($room->exclude_own), photos_enabled: @js($room->photos_enabled) }">
                            <x-toggle name="exclude_own" model="exclude_own" :checked="$room->exclude_own" label="Eigenen Eintrag ausschließen" />
                            <div x-show="exclude_own" x-cloak class="py-3">
                                <x-own-question-fields scoped :question="$room->ownQuestion()" :none="$room->ownNoneLabel()" />
                            </div>
                            <x-toggle name="photos_enabled" model="photos_enabled" :checked="$room->photos_enabled" label="Fotos erlauben" hint="Bis zu {{ \App\Models\Room::maxPhotosPerEntry() }} pro Eintrag" />
                            <div x-show="photos_enabled && exclude_own" x-cloak>
                                <x-toggle name="photos_own_only" :checked="$room->photos_own_only" label="Fotos nur für den eigenen Eintrag" />
                            </div>
                            @if ($choice)
                                <div class="flex items-center justify-between gap-4 py-3">
                                    <label for="max_choices" class="font-semibold">Auswahl pro Person</label>
                                    <input data-form-type="other" id="max_choices" name="max_choices" type="number" min="1" max="10" value="{{ $room->max_choices }}" class="field min-h-10 w-20 text-center text-base">
                                </div>
                            @else
                                <x-toggle name="one_at_a_time" :checked="$room->one_at_a_time" label="Einer nach dem anderen" />
                                <div class="py-3">
                                    <p class="mb-2 font-semibold">Art der Bewertung</p>
                                    <x-rating-style-options :selected="$room->rating_style" compact />
                                </div>
                            @endif
                        </div>
                        <button class="btn btn-quiet btn-sm mt-2">Speichern</button>
                    </form>
                </section>

                <section class="panel p-5">
                    <h2 class="text-xl">Einstellungen</h2>
                    <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.update', $room) }}" class="mt-3 space-y-4">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other"> <input type="hidden" name="_method" value="PATCH" data-form-type="other">
                        <div>
                            <label for="room-name" class="mb-1.5 block text-sm font-semibold">Name</label>
                            <input data-form-type="other" id="room-name" name="name" value="{{ $room->name }}" maxlength="80" class="field min-h-10 text-base" required>
                        </div>
                        <fieldset>
                            <legend class="mb-1.5 text-sm font-semibold">Aussehen für alle</legend>
                            <x-theme-options :selected="$room->theme" />
                        </fieldset>
                        <button class="btn btn-quiet btn-sm">Speichern</button>
                    </form>
                    <div class="mt-6 border-t border-line pt-4" x-data="qr(@js($room->adminUrl()))">
                        <p class="text-sm font-semibold">Admin-Link</p>
                        <p class="mt-0.5 text-sm text-muted">Damit verwaltet man den Raum von jedem Gerät aus.</p>
                        <div class="mt-2 flex gap-2">
                            <button type="button" @click="copy()" class="btn btn-quiet btn-sm" x-text="copied ? 'Kopiert' : 'Kopieren'">Kopieren</button>
                            <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.token', $room) }}" data-confirm="Neuen Admin-Link erzeugen? Der alte funktioniert dann nicht mehr." data-confirm-action="Neuen Link erzeugen">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                                <button class="btn btn-quiet btn-sm">Neuen erzeugen</button>
                            </form>
                        </div>
                    </div>
                    @if ($room->pruneDate())
                        <p class="mt-4 text-sm text-muted">Ohne weitere Aktivität wird der Raum am {{ $room->pruneDate()->translatedFormat('j. F Y') }} automatisch gelöscht.</p>
                    @endif
                    <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.reset', $room) }}" class="mt-4 border-t border-line pt-4" data-confirm="Alle Stimmen löschen und den Raum auf Anfang setzen? Einträge, Kriterien und Einstellungen bleiben." data-confirm-action="Zurücksetzen" data-confirm-danger>
                        <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                        <p class="text-sm font-semibold">Raum zurücksetzen</p>
                        <p class="mt-0.5 text-sm text-muted">Löscht alle Stimmen, schließt alle Einträge und blendet die Auflösung aus. Wie frisch erstellt.</p>
                        <label class="mt-2 flex items-center gap-2 text-sm">
                            <input data-form-type="other" type="checkbox" name="keep_participants" value="1" class="size-4 accent-[var(--glow)]">
                            Teilnehmende behalten
                        </label>
                        @if ($room->photos_enabled || $room->photos()->exists())
                            <label class="mt-1.5 flex items-center gap-2 text-sm">
                                <input data-form-type="other" type="checkbox" name="keep_photos" value="1" class="size-4 accent-[var(--glow)]">
                                Fotos behalten
                            </label>
                        @endif
                        <button class="btn btn-quiet btn-sm mt-3">Zurücksetzen</button>
                    </form>
                    <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.destroy', $room) }}" class="mt-4 border-t border-line pt-4" data-confirm="{{ 'Raum „'.($room->name).'“ mit allen Bewertungen und Fotos endgültig löschen?' }}" data-confirm-action="Raum löschen" data-confirm-danger>
                        <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other"> <input type="hidden" name="_method" value="DELETE" data-form-type="other">
                        <button class="text-sm font-semibold text-live">Raum löschen</button>
                    </form>
                </section>
            </aside>
        </div>
    </div>
</x-layouts.app>

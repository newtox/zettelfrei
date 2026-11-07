<x-layouts.app title="Admin – Räume" wide>
    @include('admin.nav')

    <form novalidate data-form-type="other" method="GET" class="mt-6 flex gap-2">
        <input data-form-type="other" name="q" value="{{ $search }}" placeholder="Name oder Code suchen" class="field max-w-sm">
        <button class="btn btn-quiet shrink-0">Suchen</button>
        @if ($search !== '')
            <a href="{{ route('admin.rooms') }}" class="btn btn-quiet shrink-0">Zurücksetzen</a>
        @endif
    </form>

    <p class="mt-4 text-sm text-muted">
        {{ $rooms->total() }} {{ $rooms->total() === 1 ? 'Raum' : 'Räume' }}.
        @if ($retention > 0)
            Räume ohne Aktivität werden nach {{ $retention }} Tagen samt Fotos gelöscht, außer sie sind auf „Behalten“ gesetzt.
        @else
            Automatisches Aufräumen ist ausgeschaltet.
        @endif
    </p>

    @if ($rooms->isEmpty())
        <div class="panel mt-6 p-6 text-muted">{{ $search !== '' ? 'Kein Raum passt zur Suche.' : 'Noch keine Räume.' }}</div>
    @else
        <div class="panel mt-4 overflow-x-auto">
            <table class="w-full min-w-[52rem] text-left text-sm">
                <thead class="text-muted">
                    <tr class="border-b border-line">
                        <th class="px-4 py-3 font-semibold">Raum</th>
                        <th class="px-4 py-3 font-semibold">Erstellt von</th>
                        <th class="px-4 py-3 font-semibold">Inhalt</th>
                        <th class="px-4 py-3 font-semibold">Zuletzt aktiv</th>
                        <th class="px-4 py-3 font-semibold">Löschung</th>
                        <th class="px-4 py-3"><span class="sr-only">Aktionen</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($rooms as $r)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('rooms.manage', $r) }}" class="font-semibold hover:text-glow">{{ $r->name }}</a>
                                <p class="text-muted">{{ $r->code }}, {{ \App\Models\Room::MODES[$r->mode] }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $r->owner?->name ?? 'ohne Konto' }}</td>
                            <td class="px-4 py-3 text-muted">{{ $r->stations_count }} {{ $r->items_label }}, {{ $r->participants_count }} Personen, {{ $r->photos_count }} Fotos</td>
                            <td class="px-4 py-3 text-muted" title="{{ $r->last_activity_at?->translatedFormat('j. F Y, H:i') }}">{{ $r->last_activity_at?->diffForHumans() ?? '–' }}</td>
                            <td class="px-4 py-3">
                                @if ($r->keep)
                                    <span class="rounded-full bg-glow px-2.5 py-0.5 text-xs font-semibold text-glow-ink">Behalten</span>
                                @elseif ($r->pruneDate())
                                    <span class="{{ $r->pruneDate()->isPast() ? 'font-semibold text-live' : 'text-muted' }}">{{ $r->pruneDate()->translatedFormat('j. M Y') }}</span>
                                @else
                                    <span class="text-muted">nie</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1.5">
                                    <form novalidate data-form-type="other" method="POST" action="{{ route('admin.rooms.keep', $r) }}">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                                        <button class="btn btn-quiet btn-sm">{{ $r->keep ? 'Nicht mehr behalten' : 'Behalten' }}</button>
                                    </form>
                                    <form novalidate data-form-type="other" method="POST" action="{{ route('admin.rooms.destroy', $r) }}" data-confirm="{{ 'Raum „'.$r->name.'“ mit allen Stimmen und Fotos endgültig löschen?' }}" data-confirm-action="Raum löschen" data-confirm-danger>
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other"> <input type="hidden" name="_method" value="DELETE" data-form-type="other">
                                        <button class="btn btn-sm text-live hover:bg-raised">Löschen</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($rooms->hasPages())
            <div class="mt-4 flex justify-between text-sm">
                @if ($rooms->previousPageUrl())
                    <a href="{{ $rooms->previousPageUrl() }}" class="btn btn-quiet btn-sm">Neuere</a>
                @else
                    <span></span>
                @endif
                @if ($rooms->nextPageUrl())
                    <a href="{{ $rooms->nextPageUrl() }}" class="btn btn-quiet btn-sm">Ältere</a>
                @endif
            </div>
        @endif
    @endif
</x-layouts.app>

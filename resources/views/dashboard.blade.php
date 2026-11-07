<x-layouts.app title="Meine Räume" wide>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl sm:text-4xl">Hallo {{ auth()->user()->name }}</h1>
            <p class="mt-1 text-muted">Deine Räume und die, die du auf diesem Gerät verwaltest.</p>
        </div>
        <div class="flex gap-2">
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.rooms') }}" class="btn btn-quiet">Alle Räume</a>
            @endif
            <a href="{{ route('presets.index') }}" class="btn btn-quiet">Vorlagen</a>
            <a href="{{ route('rooms.create') }}" class="btn btn-primary">Neuen Raum erstellen</a>
        </div>
    </div>

    @if ($rooms->isEmpty())
        <div class="panel mt-8 p-8">
            <h2 class="text-2xl">Noch kein Raum</h2>
            <p class="mt-2 max-w-md text-muted">Leg einen Raum an, trag ein, worüber abgestimmt wird, und zeig den anderen den QR-Code. Das dauert etwa zwei Minuten.</p>
            <a href="{{ route('rooms.create') }}" class="btn btn-primary mt-5">Ersten Raum erstellen</a>
        </div>
    @else
        <ul class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($rooms as $room)
                <li>
                    <a href="{{ route('rooms.manage', $room) }}" class="panel block h-full p-5 transition-colors hover:bg-raised">
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="text-xl">{{ $room->name }}</h2>
                            @if ($room->reveal_step > 0)
                                <span class="shrink-0 rounded-full bg-glow px-2.5 py-0.5 text-xs font-semibold text-glow-ink">Aufgelöst</span>
                            @endif
                        </div>
                        <p class="mt-3 text-sm text-muted">
                            {{ $room->created_at->translatedFormat('j. F Y') }}<br>
                            {{ $room->stations_count }} {{ $room->stations_count === 1 ? $room->item_label : $room->items_label }}, {{ $room->participants_count }} {{ $room->participants_count === 1 ? 'Person' : 'Personen' }} dabei
                        </p>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.app>

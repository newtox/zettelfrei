<x-layouts.app :title="'Fotos – '.$room->name" :theme="$room->theme" :room="$room">
    <p class="text-sm text-muted">{{ $room->name }}</p>
    <h1 class="mt-1 text-4xl">Fotos</h1>

    <div class="mt-8 space-y-10">
        @foreach ($room->stations as $station)
            <section>
                <h2 class="text-2xl">{{ $station->name }}</h2>
                @if ($station->photos->isEmpty())
                    <p class="mt-2 text-sm text-muted">Noch keine Fotos.</p>
                @endif
                <div class="mt-3">
                    <x-photo-strip :room="$room" :station="$station" :participant="$participant" :is-owner="$isOwner" />
                </div>
            </section>
        @endforeach
    </div>

    <p class="mt-10 text-sm">
        <a href="{{ $isOwner && ! $participant ? route('rooms.manage', $room) : route('play.show', $room) }}" class="font-semibold underline decoration-line underline-offset-4 hover:decoration-glow">Zurück</a>
    </p>
</x-layouts.app>

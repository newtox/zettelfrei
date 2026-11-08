<x-layouts.app :title="$room->name" :theme="$room->theme" :room="$room">
    <p class="text-muted">Du wurdest eingeladen zu</p>
    <h1 class="mt-1 text-4xl">{{ $room->name }}</h1>

    @if ($room->join_locked)
        <div class="panel mt-8 p-5">
            <p class="font-semibold">Dieser Raum nimmt gerade keine neuen Leute auf.</p>
            <p class="mt-1 text-sm text-muted">Sag der Person, die den Raum erstellt hat, dass sie den Beitritt wieder freigeben soll.</p>
        </div>
    @else
        <form novalidate data-form-type="other" method="POST" action="{{ route('play.join', $room) }}" class="panel mt-8 space-y-5 p-5">
            <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
            <x-errors />
            <div>
                <label for="name" class="mb-1.5 block font-semibold">Wie heißt du?</label>
                <input data-form-type="other" id="name" name="name" @error('name') aria-invalid="true" @enderror value="{{ old('name') }}" maxlength="40" autocomplete="given-name" class="field" required autofocus>
                <p class="mt-1.5 text-sm text-muted">Die anderen sehen nur, dass du dabei bist, nie wie du abgestimmt hast.</p>
            </div>
            @if ($room->exclude_own)
            <div>
                <label for="station_id" class="mb-1.5 block font-semibold">{{ $room->ownQuestion() }}</label>
                <select data-form-type="other" id="station_id" name="station_id" @error('station_id') aria-invalid="true" @enderror class="field">
                    <option value="">{{ $room->ownNoneLabel() }}</option>
                    @foreach ($room->stations as $station)
                        <option value="{{ $station->id }}" @selected(old('station_id') == $station->id)>{{ $station->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-sm text-muted">Dafür stimmst du dann nicht mit ab.@if ($room->photos_enabled && $room->photos_own_only) Fotos kannst du nur dort hochladen.@endif</p>
            </div>
            @endif
            <button class="btn btn-primary w-full">Mitmachen</button>
        </form>
    @endif
</x-layouts.app>

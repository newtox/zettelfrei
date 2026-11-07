@props(['room', 'station', 'participant' => null, 'isOwner' => false, 'upload' => true])
<div>
    @if ($station->photos->isNotEmpty())
        <ul class="-mx-5 flex snap-x gap-2 overflow-x-auto px-5 pb-1">
            @foreach ($station->photos as $photo)
                <li class="relative shrink-0 snap-start">
                    <a href="{{ route('photos.show', [$room, $photo]) }}" target="_blank" class="block">
                        <img src="{{ route('photos.show', [$room, $photo]) }}" alt="Foto von {{ $station->name }}" loading="lazy" class="h-40 w-auto max-w-[16rem] rounded-xl object-cover" @if ($photo->width && $photo->height) width="{{ $photo->width }}" height="{{ $photo->height }}" style="aspect-ratio: {{ $photo->width }} / {{ $photo->height }}" @endif>
                    </a>
                    @if ($isOwner || ($participant && $photo->participant_id === $participant->id))
                        <form novalidate data-form-type="other" method="POST" action="{{ route('photos.destroy', [$room, $photo]) }}" class="absolute right-1.5 top-1.5" data-confirm="Foto löschen?" data-confirm-action="Löschen" data-confirm-danger>
                            @csrf @method('DELETE')
                            <button class="grid size-8 place-items-center rounded-full bg-black/60 text-white" aria-label="Foto löschen">
                                <svg viewBox="0 0 20 20" class="size-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M5 5l10 10M15 5L5 15"/></svg>
                            </button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if ($upload && $room->photos_enabled)
        <div x-data="photoUpload('{{ route('photos.store', [$room, $station]) }}')" class="{{ $station->photos->isNotEmpty() ? 'mt-3' : '' }}">
            <label class="btn btn-quiet w-full cursor-pointer" :class="busy && 'pointer-events-none opacity-60'">
                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/></svg>
                <span x-text="busy ? 'Lade hoch … ' + done + '/' + total : 'Foto hinzufügen'">Foto hinzufügen</span>
                <input type="file" accept="image/*" multiple class="sr-only" @change="upload($event)">
            </label>
            <p x-show="error" x-text="error" x-cloak class="mt-2 text-sm text-live"></p>
        </div>
    @endif
</div>

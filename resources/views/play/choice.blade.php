@php
    $multi = $room->max_choices > 1;
@endphp
<x-layouts.app :title="$room->name" :theme="$room->theme" :room="$room">
    <div x-data="poller('{{ route('play.state', $room) }}', '{{ $stateKey }}', {{ config('zettelfrei.poll_interval') }})">
        <p class="text-sm text-muted">Du bist als <span class="font-semibold text-ink">{{ $participant->name }}</span> dabei</p>
        <h1 class="mt-3 text-4xl">{{ $room->name }}</h1>

        @if ($room->voting_open)
            <p class="mt-2 text-muted">
                {{ $multi ? 'Wähl bis zu '.$room->max_choices.' Favoriten.' : 'Wähl deinen Favoriten.' }}
                @if ($picked)
                    Du hast schon abgestimmt und kannst es noch ändern.
                @endif
            </p>

            <form novalidate data-form-type="other" method="POST" action="{{ route('play.choose', $room) }}" class="mt-6"
                x-data="{ picked: @js(array_map('intval', old('options', $picked))), max: {{ $room->max_choices }},
                    toggle(id) {
                        if (this.max === 1) { this.picked = [id]; return; }
                        if (this.picked.includes(id)) { this.picked = this.picked.filter(p => p !== id); return; }
                        if (this.picked.length < this.max) this.picked.push(id);
                    } }">
                <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                <x-errors />
                <ul class="space-y-2.5">
                    @foreach ($room->stations as $option)
                        @php
                            $own = $room->exclude_own && $participant->station_id === $option->id;
                            $photo = $room->photos_enabled ? $option->photos->first() : null;
                        @endphp
                        <li>
                            @if ($own)
                                <div class="panel flex items-center gap-4 p-4 opacity-60">
                                    <span class="size-7 shrink-0 rounded-full border-2 border-line"></span>
                                    <span class="flex-1">
                                        <span class="block text-lg font-semibold">{{ $option->name }}</span>
                                        <span class="block text-sm text-muted">Hier gehörst du selbst dazu</span>
                                    </span>
                                </div>
                            @else
                                <button type="button" @click="toggle({{ $option->id }})" role="{{ $multi ? 'checkbox' : 'radio' }}" :aria-checked="picked.includes({{ $option->id }}).toString()"
                                    class="panel flex w-full items-center gap-4 overflow-hidden p-4 text-left ring-inset transition-shadow"
                                    :class="picked.includes({{ $option->id }}) ? 'ring-2 ring-glow' : 'ring-1 ring-transparent'"
                                    :disabled="!picked.includes({{ $option->id }}) && max > 1 && picked.length >= max">
                                    <span class="grid size-7 shrink-0 place-items-center rounded-full transition-[background-color,box-shadow]"
                                        :class="picked.includes({{ $option->id }}) ? 'bg-glow text-glow-ink shadow-[0_0_0.9rem_-0.1rem_var(--glow)]' : 'border-2 border-line'">
                                        <svg x-show="picked.includes({{ $option->id }})" x-cloak viewBox="0 0 20 20" class="size-4" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 10.5l3.5 3.5 7.5-8"/></svg>
                                    </span>
                                    <span class="min-w-0 flex-1 text-lg font-semibold">{{ $option->name }}</span>
                                    @if ($photo)
                                        <img src="{{ route('photos.show', [$room, $photo]) }}" alt="" class="-my-4 -mr-4 h-20 w-24 shrink-0 object-cover">
                                    @endif
                                </button>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <template x-for="id in picked" :key="id">
                    <input data-form-type="other" type="hidden" name="options[]" :value="id">
                </template>

                <div class="sticky bottom-3 mt-5">
                    <button class="btn btn-primary w-full text-lg shadow-[0_10px_30px_-10px_rgba(0,0,0,.6)]" :disabled="!picked.length" :class="!picked.length && 'opacity-50'">
                        {{ $picked ? 'Stimme ändern' : 'Stimme abgeben' }}
                    </button>
                </div>
                <p class="mt-3 text-center text-sm text-muted"><span x-text="data?.voters ?? '…'"></span> von <span x-text="data?.eligible ?? '…'"></span> haben abgestimmt.</p>
            </form>
        @else
            <div class="panel mt-6 p-5">
                <p class="text-lg font-semibold">{{ $picked ? 'Deine Stimme ist abgegeben.' : 'Die Abstimmung ist noch nicht offen.' }}</p>
                <p class="mt-1 text-muted">{{ $picked ? 'Sobald aufgelöst wird, springt diese Seite von selbst zum Ergebnis.' : 'Sobald sie freigegeben wird, erscheint sie hier von selbst.' }}</p>
            </div>
            <ul class="mt-6 space-y-1.5">
                @foreach ($room->stations as $option)
                    <li class="flex items-center gap-3 rounded-xl px-3 py-2.5">
                        <span class="grid size-7 place-items-center rounded-full {{ in_array($option->id, $picked) ? 'bg-glow text-glow-ink' : 'bg-bulb-off' }}">
                            @if (in_array($option->id, $picked))
                                <svg viewBox="0 0 20 20" class="size-4" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 10.5l3.5 3.5 7.5-8"/></svg>
                            @endif
                        </span>
                        {{ $option->name }}
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="mt-10 flex flex-wrap gap-x-5 gap-y-2 text-sm">
            @if ($room->photos_enabled)
                <a href="{{ route('play.gallery', $room) }}" class="font-semibold underline decoration-line underline-offset-4 hover:decoration-glow">Fotos ansehen und hinzufügen</a>
            @endif
            <form novalidate data-form-type="other" method="POST" action="{{ route('play.leave', $room) }}" data-confirm="Wirklich austreten? Deine bisherige Stimme bleibt anonym erhalten." data-confirm-action="Austreten" data-confirm-danger>
                <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                <button class="text-muted underline decoration-line underline-offset-4">Nicht {{ $participant->name }}? Austreten</button>
            </form>
        </div>
    </div>
</x-layouts.app>

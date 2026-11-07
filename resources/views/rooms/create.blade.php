@php
    $defaults = $templates['trees'];
    $presetList = $presets->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'criteria' => $p->criteria, 'mine' => ! $p->isSystem()]);
    $initial = [
        'template' => old('template', 'trees'),
        'name' => old('name', $defaults['name'].' '.now()->year),
        'theme' => old('theme', $defaults['theme']),
        'mode' => old('mode', $defaults['mode']),
        'item_label' => old('item_label', $defaults['item_label']),
        'items_label' => old('items_label', $defaults['items_label']),
        'exclude_own' => (bool) old('exclude_own', $errors->any() ? false : $defaults['exclude_own']),
        'photos_enabled' => (bool) old('photos_enabled', $errors->any() ? false : $defaults['photos_enabled']),
        'one_at_a_time' => (bool) old('one_at_a_time', $errors->any() ? false : $defaults['one_at_a_time']),
        'max_choices' => (int) old('max_choices', $defaults['max_choices']),
        'save_preset' => (bool) old('save_preset'),
    ];
@endphp
<x-layouts.app title="Neuer Raum">
    <h1 class="text-3xl">Neuer Raum</h1>
    <p class="mt-2 text-muted">Alles außer der Abstimmungsart lässt sich später noch ändern.</p>

    <form novalidate data-form-type="other" method="POST" action="{{ route('rooms.store') }}" class="mt-6 space-y-5"
        x-data="{
            ...@js($initial),
            templates: @js($templates),
            presets: @js($presetList),
            year: {{ now()->year }},
            apply(key) {
                const t = this.templates[key];
                this.template = key;
                this.name = t.name + (t.mode === 'score' ? ' ' + this.year : '');
                ['theme', 'mode', 'item_label', 'items_label', 'exclude_own', 'photos_enabled', 'one_at_a_time', 'max_choices'].forEach(k => this[k] = t[k]);
                const preset = this.presets.find(p => p.name === t.preset);
                if (preset) this.$dispatch('set-criteria', preset.criteria);
            },
        }">
        <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
        <input data-form-type="other" type="hidden" name="template" :value="template">
        <x-errors />

        <section>
            <h2 class="text-xl">Worum geht’s?</h2>
            <div class="mt-3 grid gap-2 sm:grid-cols-3">
                @foreach ($templates as $key => $t)
                    <button type="button" @click="apply('{{ $key }}')" class="panel p-4 text-left ring-inset transition-shadow" :class="template === '{{ $key }}' ? 'ring-2 ring-glow' : 'ring-1 ring-line hover:ring-muted'">
                        <span class="block font-semibold">{{ $t['label'] }}</span>
                        <span class="mt-1 block text-sm text-muted">{{ $t['hint'] }}</span>
                    </button>
                @endforeach
            </div>
        </section>

        <section class="panel space-y-4 p-5">
            <div>
                <label for="name" class="mb-1.5 block font-semibold">Name des Raums</label>
                <input data-form-type="other" id="name" name="name" @error('name') aria-invalid="true" @enderror x-model="name" maxlength="80" class="field" required>
                <p class="mt-1.5 text-sm text-muted" x-show="mode === 'choice'" x-cloak>Bei Umfragen am besten gleich als Frage, die steht dann groß über der Abstimmung.</p>
            </div>
            <fieldset>
                <legend class="mb-1.5 font-semibold">Aussehen</legend>
                <x-theme-options :selected="$initial['theme']" model="theme" />
            </fieldset>
        </section>

        <section class="panel p-5">
            <h2 class="text-xl">Abstimmungsart</h2>
            <div class="mt-3 grid grid-cols-2 gap-2">
                @foreach (\App\Models\Room::MODES as $key => $label)
                    <label class="cursor-pointer">
                        <input data-form-type="other" type="radio" name="mode" value="{{ $key }}" x-model="mode" class="peer sr-only">
                        <span class="block h-full rounded-2xl p-3.5 ring-1 ring-line ring-inset peer-checked:ring-2 peer-checked:ring-glow peer-focus-visible:outline-3 peer-focus-visible:outline-glow">
                            <span class="block font-semibold">{{ $label }}</span>
                            <span class="mt-0.5 block text-sm text-muted">{{ $key === 'score' ? 'Jeder gibt 1 bis 10 Punkte pro Kriterium' : 'Jeder wählt seine Favoriten aus' }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <div x-show="mode === 'choice'" x-cloak class="mt-4 flex items-center justify-between gap-4">
                <label for="max_choices" class="font-semibold">Wie viele darf jeder auswählen?</label>
                <input data-form-type="other" id="max_choices" name="max_choices" @error('max_choices') aria-invalid="true" @enderror type="number" min="1" max="10" x-model.number="max_choices" class="field w-20 text-center">
            </div>
        </section>

        <section class="panel p-5">
            <h2 class="text-xl">Einträge</h2>
            <p class="mt-1 text-sm text-muted" x-text="templates[template]?.items_hint ?? 'Worüber abgestimmt wird.'">{{ $templates[$initial['template']]['items_hint'] ?? 'Worüber abgestimmt wird.' }}</p>
            <p class="mt-4 text-sm font-semibold">Wie heißen sie?</p>
            <div class="mt-1.5 grid grid-cols-2 gap-2">
                <div>
                    <label for="item_label" class="mb-1 block text-sm text-muted">Einzahl</label>
                    <input data-form-type="other" id="item_label" name="item_label" @error('item_label') aria-invalid="true" @enderror x-model="item_label" maxlength="30" class="field" required>
                </div>
                <div>
                    <label for="items_label" class="mb-1 block text-sm text-muted">Mehrzahl</label>
                    <input data-form-type="other" id="items_label" name="items_label" @error('items_label') aria-invalid="true" @enderror x-model="items_label" maxlength="30" class="field" required>
                </div>
            </div>
            <p class="mt-4 font-semibold" x-text="items_label || 'Einträge'"></p>
            <p class="text-sm text-muted" x-show="mode === 'score' && one_at_a_time">In der Reihenfolge, in der ihr sie besucht.</p>
            <x-list-editor class="mt-3" x-effect="placeholder = 'z. B. ' + (item_label || 'Eintrag') + ' …'" name="stations" :items="old('stations') ?: ['', '']" placeholder="" add-label="Weitere hinzufügen" />
        </section>

        <section class="panel p-5" x-show="mode === 'score'">
            <h2 class="text-xl">Kriterien</h2>
            <p class="mt-1 text-sm text-muted">Jedes Kriterium bekommt 1 bis 10 Punkte. Starte mit einer Vorlage und pass sie an.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <template x-for="p in presets" :key="p.id">
                    <button type="button" class="btn btn-quiet btn-sm" @click="$dispatch('set-criteria', p.criteria)">
                        <span x-text="p.name"></span>
                        <span x-show="p.mine" class="text-xs text-muted">eigene</span>
                    </button>
                </template>
            </div>
            <x-list-editor class="mt-4" x-on:set-criteria.window="replace($event.detail)" name="criteria" :items="old('criteria', $presets->firstWhere('name', $defaults['preset'])?->criteria ?? ['Gesamteindruck'])" placeholder="z. B. Beleuchtung" add-label="Kriterium hinzufügen" />

            @if ($canSavePreset)
                <label class="mt-5 flex items-center gap-2 text-sm">
                    <input data-form-type="other" type="checkbox" name="save_preset" value="1" x-model="save_preset" class="size-4 accent-[var(--glow)]">
                    Diese Kriterien als eigene Vorlage speichern
                </label>
                <input data-form-type="other" x-show="save_preset" x-cloak name="preset_name" value="{{ old('preset_name') }}" maxlength="60" placeholder="Name der Vorlage" class="field mt-3">
            @endif
        </section>

        <section class="panel divide-y divide-line px-5 py-2">
            <x-toggle name="exclude_own" model="exclude_own" :checked="$initial['exclude_own']" label="Eigenen Eintrag ausschließen" hint="Beim Beitritt gibt jeder an, ob er selbst zu einem Eintrag gehört, und stimmt dafür nicht mit ab." />
            <x-toggle name="photos_enabled" model="photos_enabled" :checked="$initial['photos_enabled']" label="Fotos erlauben" hint="Alle im Raum können Fotos zu jedem Eintrag hochladen." />
            <div x-show="mode === 'score'">
                <x-toggle name="one_at_a_time" model="one_at_a_time" :checked="$initial['one_at_a_time']" label="Einer nach dem anderen" hint="Du gibst immer nur einen Eintrag frei, wie bei einem Rundgang. Aus: alle sind gleichzeitig bewertbar." />
            </div>
        </section>

        @guest
            <p class="text-sm text-muted">Du brauchst kein Konto. Dieses Gerät merkt sich, dass du den Raum verwaltest, und du bekommst zusätzlich einen geheimen Admin-Link für andere Geräte.</p>
        @endguest
        <button class="btn btn-primary w-full">Raum erstellen</button>
    </form>
</x-layouts.app>

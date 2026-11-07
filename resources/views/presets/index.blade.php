<x-layouts.app title="Vorlagen">
    <h1 class="text-3xl">Vorlagen</h1>
    <p class="mt-2 text-muted">Kriterien-Sets, die du beim Erstellen eines Raums mit einem Tipp übernehmen kannst.</p>

    <x-errors />

    <div class="mt-6 space-y-3">
        @foreach ($presets as $preset)
            @if ($preset->isSystem())
                <section class="panel p-5">
                    <h2 class="text-xl">{{ $preset->name }}</h2>
                    <p class="mt-2 text-sm text-muted">{{ implode(', ', $preset->criteria) }}</p>
                </section>
            @else
                <details class="panel group p-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl">{{ $preset->name }}</h2>
                            <p class="mt-1 text-sm text-muted">{{ implode(', ', $preset->criteria) }}</p>
                        </div>
                        <span class="shrink-0 text-sm font-semibold text-muted group-open:hidden">Bearbeiten</span>
                    </summary>
                    <form novalidate data-form-type="other" method="POST" action="{{ route('presets.update', $preset) }}" class="mt-5 space-y-4">
                        @csrf @method('PUT')
                        <input name="name" value="{{ $preset->name }}" maxlength="60" class="field" required aria-label="Name der Vorlage">
                        <x-list-editor name="criteria" :items="$preset->criteria" placeholder="Kriterium" add-label="Kriterium hinzufügen" />
                        <div class="flex items-center justify-between gap-3">
                            <button class="btn btn-primary btn-sm">Speichern</button>
                        </div>
                    </form>
                    <form novalidate data-form-type="other" method="POST" action="{{ route('presets.destroy', $preset) }}" class="mt-3" data-confirm="Vorlage löschen?" data-confirm-action="Löschen" data-confirm-danger>
                        @csrf @method('DELETE')
                        <button class="text-sm font-semibold text-live">Vorlage löschen</button>
                    </form>
                </details>
            @endif
        @endforeach
    </div>

    <section class="panel mt-8 p-5">
        <h2 class="text-xl">Neue Vorlage</h2>
        <form novalidate data-form-type="other" method="POST" action="{{ route('presets.store') }}" class="mt-4 space-y-4">
            @csrf
            <input name="name" placeholder="Name, z. B. Unsere Kriterien" maxlength="60" class="field" required>
            <x-list-editor name="criteria" :items="['']" placeholder="Kriterium" add-label="Kriterium hinzufügen" />
            <button class="btn btn-primary">Vorlage speichern</button>
        </form>
    </section>
</x-layouts.app>

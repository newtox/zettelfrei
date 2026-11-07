<x-layouts.app>
    <section class="pt-6">
        <h1 class="text-[2.6rem] leading-[1.05] sm:text-5xl">Jeder stimmt ab. Keiner sieht, wie.</h1>
        <p class="mt-4 max-w-md text-lg text-muted">Für die Weihnachtsbäume der Verwandtschaft, den Plätzchen-Wettbewerb oder die Frage, wohin es zum Essen geht. Ganz ohne Zettel und Stift.</p>
    </section>

    <form novalidate data-form-type="other" method="POST" action="{{ route('join.find') }}" class="panel mt-8 p-5">
        @csrf
        <label for="code" class="block font-semibold">Raum-Code</label>
        <p class="mt-0.5 text-sm text-muted">Steht unter dem QR-Code, den dir jemand zeigt.</p>
        <div class="mt-3 flex gap-2">
            <input id="code" name="code" @error('code') aria-invalid="true" @enderror value="{{ old('code') }}" maxlength="12" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="z. B. K7TQ2W" class="field font-semibold uppercase tracking-[0.2em] placeholder:tracking-normal placeholder:normal-case" required>
            <button class="btn btn-primary shrink-0">Beitreten</button>
        </div>
        @error('code')
            <p class="mt-2 text-sm text-live">{{ $message }}</p>
        @enderror
    </form>

    <ol class="mt-10 space-y-5">
        <li class="flex gap-4">
            <span class="font-display w-5 shrink-0 text-2xl text-glow">1</span>
            <p><span class="font-semibold">QR-Code scannen, Namen eintippen.</span> <span class="text-muted">Kein Konto, keine App.</span></p>
        </li>
        <li class="flex gap-4">
            <span class="font-display w-5 shrink-0 text-2xl text-glow">2</span>
            <p><span class="font-semibold">Punkte vergeben oder Favoriten wählen.</span> <span class="text-muted">Komplett anonym, und Fotos kann jeder dazulegen.</span></p>
        </li>
        <li class="flex gap-4">
            <span class="font-display w-5 shrink-0 text-2xl text-glow">3</span>
            <p><span class="font-semibold">Gemeinsam auflösen.</span> <span class="text-muted">Vom letzten Platz bis zum Sieger.</span></p>
        </li>
    </ol>

    <section class="mt-12">
        <h2 class="text-2xl">Selbst eine Abstimmung starten</h2>
        <p class="mt-1 text-muted">Ohne Konto. Du bekommst einen QR-Code für die anderen und einen geheimen Link, mit dem nur du den Raum verwaltest.</p>
        <a href="{{ route('rooms.create') }}" class="btn btn-primary mt-4">Neuen Raum erstellen</a>
    </section>

    @if ($deviceRooms->isNotEmpty())
        <section class="mt-12">
            <h2 class="text-xl">Deine Räume auf diesem Gerät</h2>
            <ul class="mt-3 divide-y divide-line">
                @foreach ($deviceRooms as $deviceRoom)
                    <li>
                        <a href="{{ route('rooms.manage', $deviceRoom) }}" class="flex items-center justify-between gap-4 py-3 hover:text-glow">
                            <span class="font-semibold">{{ $deviceRoom->name }}</span>
                            <span class="shrink-0 text-sm text-muted">{{ $deviceRoom->created_at->translatedFormat('j. M Y') }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @guest
        <p class="mt-16 text-sm text-muted">Du hast ein Konto? <a href="{{ route('login') }}" class="font-semibold text-ink underline decoration-line underline-offset-4 hover:decoration-glow">Anmelden</a></p>
    @endguest
</x-layouts.app>

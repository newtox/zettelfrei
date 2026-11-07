<x-layouts.app title="Anmelden">
    <h1 class="text-3xl">Anmelden</h1>
    <form novalidate method="POST" action="{{ route('login') }}" class="panel mt-6 space-y-4 p-5">
        @csrf
        <x-errors />
        <div>
            <label for="email" class="mb-1.5 block font-semibold">E-Mail</label>
            <input id="email" name="email" @error('email') aria-invalid="true" @enderror type="email" value="{{ old('email') }}" autocomplete="email" class="field" required autofocus>
        </div>
        <div>
            <label for="password" class="mb-1.5 block font-semibold">Passwort</label>
            <input id="password" name="password" @error('password') aria-invalid="true" @enderror type="password" autocomplete="current-password" class="field" required>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" value="1" checked class="size-4 accent-[var(--glow)]">
            Angemeldet bleiben
        </label>
        <button class="btn btn-primary w-full">Anmelden</button>
    </form>
    <p class="mt-5 text-sm text-muted">Konten werden nur vom Betreiber angelegt. Zum Mitmachen oder um einen Raum zu erstellen, brauchst du keins.</p>
</x-layouts.app>

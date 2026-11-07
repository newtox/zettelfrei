<x-layouts.app title="Admin – Einstellungen" wide>
    @include('admin.nav')

    <x-errors />

    <section class="panel mt-6 max-w-xl p-5">
        <h2 class="text-xl">Aussehen der Seite</h2>
        <p class="mt-1 text-sm text-muted">Gilt für Startseite, Login, „Meine Räume“ und den Admin-Bereich. Räume haben ihr eigenes Aussehen, das beim Raum eingestellt wird. Wer auf dem eigenen Gerät ein Aussehen wählt, sieht überall dieses.</p>
        <form novalidate data-form-type="other" method="POST" action="{{ route('admin.settings.update') }}" class="mt-4 space-y-4">
            <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other"> <input type="hidden" name="_method" value="PUT" data-form-type="other">
            <x-theme-options name="site_theme" :selected="$siteTheme" />
            <button class="btn btn-primary btn-sm">Speichern</button>
        </form>
    </section>
</x-layouts.app>

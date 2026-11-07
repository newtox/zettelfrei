<x-layouts.app title="Admin – Konten" wide>
    @include('admin.nav')

    <p class="mt-4 max-w-2xl text-sm text-muted">Konten braucht man nur für eigene Kriterien-Vorlagen und um Räume geräteübergreifend unter „Meine Räume“ zu sehen. Super-Admins sehen und löschen alle Räume.</p>

    <x-errors />

    <div class="mt-6 grid gap-5 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <ul class="space-y-2">
            @foreach ($users as $u)
                <li>
                    <details class="panel group p-4">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold">
                                    {{ $u->name }}
                                    @if ($u->is_admin)
                                        <span class="ml-1 rounded-full bg-glow px-2 py-0.5 text-xs text-glow-ink">Super-Admin</span>
                                    @endif
                                </p>
                                <p class="truncate text-sm text-muted">{{ $u->email }}, {{ $u->rooms_count }} {{ $u->rooms_count === 1 ? 'Raum' : 'Räume' }}</p>
                            </div>
                            <span class="shrink-0 text-sm font-semibold text-muted group-open:hidden">Bearbeiten</span>
                        </summary>
                        <form novalidate data-form-type="other" method="POST" action="{{ route('admin.users.update', $u) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                            @csrf @method('PUT')
                            <input name="name" value="{{ $u->name }}" maxlength="60" autocomplete="off" data-form-type="other" class="field min-h-10 text-base" required aria-label="Name">
                            <input name="email" type="email" value="{{ $u->email }}" autocomplete="off" data-form-type="other" class="field min-h-10 text-base" required aria-label="E-Mail">
                            <input name="password" type="password" autocomplete="new-password" data-form-type="other" placeholder="Neues Passwort (leer lassen = unverändert)" class="field min-h-10 text-base sm:col-span-2">
                            @unless ($u->is(auth()->user()))
                                <label class="flex items-center gap-2 text-sm sm:col-span-2">
                                    <input type="checkbox" name="is_admin" value="1" @checked($u->is_admin) class="size-4 accent-[var(--glow)]">
                                    Super-Admin
                                </label>
                            @endunless
                            <div class="flex items-center gap-3 sm:col-span-2">
                                <button class="btn btn-primary btn-sm">Speichern</button>
                            </div>
                        </form>
                        @unless ($u->is(auth()->user()))
                            <form novalidate data-form-type="other" method="POST" action="{{ route('admin.users.destroy', $u) }}" class="mt-3" data-confirm="{{ 'Konto von '.$u->name.' löschen?' }}" data-confirm-action="Konto löschen" data-confirm-danger>
                                @csrf @method('DELETE')
                                <button class="text-sm font-semibold text-live">Konto löschen</button>
                            </form>
                        @endunless
                    </details>
                </li>
            @endforeach
        </ul>

        <section class="panel h-fit p-5">
            <h2 class="text-xl">Neues Konto</h2>
            <form novalidate data-form-type="other" method="POST" action="{{ route('admin.users.store') }}" class="mt-4 space-y-3">
                @csrf
                <input name="name" value="{{ old('name') }}" placeholder="Name" maxlength="60" autocomplete="off" data-form-type="other" class="field min-h-10 text-base" required>
                <input name="email" type="email" value="{{ old('email') }}" placeholder="E-Mail" autocomplete="off" data-form-type="other" class="field min-h-10 text-base" required>
                <input name="password" type="password" autocomplete="new-password" data-form-type="other" placeholder="Passwort (mind. 8 Zeichen)" class="field min-h-10 text-base" required>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_admin" value="1" class="size-4 accent-[var(--glow)]">
                    Super-Admin
                </label>
                <button class="btn btn-primary btn-sm">Konto anlegen</button>
            </form>
        </section>
    </div>
</x-layouts.app>

@props(['title' => null, 'theme' => null, 'room' => null, 'wide' => false])
<!DOCTYPE html>
@php($baseTheme = $theme ?? \App\Models\Setting::siteTheme())
<html lang="de" data-theme="{{ $baseTheme }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#12241c">
    <title>{{ $title ? $title.' – ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <script>try{var t=localStorage.getItem('zf-theme');if(t)document.documentElement.dataset.theme=t}catch(e){}</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh antialiased">
    <header class="mx-auto flex items-center justify-between gap-4 px-5 pt-5 {{ $wide ? 'max-w-6xl' : 'max-w-xl' }}">
         <a href="{{ route(auth()->check() ? 'dashboard' : 'home') }}" class="flex shrink-0 items-center gap-2.5 font-display text-lg">
            <svg viewBox="0 0 24 24" class="h-7 w-7" aria-hidden="true"><path d="M5 3h10l4 4v14H5z" fill="currentColor" class="text-glow"/><path d="M15 3v4h4" fill="none" stroke="currentColor" stroke-width="1.5" class="text-glow-ink" opacity=".35"/><path d="M8.5 13.2l2.4 2.4 4.6-5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="text-glow-ink"/></svg>
            {{ config('app.name') }}
        </a>
        @auth
            <nav class="hidden items-center gap-1 whitespace-nowrap text-sm sm:flex">
                <a href="{{ route('dashboard') }}" class="rounded-full px-3 py-2 hover:bg-raised">Meine Räume</a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.rooms') }}" class="rounded-full px-3 py-2 hover:bg-raised">Admin</a>
                @endif
                <form novalidate data-form-type="other" method="POST" action="{{ route('logout') }}">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                    <button class="rounded-full px-3 py-2 text-muted hover:bg-raised">Abmelden</button>
                </form>
            </nav>
            <details class="relative sm:hidden">
                <summary class="btn btn-quiet btn-sm list-none">Menü</summary>
                <div class="absolute right-0 z-30 mt-2 flex w-52 flex-col rounded-2xl bg-surface p-2 text-sm shadow-2xl ring-1 ring-line">
                    <a href="{{ route('dashboard') }}" class="rounded-xl px-3 py-2.5 hover:bg-raised">Meine Räume</a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.rooms') }}" class="rounded-xl px-3 py-2.5 hover:bg-raised">Admin</a>
                    @endif
                    <form novalidate data-form-type="other" method="POST" action="{{ route('logout') }}">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}" data-form-type="other">
                        <button class="w-full rounded-xl px-3 py-2.5 text-left text-muted hover:bg-raised">Abmelden</button>
                    </form>
                </div>
            </details>
        @endauth
    </header>

    <main class="mx-auto px-5 pb-16 pt-6 {{ $wide ? 'max-w-6xl' : 'max-w-xl' }}">
        @if (session('status'))
            <div role="status" class="mb-5 rounded-2xl bg-raised px-4 py-3 text-sm">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="mb-5 rounded-2xl bg-live px-4 py-3 text-sm text-live-ink">{{ session('error') }}</div>
        @endif

        {{ $slot }}
    </main>

    <dialog id="confirm-dialog" class="m-auto w-[min(26rem,calc(100vw-2.5rem))] rounded-3xl bg-surface p-6 text-ink shadow-2xl ring-1 ring-line backdrop:bg-black/60 backdrop:backdrop-blur-[2px]">
        <form method="dialog">
            <p data-confirm-text class="text-lg font-semibold leading-snug"></p>
            <div class="mt-6 flex flex-wrap justify-end gap-2">
                <button value="cancel" class="btn btn-quiet">Abbrechen</button>
                <button value="ok" data-confirm-ok class="btn btn-primary"></button>
            </div>
        </form>
    </dialog>

    <footer class="mx-auto flex flex-wrap items-center gap-x-3 gap-y-2 px-5 pb-8 text-sm text-muted {{ $wide ? 'max-w-6xl' : 'max-w-xl' }}" x-data="themePicker('{{ $baseTheme }}')">
        <span class="w-full sm:w-auto">Ansicht auf diesem Gerät:</span>
        <button type="button" @click="set(null)" class="-ml-1 rounded-full px-3 py-1 sm:ml-0" :class="current === null ? 'bg-raised text-ink' : 'hover:text-ink'">Standard</button>
        @foreach (\App\Models\Room::THEMES as $key => $label)
            <button type="button" @click="set('{{ $key }}')" class="-ml-1 rounded-full px-3 py-1 sm:ml-0" :class="current === '{{ $key }}' ? 'bg-raised text-ink' : 'hover:text-ink'">{{ $label }}</button>
        @endforeach
    </footer>
</body>
</html>

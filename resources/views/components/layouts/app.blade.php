@props(['title' => null, 'theme' => null, 'room' => null, 'wide' => false])
<!DOCTYPE html>
<html lang="de" data-theme="{{ $theme ?? config('zettelfrei.default_theme') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#12241c">
    <title>{{ $title ? $title.' – ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @if ($room)
        <script>try{var t=localStorage.getItem('br-theme-{{ $room->code }}');if(t)document.documentElement.dataset.theme=t}catch(e){}</script>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh antialiased">
    <header class="mx-auto flex items-center justify-between gap-4 px-5 pt-5 {{ $wide ? 'max-w-6xl' : 'max-w-xl' }}">
         <a href="{{ route(auth()->check() ? 'dashboard' : 'home') }}" class="flex items-center gap-2.5 font-display text-lg">
            <svg viewBox="0 0 24 24" class="h-7 w-7" aria-hidden="true"><path d="M5 3h10l4 4v14H5z" fill="currentColor" class="text-glow"/><path d="M15 3v4h4" fill="none" stroke="currentColor" stroke-width="1.5" class="text-glow-ink" opacity=".35"/><path d="M8.5 13.2l2.4 2.4 4.6-5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="text-glow-ink"/></svg>
            {{ config('app.name') }}
        </a>
        <nav class="flex items-center gap-1 text-sm">
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-full px-3 py-2 hover:bg-raised">Meine Räume</a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.rooms') }}" class="rounded-full px-3 py-2 hover:bg-raised">Admin</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-full px-3 py-2 text-muted hover:bg-raised">Abmelden</button>
                </form>
            @endauth
        </nav>
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

    @if ($room)
        <footer class="mx-auto flex flex-wrap items-center gap-x-3 gap-y-2 px-5 pb-8 text-sm text-muted {{ $wide ? 'max-w-6xl' : 'max-w-xl' }}" x-data="themePicker('{{ $room->code }}', '{{ $room->theme }}')">
            <span class="w-full sm:w-auto">Ansicht auf diesem Gerät:</span>
            @foreach (\App\Models\Room::THEMES as $key => $label)
                <button type="button" @click="set('{{ $key }}')" class="-ml-1 rounded-full px-3 py-1 sm:ml-0" :class="current === '{{ $key }}' ? 'bg-raised text-ink' : 'hover:text-ink'">{{ $label }}</button>
            @endforeach
        </footer>
    @endif
</body>
</html>

<div class="flex flex-wrap items-end justify-between gap-4">
    <h1 class="text-3xl sm:text-4xl">Admin</h1>
    <nav class="flex gap-1 rounded-full bg-surface p-1 text-sm font-semibold">
        <a href="{{ route('admin.rooms') }}" @class(['rounded-full px-4 py-2', 'bg-glow text-glow-ink' => request()->routeIs('admin.rooms'), 'hover:bg-raised' => ! request()->routeIs('admin.rooms')])>Räume</a>
        <a href="{{ route('admin.users') }}" @class(['rounded-full px-4 py-2', 'bg-glow text-glow-ink' => request()->routeIs('admin.users'), 'hover:bg-raised' => ! request()->routeIs('admin.users')])>Konten</a>
        <a href="{{ route('admin.settings') }}" @class(['rounded-full px-4 py-2', 'bg-glow text-glow-ink' => request()->routeIs('admin.settings'), 'hover:bg-raised' => ! request()->routeIs('admin.settings')])>Einstellungen</a>
    </nav>
</div>

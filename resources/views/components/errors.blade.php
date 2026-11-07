@if ($errors->any())
    @php $messages = collect($errors->all())->unique(fn ($m) => mb_strtolower($m))->values(); @endphp
    <div role="alert" class="mb-5 rounded-2xl bg-live px-4 py-3 text-sm text-live-ink">
        @if ($messages->count() === 1)
            {{ $messages->first() }}
        @else
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($messages as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif

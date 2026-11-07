@if ($errors->any())
    <div role="alert" class="mb-5 rounded-2xl bg-live px-4 py-3 text-sm text-live-ink">
        @if ($errors->count() === 1)
            {{ $errors->first() }}
        @else
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif

@props(['question' => null, 'none' => null, 'scoped' => false])
@php
    $options = \App\Models\Room::OWN_QUESTIONS;
@endphp
<div @if ($scoped) x-data="{ own_question: @js($question ?? $options['generic']['question']), own_none_label: @js($none ?? $options['generic']['none']) }" @endif {{ $attributes->merge(['class' => 'space-y-3']) }}>
    <div>
        <p class="mb-1.5 text-sm font-semibold">Frage beim Beitritt</p>
        <div class="flex flex-wrap gap-1.5">
            @foreach ($options as $option)
                <button type="button" @click="own_question = @js($option['question']); own_none_label = @js($option['none'])" class="rounded-full px-3 py-1.5 text-sm ring-1 ring-inset transition-shadow" :class="own_question === @js($option['question']) ? 'ring-2 ring-glow' : 'ring-line hover:ring-muted'">{{ $option['question'] }}</button>
            @endforeach
        </div>
    </div>
    <div class="grid gap-2 sm:grid-cols-[1fr_14rem] sm:items-end">
        <div>
            <label for="own_question" class="mb-1 block text-sm text-muted">Frage</label>
            <input data-form-type="other" id="own_question" name="own_question" x-model="own_question" maxlength="120" class="field min-h-10 text-base">
        </div>
        <div>
            <label for="own_none_label" class="mb-1 block text-sm text-muted">Antwort, wenn keiner passt</label>
            <input data-form-type="other" id="own_none_label" name="own_none_label" x-model="own_none_label" maxlength="40" class="field min-h-10 text-base">
        </div>
    </div>
</div>

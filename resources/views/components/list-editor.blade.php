@props(['name', 'items' => [], 'placeholder' => '', 'addLabel' => 'Hinzufügen'])
<div x-data="listEditor(@js(array_values($items)), @js($placeholder))" {{ $attributes }}>
    <ul class="space-y-2">
        <template x-for="(item, i) in items" :key="item.id">
            <li class="flex items-center gap-1.5">
                <input data-form-type="other" data-item type="text" name="{{ $name }}[]" x-model="item.v" :placeholder="placeholder" maxlength="60" class="field" @keydown.enter.prevent="onEnter(i)">
                <button type="button" @click="move(i, -1)" :disabled="i === 0" class="grid size-10 shrink-0 place-items-center rounded-full text-muted hover:bg-raised disabled:opacity-30" aria-label="Nach oben">
                    <svg viewBox="0 0 20 20" class="size-4" fill="currentColor"><path d="M10 5l6 7H4z"/></svg>
                </button>
                <button type="button" @click="move(i, 1)" :disabled="i === items.length - 1" class="grid size-10 shrink-0 place-items-center rounded-full text-muted hover:bg-raised disabled:opacity-30" aria-label="Nach unten">
                    <svg viewBox="0 0 20 20" class="size-4" fill="currentColor"><path d="M10 15l6-7H4z"/></svg>
                </button>
                <button type="button" @click="remove(i)" class="grid size-10 shrink-0 place-items-center rounded-full text-muted hover:bg-raised hover:text-live" aria-label="Entfernen">
                    <svg viewBox="0 0 20 20" class="size-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 5l10 10M15 5L5 15"/></svg>
                </button>
            </li>
        </template>
    </ul>
    <button type="button" @click="add()" class="btn btn-quiet btn-sm mt-3">{{ $addLabel }}</button>
</div>

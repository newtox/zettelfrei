<?php

namespace App\Http\Controllers;

use App\Models\Preset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PresetController extends Controller
{
    public function index(Request $request): View
    {
        return view('presets.index', [
            'presets' => Preset::availableFor($request->user())->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $request->user()->presets()->create($data);

        return back()->with('status', 'Vorlage „'.$data['name'].'“ gespeichert.');
    }

    public function update(Request $request, Preset $preset): RedirectResponse
    {
        abort_unless($preset->user_id === $request->user()->id, 403);

        $preset->update($this->validated($request));

        return back()->with('status', 'Vorlage aktualisiert.');
    }

    public function destroy(Request $request, Preset $preset): RedirectResponse
    {
        abort_unless($preset->user_id === $request->user()->id, 403);

        $preset->delete();

        return back()->with('status', 'Vorlage gelöscht.');
    }

    private function validated(Request $request): array
    {
        $request->merge([
            'criteria' => collect($request->input('criteria', []))->map(fn ($c) => trim((string) $c))->filter()->values()->all(),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'criteria' => ['required', 'array', 'min:1', 'max:15'],
            'criteria.*' => ['string', 'max:60', 'distinct'],
        ], [
            'criteria.required' => 'Mindestens ein Kriterium wird benötigt.',
            'criteria.*.distinct' => 'Jedes Kriterium darf nur einmal vorkommen.',
        ]);
    }
}

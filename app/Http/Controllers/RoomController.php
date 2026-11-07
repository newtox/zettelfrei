<?php

namespace App\Http\Controllers;

use App\Models\Preset;
use App\Models\Room;
use App\Models\Station;
use App\Support\RoomAccess;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function create(Request $request): View
    {
        return view('rooms.create', [
            'presets' => Preset::availableFor($request->user())->get(),
            'templates' => Room::TEMPLATES,
            'canSavePreset' => (bool) $request->user(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'criteria' => $this->cleanList($request->input('criteria', [])),
            'stations' => $this->cleanList($request->input('stations', [])),
        ]);

        $isScore = $request->input('mode', Room::SCORE) !== Room::CHOICE;

        $data = $request->validate(array_merge($this->settingsRules(), [
            'name' => ['required', 'string', 'max:80'],
            'theme' => ['required', Rule::in(array_keys(Room::THEMES))],
            'mode' => ['required', Rule::in(array_keys(Room::MODES))],
            'criteria' => $isScore ? ['required', 'array', 'min:1', 'max:15'] : ['nullable', 'array'],
            'criteria.*' => ['string', 'max:60', 'distinct:ignore_case'],
            'stations' => ['required', 'array', $isScore ? 'min:1' : 'min:2', 'max:40'],
            'stations.*' => ['string', 'max:60', 'distinct:ignore_case'],
            'save_preset' => ['nullable', 'boolean'],
            'preset_name' => ['nullable', Rule::requiredIf($isScore && $request->user() && $request->boolean('save_preset')), 'string', 'max:60'],
        ]), $this->messages());

        $room = DB::transaction(function () use ($request, $data, $isScore) {
            $room = new Room($this->settingsFrom($request, $data) + [
                'name' => $data['name'],
                'theme' => $data['theme'],
                'mode' => $data['mode'],
            ]);
            $room->user_id = $request->user()?->id;
            $room->save();

            if ($isScore) {
                foreach ($data['criteria'] as $i => $name) {
                    $room->criteria()->create(['name' => $name, 'position' => $i]);
                }
            }

            foreach ($data['stations'] as $i => $name) {
                $room->stations()->create(['name' => $name, 'position' => $i]);
            }

            if ($isScore && $request->user() && $request->boolean('save_preset')) {
                $request->user()->presets()->create([
                    'name' => $data['preset_name'],
                    'criteria' => $data['criteria'],
                ]);
            }

            return $room;
        });

        RoomAccess::grant($room);

        return redirect()->route('rooms.manage', $room)->with('created', true);
    }

    public function manage(Request $request, Room $room): View
    {
        $this->authorizeOwner($request, $room);

        $room->load(['criteria', 'stations' => fn ($q) => $q->withCount(['ballots', 'photos']), 'participants.station']);

        return view('rooms.manage', [
            'room' => $room,
            'locked' => $room->hasBallots(),
        ]);
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeOwner($request, $room);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:80'],
            'theme' => ['sometimes', 'required', Rule::in(array_keys(Room::THEMES))],
            'join_locked' => ['sometimes', 'boolean'],
        ]);

        $room->update($data);

        return back()->with('status', 'Gespeichert.');
    }

    public function updateSettings(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeOwner($request, $room);

        $data = $request->validate($this->settingsRules(), $this->messages());

        $room->update($this->settingsFrom($request, $data));

        if ($room->isScore() && $room->one_at_a_time && $room->stations()->where('status', Station::OPEN)->count() > 1) {
            $keep = $room->stations()->where('status', Station::OPEN)->first();
            $room->stations()->where('status', Station::OPEN)->where('id', '!=', $keep->id)->update(['status' => Station::CLOSED]);
        }

        return back()->with('status', 'Einstellungen gespeichert.');
    }

    public function destroy(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeOwner($request, $room);

        $room->deletePhotoFiles();
        $room->delete();
        RoomAccess::revoke($room);

        return redirect()->route($request->user() ? 'dashboard' : 'home')->with('status', 'Raum gelöscht.');
    }

    public function claim(Request $request, Room $room, string $token): RedirectResponse
    {
        abort_unless(hash_equals((string) $room->admin_token, $token), 404);

        RoomAccess::grant($room);

        return redirect()->route('rooms.manage', $room)->with('status', 'Du verwaltest jetzt diesen Raum auf diesem Gerät.');
    }

    public function regenerateToken(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeOwner($request, $room);

        $room->admin_token = Str::random(40);
        $room->save();
        RoomAccess::grant($room);

        return back()->with('status', 'Neuer Admin-Link erstellt. Der alte funktioniert nicht mehr, andere Geräte mit dem alten Link verlieren den Zugriff.');
    }

    public function addStation(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeOwner($request, $room);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('stations')->where('room_id', $room->id)],
        ], ['name.unique' => 'Diesen Eintrag gibt es schon.', 'name.required' => 'Trag einen Namen für den neuen Eintrag ein.']);

        $room->stations()->create([
            'name' => trim($data['name']),
            'position' => (int) $room->stations()->max('position') + 1,
        ]);

        return back()->with('status', $room->item_label.' hinzugefügt.');
    }

    public function updateStation(Request $request, Room $room, Station $station): RedirectResponse
    {
        $this->authorizeOwner($request, $room);
        $this->assertBelongs($room, $station);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('stations')->where('room_id', $room->id)->ignore($station->id)],
        ], ['name.unique' => 'Diesen Eintrag gibt es schon.', 'name.required' => 'Der Eintrag braucht einen Namen.']);

        $station->update(['name' => trim($data['name'])]);

        return back()->with('status', 'Umbenannt.');
    }

    public function destroyStation(Request $request, Room $room, Station $station): RedirectResponse
    {
        $this->authorizeOwner($request, $room);
        $this->assertBelongs($room, $station);

        $paths = $station->photos()->pluck('path')->all();
        $station->delete();
        Storage::disk('local')->delete($paths);

        return back()->with('status', $room->item_label.' entfernt.');
    }

    public function moveStation(Request $request, Room $room, Station $station, string $direction): RedirectResponse
    {
        $this->authorizeOwner($request, $room);
        $this->assertBelongs($room, $station);

        $this->move($room->stations()->get(), $station->id, $direction);

        return back();
    }

    public function openStation(Request $request, Room $room, Station $station): RedirectResponse
    {
        $this->authorizeOwner($request, $room);
        $this->assertBelongs($room, $station);

        abort_if($room->isChoice() || $room->isRevealing(), 422);

        DB::transaction(function () use ($room, $station) {
            if ($room->one_at_a_time) {
                $room->stations()->where('status', Station::OPEN)->where('id', '!=', $station->id)->update(['status' => Station::CLOSED]);
            }
            $station->update(['status' => Station::OPEN]);
        });

        return back()->with('status', '„'.$station->name.'“ ist jetzt zur Bewertung offen.');
    }

    public function openAll(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeOwner($request, $room);

        abort_if($room->isRevealing(), 422);

        if ($room->isChoice()) {
            $room->update(['voting_open' => true]);

            return back()->with('status', 'Die Abstimmung ist offen.');
        }

        abort_if($room->one_at_a_time, 422);

        $room->stations()->update(['status' => Station::OPEN]);

        return back()->with('status', 'Alle '.$room->items_label.' sind zur Bewertung offen.');
    }

    public function closeAll(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeOwner($request, $room);

        $room->update(['voting_open' => false]);
        $room->stations()->where('status', Station::OPEN)->update(['status' => Station::CLOSED]);

        return back()->with('status', 'Abstimmung geschlossen.');
    }

    public function closeStation(Request $request, Room $room, Station $station): RedirectResponse
    {
        $this->authorizeOwner($request, $room);
        $this->assertBelongs($room, $station);

        $station->update(['status' => Station::CLOSED]);

        return back()->with('status', 'Bewertung für „'.$station->name.'“ geschlossen.');
    }

    public function addCriterion(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeOwner($request, $room);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('criteria')->where('room_id', $room->id)],
        ], ['name.unique' => 'Dieses Kriterium gibt es schon.', 'name.required' => 'Trag einen Namen für das neue Kriterium ein.']);

        $room->criteria()->create([
            'name' => trim($data['name']),
            'position' => (int) $room->criteria()->max('position') + 1,
        ]);

        return back()->with('status', 'Kriterium hinzugefügt.');
    }

    public function destroyCriterion(Request $request, Room $room, int $criterion): RedirectResponse
    {
        $this->authorizeOwner($request, $room);

        abort_if($room->criteria()->count() <= 1, 422, 'Mindestens ein Kriterium muss bleiben.');

        $room->criteria()->whereKey($criterion)->firstOrFail()->delete();

        return back()->with('status', 'Kriterium entfernt.');
    }

    public function moveCriterion(Request $request, Room $room, int $criterion, string $direction): RedirectResponse
    {
        $this->authorizeOwner($request, $room);

        $this->move($room->criteria()->get(), $criterion, $direction);

        return back();
    }

    public function destroyParticipant(Request $request, Room $room, int $participant): RedirectResponse
    {
        $this->authorizeOwner($request, $room);

        $room->participants()->whereKey($participant)->firstOrFail()->delete();

        return back()->with('status', 'Teilnehmer entfernt. Bereits abgegebene Bewertungen bleiben anonym erhalten.');
    }

    public function reveal(Request $request, Room $room): RedirectResponse
    {
        $this->authorizeOwner($request, $room);

        $data = $request->validate(['action' => ['required', Rule::in(['next', 'all', 'hide'])]]);

        $count = $room->stations()->count();

        if ($data['action'] !== 'hide') {
            $room->stations()->where('status', Station::OPEN)->update(['status' => Station::CLOSED]);
            $room->voting_open = false;
        }

        $room->reveal_step = match ($data['action']) {
            'next' => min($room->reveal_step + 1, $count),
            'all' => $count,
            'hide' => 0,
        };
        $room->save();

        return $data['action'] === 'hide'
            ? back()->with('status', 'Ergebnisse wieder verborgen.')
            : redirect()->route('play.results', $room);
    }

    public function state(Request $request, Room $room): JsonResponse
    {
        $this->authorizeOwner($request, $room);

        $room->load('stations');

        return response()->json([
            'participants' => $room->participants()->count(),
            'voters' => $room->isChoice() ? $room->voterCount() : null,
            'stations' => $room->stations->map(fn (Station $s) => [
                'id' => $s->id,
                'status' => $s->status,
                'votes' => $room->isChoice() ? null : $s->ballots()->count(),
                'eligible' => $s->eligibleVoterCount(),
                'photos' => $s->photos()->count(),
            ]),
        ]);
    }

    private function settingsRules(): array
    {
        return [
            'item_label' => ['required', 'string', 'max:30'],
            'items_label' => ['required', 'string', 'max:30'],
            'exclude_own' => ['nullable', 'boolean'],
            'photos_enabled' => ['nullable', 'boolean'],
            'one_at_a_time' => ['nullable', 'boolean'],
            'max_choices' => ['nullable', 'integer', 'between:1,10'],
        ];
    }

    private function settingsFrom(Request $request, array $data): array
    {
        return [
            'item_label' => trim($data['item_label']),
            'items_label' => trim($data['items_label']),
            'exclude_own' => $request->boolean('exclude_own'),
            'photos_enabled' => $request->boolean('photos_enabled'),
            'one_at_a_time' => $request->boolean('one_at_a_time'),
            'max_choices' => (int) ($data['max_choices'] ?? 1),
        ];
    }

    private function messages(): array
    {
        return [
            'criteria.required' => 'Mindestens ein Kriterium wird benötigt.',
            'stations.required' => 'Trag mindestens einen Eintrag ein.',
            'stations.min' => 'Für eine Umfrage braucht es mindestens zwei Optionen.',
            'criteria.*.distinct' => 'Das Kriterium „:input“ steht mehrfach in der Liste.',
            'stations.*.distinct' => '„:input“ steht mehrfach in der Liste.',
            'preset_name.required' => 'Gib der Vorlage einen Namen.',
            'name.required' => 'Gib dem Raum einen Namen.',
            'item_label.required' => 'Wie heißt ein einzelner Eintrag?',
            'items_label.required' => 'Wie heißen mehrere Einträge?',
        ];
    }

    private function authorizeOwner(Request $request, Room $room): void
    {
        abort_unless(RoomAccess::canManage($request, $room), 403);

        if (! $request->isMethodSafe()) {
            $room->touchActivity();
        }
    }

    private function assertBelongs(Room $room, Station $station): void
    {
        abort_unless($station->room_id === $room->id, 404);
    }

    private function move($items, int $id, string $direction): void
    {
        $items = $items->values();
        $index = $items->search(fn ($i) => $i->id === $id);

        abort_if($index === false, 404);

        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($target < 0 || $target >= $items->count()) {
            return;
        }

        $ordered = $items->all();
        [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];

        foreach ($ordered as $position => $item) {
            $item->update(['position' => $position]);
        }
    }

    private function cleanList(mixed $list): array
    {
        return collect(is_array($list) ? $list : [])
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->values()
            ->all();
    }
}

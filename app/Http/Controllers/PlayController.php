<?php

namespace App\Http\Controllers;

use App\Models\Ballot;
use App\Models\Participant;
use App\Models\Room;
use App\Models\Station;
use App\Support\ParticipantSession;
use App\Support\RoomAccess;
use App\Support\Results;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlayController extends Controller
{
    public function find(Request $request): RedirectResponse
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $request->input('code')));

        $room = Room::where('code', $code)->first();

        if (! $room) {
            return back()->withErrors(['code' => 'Diesen Raum gibt es nicht. Prüf den Code nochmal.'])->withInput();
        }

        return redirect()->route('play.show', $room);
    }

    public function show(Request $request, Room $room): View|RedirectResponse
    {
        $participant = ParticipantSession::current($request, $room);
        $room->load(['criteria', 'stations']);

        if (! $participant) {
            return view('play.join', ['room' => $room]);
        }

        if ($room->isRevealing()) {
            return redirect()->route('play.results', $room);
        }

        if ($room->isChoice()) {
            $room->load('stations.photos');

            return view('play.choice', [
                'room' => $room,
                'participant' => $participant,
                'picked' => $room->choices()->where('voter_hash', $room->voterHash($participant))->pluck('station_id')->all(),
                'stateKey' => $this->stateKey($room),
            ]);
        }

        $open = $room->openStations();
        $votedStations = $this->votedStationIds($room, $participant);
        $station = $open->firstWhere('id', (int) $request->query('station'))
            ?? $open->first(fn ($s) => $participant->canRate($s) && ! in_array($s->id, $votedStations))
            ?? $open->first();
        $ballot = null;

        if ($station && $participant->canRate($station)) {
            $ballot = Ballot::with('ratings')
                ->where('station_id', $station->id)
                ->where('voter_hash', $participant->voterHashFor($station))
                ->first();
        }

        return view('play.show', [
            'room' => $room,
            'participant' => $participant,
            'station' => $station?->load('photos'),
            'ballot' => $ballot,
            'scores' => $ballot ? $ballot->ratings->pluck('score', 'criterion_id') : collect(),
            'votedStations' => $votedStations,
            'openStations' => $open,
            'stateKey' => $this->stateKey($room),
        ]);
    }

    public function join(Request $request, Room $room): RedirectResponse
    {
        if (ParticipantSession::current($request, $room)) {
            return redirect()->route('play.show', $room);
        }

        if ($room->join_locked) {
            return back()->withErrors(['name' => 'Der Raum ist für neue Teilnehmer gesperrt.']);
        }

        $request->merge(['name' => trim(preg_replace('/\s+/', ' ', (string) $request->input('name')))]);

        if (! $room->exclude_own) {
            $request->merge(['station_id' => null]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:40', Rule::unique('participants')->where('room_id', $room->id)],
            'station_id' => ['nullable', 'integer', Rule::exists('stations', 'id')->where('room_id', $room->id)],
        ], [
            'name.required' => 'Wie heißt du?',
            'name.unique' => 'Diesen Namen gibt es in diesem Raum schon. Nimm z. B. noch den Anfangsbuchstaben deines Nachnamens dazu.',
        ]);

        ParticipantSession::create($room, $data['name'], $data['station_id'] ?? null);
        $room->touchActivity();

        return redirect()->route('play.show', $room);
    }

    public function leave(Request $request, Room $room): RedirectResponse
    {
        $participant = ParticipantSession::current($request, $room);

        if ($participant) {
            $participant->delete();
        }

        ParticipantSession::forget($room);

        return redirect()->route('play.show', $room);
    }

    public function vote(Request $request, Room $room, Station $station): RedirectResponse
    {
        abort_unless($station->room_id === $room->id, 404);

        $participant = ParticipantSession::current($request, $room);

        if (! $participant) {
            return redirect()->route('play.show', $room);
        }

        abort_if($room->isChoice(), 404);

        if (! $station->isOpen() || $room->isRevealing()) {
            return redirect()->route('play.show', $room)->with('error', 'Für „'.$station->name.'“ ist die Bewertung schon geschlossen.');
        }

        abort_unless($participant->canRate($station), 403, 'Deinen eigenen Eintrag bewerten die anderen.');

        $criteria = $room->criteria()->pluck('id');

        $rules = [];
        foreach ($criteria as $id) {
            $rules['scores.'.$id] = ['required', 'integer', 'between:1,10'];
        }

        $data = $request->validate($rules, [
            'scores.*.required' => 'Bitte vergib für jedes Kriterium Punkte.',
            'scores.*.between' => 'Punkte gehen von 1 bis 10.',
        ]);

        DB::transaction(function () use ($station, $participant, $criteria, $data) {
            $ballot = Ballot::firstOrCreate([
                'station_id' => $station->id,
                'voter_hash' => $participant->voterHashFor($station),
            ]);

            foreach ($criteria as $id) {
                $ballot->ratings()->updateOrCreate(
                    ['criterion_id' => $id],
                    ['score' => (int) $data['scores'][$id]],
                );
            }

            $ballot->touch();
        });

        $room->touchActivity();

        $next = $room->stations()->where('status', Station::OPEN)->get()
            ->first(fn ($s) => $s->id !== $station->id && $participant->canRate($s)
                && ! Ballot::where('station_id', $s->id)->where('voter_hash', $participant->voterHashFor($s))->exists());

        return redirect()->route('play.show', $next ? [$room, 'station' => $next->id] : $room)
            ->with('status', 'Danke! Deine Bewertung für „'.$station->name.'“ ist gespeichert. Solange sie offen ist, kannst du sie noch ändern.');
    }

    public function choose(Request $request, Room $room): RedirectResponse
    {
        abort_unless($room->isChoice(), 404);

        $participant = ParticipantSession::current($request, $room);

        if (! $participant) {
            return redirect()->route('play.show', $room);
        }

        if (! $room->voting_open || $room->isRevealing()) {
            return redirect()->route('play.show', $room)->with('error', 'Die Abstimmung ist gerade nicht offen.');
        }

        $allowed = $room->stations()->pluck('id')
            ->reject(fn ($id) => $room->exclude_own && $participant->station_id === $id)
            ->values()
            ->all();

        $data = $request->validate([
            'options' => ['required', 'array', 'min:1', 'max:'.$room->max_choices],
            'options.*' => ['integer', 'distinct', Rule::in($allowed)],
        ], [
            'options.required' => 'Triff zuerst eine Auswahl.',
            'options.max' => 'Du kannst höchstens '.$room->max_choices.' auswählen.',
            'options.*.in' => 'Diese Auswahl ist nicht möglich.',
        ]);

        $hash = $room->voterHash($participant);

        DB::transaction(function () use ($room, $hash, $data) {
            $room->choices()->where('voter_hash', $hash)->delete();

            foreach ($data['options'] as $stationId) {
                $room->choices()->create(['station_id' => $stationId, 'voter_hash' => $hash]);
            }
        });

        $room->touchActivity();

        return redirect()->route('play.show', $room)->with('status', 'Danke! Deine Stimme ist gespeichert. Solange die Abstimmung offen ist, kannst du sie noch ändern.');
    }

    public function state(Request $request, Room $room): JsonResponse
    {
        $participant = ParticipantSession::current($request, $room);
        $isOwner = RoomAccess::canManage($request, $room);

        abort_unless($participant || $isOwner, 403);

        $room->load('stations');
        $station = $room->stations->firstWhere('id', (int) $request->query('station')) ?? $room->activeStation();

        return response()->json([
            'key' => $this->stateKey($room),
            'reveal' => $room->reveal_step,
            'voters' => $room->isChoice() ? $room->voterCount() : null,
            'eligible' => $room->isChoice() ? $room->eligibleChoiceVoters() : null,
            'station' => $station && $room->isScore() ? [
                'id' => $station->id,
                'votes' => $station->ballots()->count(),
                'eligible' => $station->eligibleVoterCount(),
                'photos' => $station->photos()->count(),
            ] : null,
        ]);
    }

    public function results(Request $request, Room $room): View|RedirectResponse
    {
        $participant = ParticipantSession::current($request, $room);
        $isOwner = RoomAccess::canManage($request, $room);

        if (! $participant && ! $isOwner) {
            return redirect()->route('play.show', $room);
        }

        if (! $room->isRevealing()) {
            return $isOwner ? redirect()->route('rooms.manage', $room) : redirect()->route('play.show', $room);
        }

        $results = Results::for($room);
        $count = $results->count();
        $visibleFrom = $count - $room->reveal_step;

        $room->stations->load('photos');

        return view('play.results', [
            'room' => $room,
            'participant' => $participant,
            'isOwner' => $isOwner,
            'results' => $results->map(fn ($row, $i) => $row + ['visible' => $i >= $visibleFrom, 'fresh' => $i === $visibleFrom]),
            'full' => $room->isFullyRevealed(),
            'winners' => $room->isFullyRevealed() ? Results::categoryWinners($room, $results) : collect(),
            'stateKey' => $this->stateKey($room),
        ]);
    }

    public function gallery(Request $request, Room $room): View|RedirectResponse
    {
        $participant = ParticipantSession::current($request, $room);
        $isOwner = RoomAccess::canManage($request, $room);

        if (! $participant && ! $isOwner) {
            return redirect()->route('play.show', $room);
        }

        abort_unless($room->photos_enabled || $room->photos()->exists(), 404);

        $room->load(['stations.photos']);

        return view('play.gallery', [
            'room' => $room,
            'participant' => $participant,
            'isOwner' => $isOwner,
        ]);
    }

    private function votedStationIds(Room $room, Participant $participant): array
    {
        $hashes = $room->stations->mapWithKeys(fn ($s) => [$participant->voterHashFor($s) => $s->id]);

        return Ballot::whereIn('voter_hash', $hashes->keys())
            ->pluck('voter_hash')
            ->map(fn ($h) => $hashes[$h])
            ->all();
    }

    private function stateKey(Room $room): string
    {
        return md5($room->reveal_step.'|'.(int) $room->voting_open.'|'.$room->mode.'|'.$room->stations->map(fn ($s) => $s->id.':'.$s->status)->join(','));
    }
}

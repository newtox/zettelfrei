<?php

namespace App\Support;

use App\Models\Ballot;
use App\Models\Participant;
use App\Models\Preset;
use App\Models\Room;
use App\Models\Station;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoRoom
{
    public const RUNNING = 'running';

    public const READY = 'ready';

    private const PEOPLE = ['Anna', 'Ben', 'Clara', 'David', 'Emma', 'Felix', 'Greta', 'Hannes'];

    private const DATA = [
        'trees' => [
            'name' => 'Demo: Baumbewertung',
            'entries' => ['Familie Müller', 'Familie Schmidt', 'Oma & Opa', 'Familie Weber', 'Tante Gabi'],
            'preset' => 'Weihnachtsbaum',
            'members' => ['Anna' => 0, 'Ben' => 0, 'Clara' => 1, 'David' => 1, 'Emma' => 3, 'Felix' => 3, 'Greta' => 4],
        ],
        'contest' => [
            'name' => 'Demo: Plätzchen-Wettbewerb',
            'entries' => ['Zimtsterne', 'Vanillekipferl', 'Spritzgebäck', 'Lebkuchen', 'Kokosmakronen'],
            'preset' => 'Essen & Backen',
            'members' => ['Anna' => 0, 'Ben' => 1, 'Clara' => 2, 'David' => 3, 'Emma' => 4],
        ],
        'poll' => [
            'name' => 'Demo: Wohin gehen wir essen?',
            'entries' => ['Italiener', 'Grieche', 'Burgerladen', 'Sushi', 'Mexikaner'],
            'preset' => null,
            'members' => [],
        ],
    ];

    public static function create(User $owner, string $template, string $state): Room
    {
        $data = self::DATA[$template];
        $settings = Room::TEMPLATES[$template];

        return DB::transaction(function () use ($owner, $template, $state, $data, $settings) {
            $room = new Room([
                'name' => $data['name'],
                'theme' => $settings['theme'],
                'mode' => $settings['mode'],
                'rating_style' => $settings['rating_style'],
                'item_label' => $settings['item_label'],
                'items_label' => $settings['items_label'],
                'exclude_own' => $settings['exclude_own'],
                'photos_enabled' => $settings['photos_enabled'],
                'one_at_a_time' => $settings['one_at_a_time'],
                'max_choices' => $settings['max_choices'],
            ]);
            $room->user_id = $owner->id;
            $room->save();

            if ($data['preset']) {
                $criteria = Preset::whereNull('user_id')->where('name', $data['preset'])->value('criteria')
                    ?? ['Gesamteindruck'];
                foreach ($criteria as $i => $name) {
                    $room->criteria()->create(['name' => $name, 'position' => $i]);
                }
            }

            $stations = collect($data['entries'])->map(fn ($name, $i) => $room->stations()->create(['name' => $name, 'position' => $i]));

            $participants = collect(self::PEOPLE)->map(function ($name) use ($room, $data, $stations) {
                $index = $data['members'][$name] ?? null;

                return self::participant($room, $name, $index === null ? null : $stations[$index]->id);
            });

            $room->load('criteria');

            if ($room->isChoice()) {
                self::fillChoices($room, $stations, $participants, $state);
            } else {
                self::fillScores($room, $stations, $participants, $state, $template);
            }

            return $room;
        });
    }

    private static function participant(Room $room, string $name, ?int $stationId): Participant
    {
        $token = Str::random(48);

        $participant = $room->participants()->create([
            'name' => $name,
            'station_id' => $stationId,
            'token_hash' => hash('sha256', $token),
        ]);
        $participant->plainToken = $token;

        return $participant;
    }

    private static function fillScores(Room $room, $stations, $participants, string $state, string $template): void
    {
        $quality = $stations->mapWithKeys(fn ($s) => [$s->id => random_int(45, 90) / 10]);
        $last = $stations->count() - 1;

        foreach ($stations as $i => $station) {
            $running = $state === self::RUNNING;
            $isCurrent = $running && ($room->one_at_a_time ? $i === $last : true);

            $station->update(['status' => $isCurrent ? Station::OPEN : Station::CLOSED]);

            $voters = $participants->filter(fn ($p) => $p->canRate($station))->values();

            if ($isCurrent) {
                $voters = $voters->take((int) ceil($voters->count() * ($room->one_at_a_time ? 0.5 : 0.6)));
            }

            foreach ($voters as $participant) {
                $ballot = Ballot::create([
                    'station_id' => $station->id,
                    'voter_hash' => $participant->voterHashFor($station),
                ]);

                foreach ($room->criteria as $criterion) {
                    $score = (int) round($quality[$station->id] + random_int(-18, 18) / 10);
                    $ballot->ratings()->create([
                        'criterion_id' => $criterion->id,
                        'score' => max(1, min(10, $score)),
                    ]);
                }
            }
        }
    }

    private static function fillChoices(Room $room, $stations, $participants, string $state): void
    {
        $weights = $stations->mapWithKeys(fn ($s, $i) => [$s->id => [6, 3, 4, 2, 1][$i] ?? 1]);
        $voters = $state === self::RUNNING ? $participants->take(5) : $participants;

        foreach ($voters as $participant) {
            $pick = self::weightedPick($weights->all());
            $room->choices()->create([
                'station_id' => $pick,
                'voter_hash' => $room->voterHash($participant),
            ]);
        }

        $room->update(['voting_open' => $state === self::RUNNING]);
    }

    private static function weightedPick(array $weights): int
    {
        $roll = random_int(1, array_sum($weights));

        foreach ($weights as $id => $weight) {
            $roll -= $weight;
            if ($roll <= 0) {
                return $id;
            }
        }

        return array_key_first($weights);
    }
}

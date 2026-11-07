<?php

namespace App\Support;

use App\Models\Rating;
use App\Models\Room;
use Illuminate\Support\Collection;

class Results
{
    public static function for(Room $room): Collection
    {
        $room->loadMissing(['criteria', 'stations']);

        if ($room->isChoice()) {
            return static::forChoice($room);
        }

        $averages = Rating::query()
            ->join('ballots', 'ballots.id', '=', 'ratings.ballot_id')
            ->whereIn('ballots.station_id', $room->stations->pluck('id'))
            ->groupBy('ballots.station_id', 'ratings.criterion_id')
            ->selectRaw('ballots.station_id as station_id, ratings.criterion_id as criterion_id, avg(ratings.score) as avg_score')
            ->get()
            ->groupBy('station_id');

        $ballotCounts = $room->stations->mapWithKeys(fn ($s) => [$s->id => $s->ballots()->count()]);

        $rows = $room->stations->map(function ($station) use ($room, $averages, $ballotCounts) {
            $byCriterion = collect($averages->get($station->id, []))->keyBy('criterion_id');

            $criteria = $room->criteria->mapWithKeys(fn ($c) => [
                $c->id => $byCriterion->has($c->id) ? round((float) $byCriterion[$c->id]->avg_score, 2) : null,
            ]);

            $scored = $criteria->filter(fn ($v) => $v !== null);

            return [
                'station' => $station,
                'criteria' => $criteria,
                'ballots' => $ballotCounts[$station->id] ?? 0,
                'average' => $scored->isEmpty() ? null : round($scored->avg(), 2),
                'total' => $scored->isEmpty() ? null : round($scored->sum(), 2),
            ];
        });

        return static::rank($rows);
    }

    private static function forChoice(Room $room): Collection
    {
        $counts = $room->choices()
            ->selectRaw('station_id, count(*) as votes')
            ->groupBy('station_id')
            ->pluck('votes', 'station_id');

        $voters = max(1, $room->voterCount());

        $rows = $room->stations->map(fn ($station) => [
            'station' => $station,
            'criteria' => collect(),
            'votes' => (int) ($counts[$station->id] ?? 0),
            'share' => round(((int) ($counts[$station->id] ?? 0)) / $voters * 100),
            'ballots' => (int) ($counts[$station->id] ?? 0),
            'average' => (int) ($counts[$station->id] ?? 0),
            'total' => (int) ($counts[$station->id] ?? 0),
        ]);

        return static::rank($rows);
    }

    private static function rank(Collection $rows): Collection
    {
        $sorted = $rows->sort(function ($a, $b) {
            if ($a['average'] === $b['average']) {
                return $a['station']->position <=> $b['station']->position;
            }
            if ($a['average'] === null) {
                return 1;
            }
            if ($b['average'] === null) {
                return -1;
            }

            return $b['average'] <=> $a['average'];
        })->values();

        $rank = 0;
        $previous = false;

        return $sorted->map(function ($row, $index) use (&$rank, &$previous) {
            if ($row['average'] !== $previous) {
                $rank = $index + 1;
                $previous = $row['average'];
            }
            $row['rank'] = $rank;

            return $row;
        });
    }

    public static function categoryWinners(Room $room, Collection $results): Collection
    {
        return $room->criteria->map(function ($criterion) use ($results) {
            $best = $results->filter(fn ($r) => $r['criteria'][$criterion->id] !== null)
                ->sortByDesc(fn ($r) => $r['criteria'][$criterion->id])
                ->values();

            if ($best->isEmpty()) {
                return null;
            }

            $top = $best->first()['criteria'][$criterion->id];

            return [
                'criterion' => $criterion,
                'score' => $top,
                'stations' => $best->filter(fn ($r) => $r['criteria'][$criterion->id] === $top)->pluck('station'),
            ];
        })->filter()->values();
    }
}

<?php

namespace App\Support;

use App\Models\Participant;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class ParticipantSession
{
    public static function cookieName(Room $room): string
    {
        return 'br_p_'.$room->id;
    }

    public static function current(Request $request, Room $room): ?Participant
    {
        $token = $request->cookie(static::cookieName($room));

        if (! is_string($token) || $token === '') {
            return null;
        }

        $participant = Participant::where('room_id', $room->id)
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if ($participant) {
            $participant->plainToken = $token;
        }

        return $participant;
    }

    public static function create(Room $room, string $name, ?int $stationId): Participant
    {
        $token = Str::random(48);

        $participant = $room->participants()->create([
            'name' => $name,
            'station_id' => $stationId,
            'token_hash' => hash('sha256', $token),
        ]);

        $participant->plainToken = $token;

        Cookie::queue(static::cookieName($room), $token, 60 * 24 * 400);

        return $participant;
    }

    public static function forget(Room $room): void
    {
        Cookie::queue(Cookie::forget(static::cookieName($room)));
    }
}

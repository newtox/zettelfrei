<?php

namespace App\Support;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;

class RoomAccess
{
    public const PREFIX = 'zf_a_';

    public static function cookieName(Room $room): string
    {
        return self::PREFIX.$room->id;
    }

    public static function canManage(Request $request, Room $room): bool
    {
        $user = $request->user();

        if ($user && ($user->isAdmin() || $room->user_id === $user->id)) {
            return true;
        }

        return static::hasToken($request, $room);
    }

    public static function hasToken(Request $request, Room $room): bool
    {
        $token = $request->cookie(static::cookieName($room));

        return is_string($token) && $token !== '' && hash_equals((string) $room->admin_token, $token);
    }

    public static function grant(Room $room): void
    {
        Cookie::queue(static::cookieName($room), $room->admin_token, 60 * 24 * 400);
    }

    public static function revoke(Room $room): void
    {
        Cookie::queue(Cookie::forget(static::cookieName($room)));
    }

    public static function roomsOnDevice(Request $request): Collection
    {
        $ids = collect($request->cookies->all())
            ->keys()
            ->filter(fn ($name) => str_starts_with($name, self::PREFIX))
            ->map(fn ($name) => (int) substr($name, strlen(self::PREFIX)))
            ->filter();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Room::whereIn('id', $ids)
            ->withCount(['stations', 'participants'])
            ->latest()
            ->get()
            ->filter(fn (Room $room) => static::hasToken($request, $room))
            ->values();
    }
}

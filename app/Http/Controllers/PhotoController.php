<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\Room;
use App\Models\Station;
use App\Support\ParticipantSession;
use App\Support\RoomAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhotoController extends Controller
{
    public function store(Request $request, Room $room, Station $station): JsonResponse|RedirectResponse
    {
        abort_unless($station->room_id === $room->id, 404);

        $participant = ParticipantSession::current($request, $room);
        $isOwner = RoomAccess::canManage($request, $room);

        abort_unless($participant || $isOwner, 403);
        abort_unless($room->photos_enabled, 403, 'Fotos sind in diesem Raum ausgeschaltet.');

        $request->validate([
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.config('zettelfrei.max_photo_kb')],
        ], [
            'photo.mimes' => 'Nur JPG, PNG oder WebP.',
            'photo.max' => 'Das Bild ist zu groß.',
        ]);

        $file = $request->file('photo');
        $size = @getimagesize($file->getRealPath());

        abort_unless($size !== false, 422, 'Das ist kein gültiges Bild.');

        $path = $file->store('photos/'.$room->id.'/'.$station->id, 'local');

        $photo = $station->photos()->create([
            'participant_id' => $participant?->id,
            'path' => $path,
            'width' => $size[0] ?? null,
            'height' => $size[1] ?? null,
        ]);

        $room->touchActivity();

        if ($request->expectsJson()) {
            return response()->json(['id' => $photo->id, 'url' => route('photos.show', [$room, $photo])], 201);
        }

        return back()->with('status', 'Foto hochgeladen.');
    }

    public function show(Request $request, Room $room, Photo $photo): StreamedResponse
    {
        $this->authorizeView($request, $room, $photo);

        abort_unless(Storage::disk('local')->exists($photo->path), 404);

        return Storage::disk('local')->response($photo->path, null, [
            'Cache-Control' => 'private, max-age=604800, immutable',
        ]);
    }

    public function destroy(Request $request, Room $room, Photo $photo): RedirectResponse
    {
        $this->authorizeView($request, $room, $photo);

        $participant = ParticipantSession::current($request, $room);
        $isOwner = RoomAccess::canManage($request, $room);

        abort_unless($isOwner || ($participant && $photo->participant_id === $participant->id), 403);

        Storage::disk('local')->delete($photo->path);
        $photo->delete();

        return back()->with('status', 'Foto gelöscht.');
    }

    private function authorizeView(Request $request, Room $room, Photo $photo): void
    {
        abort_unless($photo->station && $photo->station->room_id === $room->id, 404);

        $isOwner = RoomAccess::canManage($request, $room);

        abort_unless($isOwner || ParticipantSession::current($request, $room), 403);
    }
}

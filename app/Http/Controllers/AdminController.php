<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function rooms(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $rooms = Room::query()
            ->with('owner')
            ->withCount(['stations', 'participants', 'photos'])
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('code', strtoupper($search))))
            ->orderByDesc('last_activity_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.rooms', [
            'rooms' => $rooms,
            'search' => $search,
            'retention' => Room::retentionDays(),
        ]);
    }

    public function keepRoom(Room $room): RedirectResponse
    {
        $room->forceFill(['keep' => ! $room->keep])->save();

        return back()->with('status', $room->keep ? '„'.$room->name.'“ wird nicht mehr automatisch gelöscht.' : '„'.$room->name.'“ wird wieder automatisch aufgeräumt.');
    }

    public function destroyRoom(Room $room): RedirectResponse
    {
        $room->deletePhotoFiles();
        $room->delete();

        return back()->with('status', '„'.$room->name.'“ wurde gelöscht.');
    }

    public function users(): View
    {
        return view('admin.users', [
            'users' => User::withCount('rooms')->orderBy('name')->get(),
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        User::create($data + ['is_admin' => $request->boolean('is_admin')]);

        return back()->with('status', 'Konto für '.$data['name'].' angelegt.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', Password::min(8)],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        $isSelf = $user->is($request->user());

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'is_admin' => $isSelf ? true : $request->boolean('is_admin'),
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return back()->with('status', 'Konto von '.$user->name.' gespeichert.');
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 422, 'Du kannst dich nicht selbst löschen.');

        $user->delete();

        return back()->with('status', 'Konto von '.$user->name.' gelöscht. Seine Räume bleiben erhalten und sind weiter über ihren Admin-Link erreichbar.');
    }
}

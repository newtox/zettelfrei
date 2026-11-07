<?php

namespace App\Http\Controllers;

use App\Support\RoomAccess;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $rooms = $request->user()->rooms()
            ->withCount(['stations', 'participants'])
            ->latest()
            ->get()
            ->merge(RoomAccess::roomsOnDevice($request))
            ->unique('id')
            ->sortByDesc('created_at')
            ->values();

        return view('dashboard', ['rooms' => $rooms]);
    }
}

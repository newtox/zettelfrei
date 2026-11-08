<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\PresetController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::post('/beitreten', [PlayController::class, 'find'])->middleware('throttle:30,1')->name('join.find');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/vorlagen', [PresetController::class, 'index'])->name('presets.index');
    Route::post('/vorlagen', [PresetController::class, 'store'])->name('presets.store');
    Route::put('/vorlagen/{preset}', [PresetController::class, 'update'])->name('presets.update');
    Route::delete('/vorlagen/{preset}', [PresetController::class, 'destroy'])->name('presets.destroy');


    Route::middleware('admin')->prefix('/admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'rooms'])->name('rooms');
        Route::post('/demo', [AdminController::class, 'createDemo'])->name('demo');
        Route::post('/raeume/{room}/behalten', [AdminController::class, 'keepRoom'])->name('rooms.keep');
        Route::delete('/raeume/{room}', [AdminController::class, 'destroyRoom'])->name('rooms.destroy');
        Route::get('/einstellungen', [AdminController::class, 'settings'])->name('settings');
        Route::put('/einstellungen', [AdminController::class, 'updateSettings'])->name('settings.update');
        Route::get('/konten', [AdminController::class, 'users'])->name('users');
        Route::post('/konten', [AdminController::class, 'storeUser'])->name('users.store');
        Route::put('/konten/{user}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/konten/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
    });
});

Route::get('/raeume/neu', [RoomController::class, 'create'])->name('rooms.create');
Route::post('/raeume', [RoomController::class, 'store'])->middleware('throttle:10,1')->name('rooms.store');
Route::get('/raeume/{room}/admin/{token}', [RoomController::class, 'claim'])->middleware('throttle:20,1')->name('rooms.claim');

Route::prefix('/raeume/{room}')->name('rooms.')->group(function () {
    Route::get('/', [RoomController::class, 'manage'])->name('manage');
    Route::get('/status', [RoomController::class, 'state'])->name('state');
    Route::patch('/', [RoomController::class, 'update'])->name('update');
    Route::delete('/', [RoomController::class, 'destroy'])->name('destroy');
    Route::post('/zuruecksetzen', [RoomController::class, 'reset'])->name('reset');
    Route::post('/aufloesung', [RoomController::class, 'reveal'])->name('reveal');
    Route::patch('/einstellungen', [RoomController::class, 'updateSettings'])->name('settings');
    Route::post('/alle-oeffnen', [RoomController::class, 'openAll'])->name('open-all');
    Route::post('/alle-schliessen', [RoomController::class, 'closeAll'])->name('close-all');

    Route::post('/eintraege', [RoomController::class, 'addStation'])->name('stations.store');
    Route::patch('/eintraege/{station}', [RoomController::class, 'updateStation'])->name('stations.update');
    Route::delete('/eintraege/{station}', [RoomController::class, 'destroyStation'])->name('stations.destroy');
    Route::post('/eintraege/{station}/oeffnen', [RoomController::class, 'openStation'])->name('stations.open');
    Route::post('/eintraege/{station}/schliessen', [RoomController::class, 'closeStation'])->name('stations.close');
    Route::post('/eintraege/{station}/verschieben/{direction}', [RoomController::class, 'moveStation'])->whereIn('direction', ['up', 'down'])->name('stations.move');

    Route::post('/kriterien', [RoomController::class, 'addCriterion'])->name('criteria.store');
    Route::delete('/kriterien/{criterion}', [RoomController::class, 'destroyCriterion'])->whereNumber('criterion')->name('criteria.destroy');
    Route::post('/kriterien/{criterion}/verschieben/{direction}', [RoomController::class, 'moveCriterion'])->whereNumber('criterion')->whereIn('direction', ['up', 'down'])->name('criteria.move');

    Route::delete('/teilnehmer/{participant}', [RoomController::class, 'destroyParticipant'])->whereNumber('participant')->name('participants.destroy');
    Route::post('/admin-link', [RoomController::class, 'regenerateToken'])->name('token');
});

Route::prefix('/r/{room}')->group(function () {
    Route::get('/', [PlayController::class, 'show'])->name('play.show');
    Route::post('/beitreten', [PlayController::class, 'join'])->middleware('throttle:20,1')->name('play.join');
    Route::post('/verlassen', [PlayController::class, 'leave'])->name('play.leave');
    Route::get('/status', [PlayController::class, 'state'])->name('play.state');
    Route::get('/ergebnis', [PlayController::class, 'results'])->name('play.results');
    Route::get('/fotos', [PlayController::class, 'gallery'])->name('play.gallery');
    Route::post('/eintraege/{station}/bewertung', [PlayController::class, 'vote'])->name('play.vote');
    Route::post('/stimme', [PlayController::class, 'choose'])->name('play.choose');
    Route::post('/eintraege/{station}/fotos', [PhotoController::class, 'store'])->middleware('throttle:60,1')->name('photos.store');
    Route::get('/fotos/{photo}', [PhotoController::class, 'show'])->name('photos.show');
    Route::delete('/fotos/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');
});

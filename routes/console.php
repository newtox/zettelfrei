<?php

use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Artisan::command('user:create {email} {--name=} {--password=} {--admin}', function (string $email) {
    if (User::where('email', $email)->exists()) {
        $this->error('Es gibt schon ein Konto mit '.$email.'.');

        return 1;
    }

    $password = $this->option('password') ?: Str::password(16, symbols: false);

    $user = User::create([
        'name' => $this->option('name') ?: Str::before($email, '@'),
        'email' => $email,
        'password' => $password,
        'is_admin' => (bool) $this->option('admin'),
    ]);

    $this->info(($user->is_admin ? 'Admin' : 'Konto').' angelegt: '.$user->email);

    if (! $this->option('password')) {
        $this->line('Passwort: '.$password);
    }

    return 0;
})->purpose('Konto anlegen, mit --admin als Super-Admin');

Schedule::command('model:prune', ['--model' => [Room::class]])->dailyAt('04:00');

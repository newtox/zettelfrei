<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'station_id', 'token_hash'])]
#[Hidden(['token_hash'])]
class Participant extends Model
{
    public ?string $plainToken = null;

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function canRate(Station $station): bool
    {
        return ! $this->station_id || $this->station_id !== $station->id;
    }

    public function voterHashFor(Station $station): string
    {
        return hash_hmac('sha256', $this->plainToken.'|'.$station->id, config('app.key'));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'position', 'status'])]
class Station extends Model
{
    public const PENDING = 'pending';

    public const OPEN = 'open';

    public const CLOSED = 'closed';

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function ballots(): HasMany
    {
        return $this->hasMany(Ballot::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class)->latest();
    }

    public function members(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function choices(): HasMany
    {
        return $this->hasMany(Choice::class);
    }

    public function isOpen(): bool
    {
        return $this->status === self::OPEN;
    }

    public function eligibleVoterCount(): int
    {
        return Participant::where('room_id', $this->room_id)
            ->where(fn ($q) => $q->whereNull('station_id')->orWhere('station_id', '!=', $this->id))
            ->count();
    }
}

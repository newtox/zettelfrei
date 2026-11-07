<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['participant_id', 'path', 'width', 'height'])]
class Photo extends Model
{
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function url(): string
    {
        return route('photos.show', [$this->station->room, $this]);
    }
}

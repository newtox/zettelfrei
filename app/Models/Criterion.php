<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'position'])]
class Criterion extends Model
{
    protected $table = 'criteria';

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}

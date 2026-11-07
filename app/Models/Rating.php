<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['criterion_id', 'score'])]
class Rating extends Model
{
    public $timestamps = false;

    public function ballot(): BelongsTo
    {
        return $this->belongsTo(Ballot::class);
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'criteria'])]
class Preset extends Model
{
    protected function casts(): array
    {
        return ['criteria' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSystem(): bool
    {
        return $this->user_id === null;
    }

    public function scopeAvailableFor(Builder $query, ?User $user): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('user_id')->when($user, fn ($q) => $q->orWhere('user_id', $user->id)))
            ->orderByRaw('user_id is not null')
            ->orderBy('id');
    }
}

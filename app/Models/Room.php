<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Hidden(['admin_token'])]
#[Fillable(['name', 'theme', 'join_locked', 'keep', 'mode', 'item_label', 'items_label', 'exclude_own', 'photos_enabled', 'one_at_a_time', 'voting_open', 'max_choices'])]
class Room extends Model
{
    use Prunable;

    public const SCORE = 'score';

    public const CHOICE = 'choice';

    public const MODES = [
        self::SCORE => 'Punkte vergeben',
        self::CHOICE => 'Auswählen',
    ];

    public const TEMPLATES = [
        'trees' => [
            'label' => 'Weihnachtsbäume',
            'hint' => 'Rundgang von Familie zu Familie, Punkte pro Kriterium',
            'items_hint' => 'Die Familien, deren Baum ihr bewertet.',
            'name' => 'Baumbewertung',
            'mode' => self::SCORE,
            'item_label' => 'Familie',
            'items_label' => 'Familien',
            'exclude_own' => true,
            'photos_enabled' => true,
            'one_at_a_time' => true,
            'max_choices' => 1,
            'theme' => 'christmas',
            'preset' => 'Weihnachtsbaum',
        ],
        'contest' => [
            'label' => 'Wettbewerb',
            'hint' => 'Alle Beiträge gleichzeitig bewerten, z. B. Plätzchen oder Kostüme',
            'items_hint' => 'Die Beiträge, die bewertet werden, z. B. jede Plätzchensorte oder jedes Kostüm.',
            'name' => 'Wettbewerb',
            'mode' => self::SCORE,
            'item_label' => 'Beitrag',
            'items_label' => 'Beiträge',
            'exclude_own' => true,
            'photos_enabled' => true,
            'one_at_a_time' => false,
            'max_choices' => 1,
            'theme' => 'dark',
            'preset' => 'Allgemein',
        ],
        'poll' => [
            'label' => 'Umfrage',
            'hint' => 'Jeder wählt eine Option, z. B. wohin es zum Essen geht',
            'items_hint' => 'Die Antworten, aus denen jeder auswählt.',
            'name' => 'Wohin gehen wir?',
            'mode' => self::CHOICE,
            'item_label' => 'Option',
            'items_label' => 'Optionen',
            'exclude_own' => false,
            'photos_enabled' => false,
            'one_at_a_time' => false,
            'max_choices' => 1,
            'theme' => 'light',
            'preset' => null,
        ],
    ];

    public const THEMES = [
        'christmas' => 'Weihnachtlich',
        'dark' => 'Dunkel',
        'light' => 'Hell',
    ];

    protected function casts(): array
    {
        return [
            'admin_token' => 'encrypted',
            'keep' => 'boolean',
            'last_activity_at' => 'datetime',
            'join_locked' => 'boolean',
            'reveal_step' => 'integer',
            'exclude_own' => 'boolean',
            'photos_enabled' => 'boolean',
            'one_at_a_time' => 'boolean',
            'voting_open' => 'boolean',
            'max_choices' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Room $room) {
            if (! $room->code) {
                do {
                    $code = Str::upper(Str::random(6));
                    $code = strtr($code, ['0' => 'X', 'O' => 'Y', 'I' => 'Z', 'L' => 'K', '1' => 'M']);
                } while (static::where('code', $code)->exists());

                $room->code = $code;
            }

            $room->admin_token ??= Str::random(40);
            $room->last_activity_at ??= now();
        });
    }

    public static function retentionDays(): int
    {
        return max(0, (int) config('zettelfrei.retention_days'));
    }

    public function prunable(): Builder
    {
        $days = static::retentionDays();

        return static::query()
            ->where('keep', false)
            ->when($days === 0, fn ($q) => $q->whereRaw('1 = 0'))
            ->where('last_activity_at', '<', now()->subDays(max(1, $days)));
    }

    protected function pruning(): void
    {
        $this->deletePhotoFiles();
    }

    public function deletePhotoFiles(): void
    {
        Storage::disk('local')->delete($this->photos()->pluck('path')->all());
        Storage::disk('local')->deleteDirectory('photos/'.$this->id);
    }

    public function pruneDate(): ?\Illuminate\Support\Carbon
    {
        $days = static::retentionDays();

        if ($this->keep || $days === 0) {
            return null;
        }

        return ($this->last_activity_at ?? $this->created_at)->copy()->addDays($days);
    }

    public function touchActivity(): void
    {
        if (! $this->last_activity_at || $this->last_activity_at->lt(now()->subMinute())) {
            $this->forceFill(['last_activity_at' => now()])->saveQuietly();
        }
    }

    public function adminUrl(): string
    {
        return route('rooms.claim', [$this, $this->admin_token]);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(Criterion::class)->orderBy('position')->orderBy('id');
    }

    public function stations(): HasMany
    {
        return $this->hasMany(Station::class)->orderBy('position')->orderBy('id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class)->orderBy('name');
    }

    public function photos(): HasManyThrough
    {
        return $this->hasManyThrough(Photo::class, Station::class);
    }

    public function choices(): HasMany
    {
        return $this->hasMany(Choice::class);
    }

    public function isChoice(): bool
    {
        return $this->mode === self::CHOICE;
    }

    public function isScore(): bool
    {
        return $this->mode !== self::CHOICE;
    }

    public function openStations()
    {
        return $this->stations->where('status', Station::OPEN)->values();
    }

    public function activeStation(): ?Station
    {
        return $this->openStations()->first();
    }

    public function hasBallots(): bool
    {
        return Ballot::whereIn('station_id', $this->stations()->select('id'))->exists()
            || $this->choices()->exists();
    }

    public function voterCount(): int
    {
        return $this->choices()->distinct()->count('voter_hash');
    }

    public function eligibleChoiceVoters(): int
    {
        return $this->participants()->count();
    }

    public function voterHash(Participant $participant): string
    {
        return hash_hmac('sha256', $participant->plainToken.'|room:'.$this->id, config('app.key'));
    }

    public function isRevealing(): bool
    {
        return $this->reveal_step > 0;
    }

    public function isFullyRevealed(): bool
    {
        return $this->reveal_step > 0 && $this->reveal_step >= $this->stations()->count();
    }

    public function joinUrl(): string
    {
        return route('play.show', $this);
    }
}

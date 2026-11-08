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
#[Fillable(['name', 'theme', 'join_locked', 'keep', 'mode', 'rating_style', 'item_label', 'items_label', 'exclude_own', 'own_question', 'own_none_label', 'photos_enabled', 'photos_own_only', 'one_at_a_time', 'voting_open', 'max_choices'])]
class Room extends Model
{
    use Prunable;

    public const SCORE = 'score';

    public const CHOICE = 'choice';

    public const MODES = [
        self::SCORE => 'Punkte vergeben',
        self::CHOICE => 'Auswählen',
    ];

    public const RATING_STYLES = [
        'lights' => ['label' => 'Lichterkette', 'hint' => 'Birnen leuchten bis zur gewählten Zahl auf'],
        'numbers' => ['label' => 'Zahlen', 'hint' => 'Schlichte Knöpfe von 1 bis 10'],
        'slider' => ['label' => 'Schieberegler', 'hint' => 'Ein Regler mit großer Zahlenanzeige'],
        'stars' => ['label' => 'Sterne', 'hint' => '5 Sterne mit halben Schritten'],
        'grades' => ['label' => 'Schulnoten', 'hint' => 'Noten von 1 bis 6, die 1 ist die beste'],
    ];

    public const OWN_QUESTIONS = [
        'generic' => ['question' => 'Gehörst du selbst zu einem Eintrag?', 'none' => 'Nein'],
        'tree' => ['question' => 'Welcher Baum ist deiner?', 'none' => 'Keiner davon'],
        'family' => ['question' => 'Zu welcher Familie gehörst du?', 'none' => 'Zu keiner davon'],
        'entry' => ['question' => 'Welcher Beitrag ist von dir?', 'none' => 'Keiner davon'],
        'team' => ['question' => 'In welchem Team bist du?', 'none' => 'In keinem'],
    ];

    public const TEMPLATES = [
        'trees' => [
            'label' => 'Weihnachtsbäume',
            'hint' => 'Rundgang von Baum zu Baum, Punkte pro Kriterium',
            'items_hint' => 'Die Bäume, die ihr bewertet. Benennt sie z. B. nach denen, die dort wohnen.',
            'placeholder' => 'z. B. Erika & Jörg',
            'name' => 'Baumbewertung',
            'mode' => self::SCORE,
            'item_label' => 'Baum',
            'items_label' => 'Bäume',
            'rating_style' => 'lights',
            'exclude_own' => true,
            'own_question' => self::OWN_QUESTIONS['tree']['question'],
            'own_none_label' => self::OWN_QUESTIONS['tree']['none'],
            'photos_enabled' => true,
            'photos_own_only' => false,
            'one_at_a_time' => true,
            'max_choices' => 1,
            'theme' => 'christmas',
            'preset' => 'Weihnachtsbaum',
        ],
        'contest' => [
            'label' => 'Wettbewerb',
            'hint' => 'Alle Beiträge gleichzeitig bewerten, z. B. Plätzchen oder Kostüme',
            'items_hint' => 'Die Beiträge, die bewertet werden, z. B. jede Plätzchensorte oder jedes Kostüm.',
            'placeholder' => 'z. B. Zimtsterne',
            'name' => 'Wettbewerb',
            'mode' => self::SCORE,
            'item_label' => 'Beitrag',
            'items_label' => 'Beiträge',
            'rating_style' => 'stars',
            'exclude_own' => true,
            'own_question' => self::OWN_QUESTIONS['entry']['question'],
            'own_none_label' => self::OWN_QUESTIONS['entry']['none'],
            'photos_enabled' => true,
            'photos_own_only' => false,
            'one_at_a_time' => false,
            'max_choices' => 1,
            'theme' => 'dark',
            'preset' => 'Allgemein',
        ],
        'poll' => [
            'label' => 'Umfrage',
            'hint' => 'Alle wählen eine Option, z. B. wohin es zum Essen geht',
            'items_hint' => 'Die Antworten, aus denen alle auswählen.',
            'placeholder' => 'z. B. Pizzeria am Markt',
            'name' => 'Wohin gehen wir?',
            'mode' => self::CHOICE,
            'item_label' => 'Option',
            'items_label' => 'Optionen',
            'rating_style' => 'lights',
            'exclude_own' => false,
            'own_question' => self::OWN_QUESTIONS['generic']['question'],
            'own_none_label' => self::OWN_QUESTIONS['generic']['none'],
            'photos_enabled' => false,
            'photos_own_only' => false,
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
            'photos_own_only' => 'boolean',
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

    public function usesGrades(): bool
    {
        return $this->rating_style === 'grades';
    }

    public function scoreMax(): int
    {
        return $this->usesGrades() ? 6 : 10;
    }

    public static function gradeToScore(int $grade): int
    {
        return 7 - $grade;
    }

    public function displayScore(?float $score): string
    {
        if ($score === null) {
            return '–';
        }

        return $this->usesGrades()
            ? number_format(7 - $score, 2, ',', '.')
            : number_format($score, 1, ',', '.');
    }

    public function ownQuestion(): string
    {
        return filled($this->own_question) ? $this->own_question : self::OWN_QUESTIONS['generic']['question'];
    }

    public function ownNoneLabel(): string
    {
        return filled($this->own_none_label) ? $this->own_none_label : self::OWN_QUESTIONS['generic']['none'];
    }

    public static function maxPhotosPerEntry(): int
    {
        return max(1, (int) config('zettelfrei.max_photos_per_entry'));
    }

    public function canUploadPhoto(?Participant $participant, Station $station, bool $canManage): bool
    {
        if (! $this->photos_enabled) {
            return false;
        }

        if ($canManage) {
            return true;
        }

        if (! $participant) {
            return false;
        }

        return ! ($this->photos_own_only && $this->exclude_own) || $participant->station_id === $station->id;
    }

    public function resetVotes(bool $keepParticipants, bool $keepPhotos): void
    {
        $stationIds = $this->stations()->pluck('id');

        Ballot::whereIn('station_id', $stationIds)->delete();
        $this->choices()->delete();

        if (! $keepPhotos) {
            $this->deletePhotoFiles();
            Photo::whereIn('station_id', $stationIds)->delete();
        }

        if (! $keepParticipants) {
            $this->participants()->delete();
        }

        Station::whereIn('id', $stationIds)->update(['status' => Station::PENDING]);

        $this->forceFill(['voting_open' => false, 'reveal_step' => 0])->save();
        $this->touchActivity();
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

<?php

namespace Tests\Feature;

use App\Models\Ballot;
use App\Models\Photo;
use App\Models\Room;
use App\Models\Station;
use App\Models\User;
use App\Support\ParticipantSession;
use App\Support\RoomAccess;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ZettelfreiTest extends TestCase
{
    use RefreshDatabase;

    private function makeRoom(?User $owner = null, array $overrides = []): Room
    {
        $owner ??= User::factory()->create();

        $this->actingAs($owner)->post(route('rooms.store'), array_merge([
            'name' => 'Baumbewertung 2026',
            'theme' => 'christmas',
            'mode' => 'score',
            'item_label' => 'Familie',
            'items_label' => 'Familien',
            'exclude_own' => '1',
            'photos_enabled' => '1',
            'one_at_a_time' => '1',
            'criteria' => ['Schmuck', 'Beleuchtung', ''],
            'stations' => ['Müller', 'Schmidt', 'Weber'],
        ], $overrides))->assertSessionHasNoErrors()->assertRedirect();

        auth()->logout();

        return Room::with(['criteria', 'stations'])->latest('id')->first();
    }

    private function join(Room $room, string $name, ?Station $own = null): string
    {
        $response = $this->anonymous()->post(route('play.join', $room), [
            'name' => $name,
            'station_id' => $own?->id,
        ]);

        $response->assertRedirect(route('play.show', $room));

        $token = $response->getCookie(ParticipantSession::cookieName($room))?->getValue();
        $this->assertNotEmpty($token);

        return $token;
    }

    private function anonymous(): static
    {
        $this->defaultCookies = [];
        $this->unencryptedCookies = [];

        return $this;
    }

    private function as(Room $room, string $token): static
    {
        $this->anonymous();

        return $this->withCookie(ParticipantSession::cookieName($room), $token);
    }

    private function scores(Room $room, array $values): array
    {
        return ['scores' => $room->criteria->pluck('id')->combine($values)->all()];
    }

    private function open(Room $room, Station $station): void
    {
        $this->actingAs($room->owner)->post(route('rooms.stations.open', [$room, $station]))->assertRedirect();
        auth()->logout();
    }

    public function test_owner_creates_room_with_criteria_and_families(): void
    {
        $room = $this->makeRoom();

        $this->assertSame(['Schmuck', 'Beleuchtung'], $room->criteria->pluck('name')->all());
        $this->assertSame(['Müller', 'Schmidt', 'Weber'], $room->stations->pluck('name')->all());
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $room->code);
    }

    public function test_code_lookup_redirects_to_room(): void
    {
        $room = $this->makeRoom();

        $this->post(route('join.find'), ['code' => strtolower($room->code)])
            ->assertRedirect(route('play.show', $room));

        $this->post(route('join.find'), ['code' => 'NOPE00'])->assertSessionHasErrors('code');
    }

    public function test_participant_votes_and_can_change_vote_while_open(): void
    {
        $room = $this->makeRoom();
        [$mueller] = $room->stations;
        $token = $this->join($room, 'Oma Erika');
        $this->open($room, $mueller);

        $this->as($room, $token)->post(route('play.vote', [$room, $mueller]), $this->scores($room, [7, 9]))->assertRedirect();
        $this->as($room, $token)->post(route('play.vote', [$room, $mueller]), $this->scores($room, [8, 10]))->assertRedirect();

        $this->assertSame(1, Ballot::count());
        $this->assertEqualsCanonicalizing([8, 10], Ballot::first()->ratings->pluck('score')->all());
    }

    public function test_ballots_cannot_be_linked_to_participants_in_the_database(): void
    {
        $this->assertFalse(Schema::hasColumn('ballots', 'participant_id'));
        $this->assertFalse(Schema::hasColumn('ratings', 'participant_id'));

        $room = $this->makeRoom();
        [$mueller] = $room->stations;
        $token = $this->join($room, 'Tante Gabi');
        $this->open($room, $mueller);
        $this->as($room, $token)->post(route('play.vote', [$room, $mueller]), $this->scores($room, [5, 5]));

        $ballot = Ballot::first();
        $participant = $room->participants()->first();

        $this->assertNotSame(hash('sha256', $token), $ballot->voter_hash);
        $this->assertNotSame(hash('sha256', (string) $participant->id), $ballot->voter_hash);
    }

    public function test_own_family_cannot_be_rated(): void
    {
        $room = $this->makeRoom();
        [$mueller] = $room->stations;
        $token = $this->join($room, 'Peter', $mueller);
        $this->open($room, $mueller);

        $this->as($room, $token)->post(route('play.vote', [$room, $mueller]), $this->scores($room, [10, 10]))->assertForbidden();
        $this->assertSame(0, Ballot::count());

        $this->as($room, $token)->get(route('play.show', $room))->assertSee('Hier gehörst du selbst dazu.');
        $this->assertSame(0, $mueller->fresh()->eligibleVoterCount());
    }

    public function test_closed_or_pending_station_rejects_votes(): void
    {
        $room = $this->makeRoom();
        [$mueller, $schmidt] = $room->stations;
        $token = $this->join($room, 'Lena');

        $this->as($room, $token)->post(route('play.vote', [$room, $schmidt]), $this->scores($room, [3, 3]))
            ->assertSessionHas('error');

        $this->open($room, $mueller);
        $this->open($room, $schmidt);

        $this->assertSame(Station::CLOSED, $mueller->fresh()->status);
        $this->as($room, $token)->post(route('play.vote', [$room, $mueller]), $this->scores($room, [3, 3]))
            ->assertSessionHas('error');
        $this->assertSame(0, Ballot::count());
    }

    public function test_all_criteria_need_a_score_between_one_and_ten(): void
    {
        $room = $this->makeRoom();
        [$mueller] = $room->stations;
        $token = $this->join($room, 'Max');
        $this->open($room, $mueller);

        $this->as($room, $token)->post(route('play.vote', [$room, $mueller]), ['scores' => [$room->criteria[0]->id => 5]])
            ->assertSessionHasErrors();
        $this->as($room, $token)->post(route('play.vote', [$room, $mueller]), $this->scores($room, [11, 0]))
            ->assertSessionHasErrors();

        $this->assertSame(0, Ballot::count());
    }

    public function test_duplicate_names_are_rejected(): void
    {
        $room = $this->makeRoom();
        $this->join($room, 'Anna');

        $this->anonymous()->post(route('play.join', $room), ['name' => 'Anna'])->assertSessionHasErrors('name');
    }

    public function test_locked_room_rejects_new_participants(): void
    {
        $room = $this->makeRoom();
        $room->update(['join_locked' => true]);

        $this->post(route('play.join', $room), ['name' => 'Spät'])->assertSessionHasErrors('name');
        $this->assertSame(0, $room->participants()->count());
    }

    public function test_results_stay_hidden_until_revealed_and_ranking_is_correct(): void
    {
        $room = $this->makeRoom();
        [$mueller, $schmidt, $weber] = $room->stations;

        $a = $this->join($room, 'Anna');
        $b = $this->join($room, 'Bernd', $weber);

        $this->as($room, $a)->get(route('play.results', $room))->assertRedirect(route('play.show', $room));
        $this->actingAs($room->owner)->get(route('play.results', $room))->assertRedirect(route('rooms.manage', $room));
        auth()->logout();

        $this->open($room, $mueller);
        $this->as($room, $a)->post(route('play.vote', [$room, $mueller]), $this->scores($room, [6, 6]));
        $this->as($room, $b)->post(route('play.vote', [$room, $mueller]), $this->scores($room, [8, 8]));

        $this->open($room, $schmidt);
        $this->as($room, $a)->post(route('play.vote', [$room, $schmidt]), $this->scores($room, [9, 10]));
        $this->as($room, $b)->post(route('play.vote', [$room, $schmidt]), $this->scores($room, [9, 8]));

        $this->open($room, $weber);
        $this->as($room, $a)->post(route('play.vote', [$room, $weber]), $this->scores($room, [2, 4]));

        $this->actingAs($room->owner)->post(route('rooms.reveal', $room), ['action' => 'next'])
            ->assertRedirect(route('play.results', $room));
        auth()->logout();

        $this->assertSame(Station::CLOSED, $weber->fresh()->status);

        $this->as($room, $a)->get(route('play.results', $room))
            ->assertOk()
            ->assertSee('Weber')
            ->assertDontSee('Schmidt')
            ->assertDontSee('Müller');

        $this->actingAs($room->owner)->post(route('rooms.reveal', $room), ['action' => 'all']);
        auth()->logout();

        $this->as($room, $a)->get(route('play.results', $room))
            ->assertOk()
            ->assertSeeInOrder(['Schmidt', '9,0', 'Müller', '7,0', 'Weber', '3,0'])
            ->assertSee('Die Besten je Kriterium');

        $this->as($room, $a)->get(route('play.show', $room))->assertRedirect(route('play.results', $room));
    }

    public function test_only_owner_can_manage_room(): void
    {
        $room = $this->makeRoom();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get(route('rooms.manage', $room))->assertForbidden();
        $this->actingAs($stranger)->post(route('rooms.reveal', $room), ['action' => 'all'])->assertForbidden();
        $this->actingAs($stranger)->post(route('rooms.stations.open', [$room, $room->stations[0]]))->assertForbidden();

        $this->actingAs($room->owner)->get(route('rooms.manage', $room))->assertOk()->assertSee($room->code);
    }

    public function test_guests_are_sent_to_login_for_account_pages(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('presets.index'))->assertRedirect(route('login'));
        $this->get(route('admin.rooms'))->assertRedirect(route('login'));
    }

    public function test_public_registration_is_gone(): void
    {
        $this->get('/registrieren')->assertNotFound();
        $this->post('/registrieren', ['email' => 'x@example.com'])->assertNotFound();
    }

    private function guestRoom(): array
    {
        $response = $this->anonymous()->post(route('rooms.store'), [
            'name' => 'Ohne Konto',
            'theme' => 'dark',
            'mode' => 'score',
            'item_label' => 'Beitrag',
            'items_label' => 'Beiträge',
            'photos_enabled' => '1',
            'criteria' => ['Geschmack'],
            'stations' => ['Zimtsterne', 'Kipferl'],
        ]);

        $room = Room::latest('id')->first();
        $response->assertRedirect(route('rooms.manage', $room))->assertSessionHas('created');

        $token = $response->getCookie(RoomAccess::cookieName($room))?->getValue();
        $this->assertNotEmpty($token);

        return [$room, $token];
    }

    private function asRoomAdmin(Room $room, string $token): static
    {
        return $this->anonymous()->withCookie(RoomAccess::cookieName($room), $token);
    }

    public function test_guest_can_create_and_manage_room_without_account(): void
    {
        $this->get(route('rooms.create'))->assertOk()->assertSee('Weihnachtsbaum')->assertSee('Kostüm')->assertDontSee('als eigene Vorlage speichern');

        [$room, $token] = $this->guestRoom();

        $this->assertNull($room->user_id);
        $this->asRoomAdmin($room, $token)->get(route('rooms.manage', $room))
            ->assertOk()
            ->assertSee('Speicher dir diesen Admin-Link')
            ->assertSee($room->adminUrl());
        $this->asRoomAdmin($room, $token)->post(route('rooms.stations.open', [$room, $room->stations[0]]))->assertRedirect();
        $this->asRoomAdmin($room, $token)->get(route('home'))->assertSee('Deine Räume auf diesem Gerät')->assertSee('Ohne Konto');

        $this->anonymous()->get(route('rooms.manage', $room))->assertForbidden();
        $this->asRoomAdmin($room, 'falsch')->get(route('rooms.manage', $room))->assertForbidden();
    }

    public function test_admin_link_grants_access_and_can_be_regenerated(): void
    {
        [$room, $token] = $this->guestRoom();
        $oldUrl = $room->adminUrl();

        $this->anonymous()->get(route('rooms.claim', [$room, 'falsch']))->assertNotFound();

        $claim = $this->anonymous()->get($oldUrl);
        $claim->assertRedirect(route('rooms.manage', $room));
        $this->assertSame($token, $claim->getCookie(RoomAccess::cookieName($room))->getValue());

        $this->asRoomAdmin($room, $token)->post(route('rooms.token', $room))->assertRedirect();
        $room->refresh();

        $this->assertNotSame($token, $room->admin_token);
        $this->asRoomAdmin($room, $token)->get(route('rooms.manage', $room))->assertForbidden();
        $this->anonymous()->get($oldUrl)->assertNotFound();
        $this->asRoomAdmin($room, $room->admin_token)->get(route('rooms.manage', $room))->assertOk();
    }

    public function test_admin_token_is_stored_encrypted(): void
    {
        [$room, $token] = $this->guestRoom();

        $raw = \Illuminate\Support\Facades\DB::table('rooms')->where('id', $room->id)->value('admin_token');
        $this->assertNotSame($token, $raw);
        $this->assertStringNotContainsString($token, $raw);
    }

    public function test_super_admin_sees_and_deletes_any_room(): void
    {
        Storage::fake('local');

        [$room, $token] = $this->guestRoom();
        $participant = $this->join($room, 'Fotograf');
        $this->as($room, $participant)->post(route('photos.store', [$room, $room->stations[0]]), ['photo' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json'])->assertCreated();
        $path = Photo::first()->path;

        $admin = User::factory()->create(['is_admin' => true]);
        $normal = User::factory()->create();

        $this->anonymous()->actingAs($normal)->get(route('admin.rooms'))->assertForbidden();
        $this->actingAs($normal)->get(route('rooms.manage', $room))->assertForbidden();

        $this->actingAs($admin)->get(route('admin.rooms'))->assertOk()->assertSee('Ohne Konto')->assertSee('ohne Konto');
        $this->actingAs($admin)->get(route('rooms.manage', $room))->assertOk();

        $this->actingAs($admin)->delete(route('admin.rooms.destroy', $room))->assertRedirect();
        $this->assertModelMissing($room);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_super_admin_manages_accounts(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Mama', 'email' => 'mama@example.com', 'password' => 'geheim123',
        ])->assertSessionHasNoErrors();

        $mama = User::where('email', 'mama@example.com')->first();
        $this->assertFalse($mama->isAdmin());

        $this->actingAs($admin)->put(route('admin.users.update', $mama), [
            'name' => 'Mama', 'email' => 'mama@example.com', 'is_admin' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertTrue($mama->fresh()->isAdmin());

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email,
        ]);
        $this->assertTrue($admin->fresh()->isAdmin());

        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertStatus(422);
        $this->actingAs($admin)->delete(route('admin.users.destroy', $mama))->assertRedirect();
        $this->assertModelMissing($mama);
    }

    public function test_inactive_rooms_are_pruned_unless_kept(): void
    {
        Storage::fake('local');
        config(['zettelfrei.retention_days' => 90]);

        $old = $this->makeRoom();
        $kept = $this->makeRoom();
        $fresh = $this->makeRoom();

        $token = $this->join($old, 'Alt');
        $this->as($old, $token)->post(route('photos.store', [$old, $old->stations[0]]), ['photo' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json']);
        $path = Photo::first()->path;

        Room::whereKey([$old->id, $kept->id])->update(['last_activity_at' => now()->subDays(91)]);
        $kept->forceFill(['keep' => true])->save();

        Artisan::call('model:prune', ['--model' => [Room::class]]);

        $this->assertModelMissing($old);
        $this->assertModelExists($kept);
        $this->assertModelExists($fresh);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_pruning_can_be_disabled(): void
    {
        config(['zettelfrei.retention_days' => 0]);
        $room = $this->makeRoom();
        $room->forceFill(['last_activity_at' => now()->subYears(3)])->save();

        Artisan::call('model:prune', ['--model' => [Room::class]]);

        $this->assertModelExists($room);
        $this->assertNull($room->fresh()->pruneDate());
    }

    public function test_activity_is_tracked(): void
    {
        $room = $this->makeRoom();
        $room->forceFill(['last_activity_at' => now()->subDays(10)])->save();

        $this->join($room, 'Neu');

        $this->assertTrue($room->fresh()->last_activity_at->isToday());
    }

    public function test_user_create_command(): void
    {
        $this->artisan('user:create', ['email' => 'justin@example.com', '--admin' => true, '--password' => 'geheim123'])
            ->expectsOutputToContain('Admin angelegt')
            ->assertSuccessful();

        $this->assertTrue(User::where('email', 'justin@example.com')->first()->isAdmin());

        $this->artisan('user:create', ['email' => 'justin@example.com'])->assertFailed();
    }

    public function test_anyone_in_the_room_can_upload_photos_but_outsiders_cannot_see_them(): void
    {
        Storage::fake('local');

        $room = $this->makeRoom();
        [$mueller] = $room->stations;
        $token = $this->join($room, 'Fotograf');

        $this->as($room, $token)
            ->post(route('photos.store', [$room, $mueller]), ['photo' => UploadedFile::fake()->image('baum.jpg', 800, 1200)], ['Accept' => 'application/json'])
            ->assertCreated();

        $photo = $mueller->photos()->first();
        Storage::disk('local')->assertExists($photo->path);
        $this->assertSame(800, $photo->width);

        $this->as($room, $token)->get(route('photos.show', [$room, $photo]))->assertOk();

        $this->anonymous()->get(route('photos.show', [$room, $photo]))->assertForbidden();

        $other = $this->makeRoom();
        $this->as($other, $this->join($other, 'Fremd'))->get(route('photos.show', [$room, $photo]))->assertForbidden();
    }

    public function test_presets_can_be_created_and_are_private(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->post(route('presets.store'), [
            'name' => 'Unsere Liste',
            'criteria' => ['Lametta', 'Kugeln', ''],
        ])->assertRedirect();

        $preset = $user->presets()->first();
        $this->assertSame(['Lametta', 'Kugeln'], $preset->criteria);

        $this->actingAs($user)->get(route('rooms.create'))->assertSee('Unsere Liste')->assertSee('Nur Gesamteindruck');
        $this->actingAs($other)->get(route('rooms.create'))->assertDontSee('Unsere Liste');
        $this->actingAs($other)->delete(route('presets.destroy', $preset))->assertForbidden();
    }

    public function test_criteria_can_be_saved_as_preset_when_creating_room(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('rooms.store'), [
            'name' => 'Test',
            'theme' => 'dark',
            'mode' => 'score',
            'item_label' => 'Beitrag',
            'items_label' => 'Beiträge',
            'criteria' => ['Glitzer'],
            'stations' => ['Eins'],
            'save_preset' => '1',
            'preset_name' => 'Glitzer-Wertung',
        ])->assertRedirect();

        $this->assertSame(['Glitzer'], $user->presets()->where('name', 'Glitzer-Wertung')->first()->criteria);
    }

    private function poll(array $overrides = []): Room
    {
        return $this->makeRoom(null, array_merge([
            'name' => 'Wohin gehen wir essen?',
            'theme' => 'light',
            'mode' => 'choice',
            'item_label' => 'Option',
            'items_label' => 'Optionen',
            'exclude_own' => '0',
            'photos_enabled' => '0',
            'one_at_a_time' => '0',
            'max_choices' => 1,
            'criteria' => [],
            'stations' => ['Italiener', 'Grieche', 'Chinese'],
        ], $overrides));
    }

    public function test_poll_room_is_created_without_criteria(): void
    {
        $room = $this->poll();

        $this->assertTrue($room->isChoice());
        $this->assertSame(0, $room->criteria->count());
        $this->assertSame('Optionen', $room->items_label);
    }

    public function test_poll_needs_at_least_two_options(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('rooms.store'), [
            'name' => 'Frage', 'theme' => 'light', 'mode' => 'choice',
            'item_label' => 'Option', 'items_label' => 'Optionen',
            'stations' => ['Nur eine'],
        ])->assertSessionHasErrors('stations');
    }

    public function test_poll_voting_only_while_open_and_vote_can_be_changed(): void
    {
        $room = $this->poll();
        [$italiener, $grieche] = $room->stations;
        $token = $this->join($room, 'Oma Erika');

        $this->as($room, $token)->post(route('play.choose', $room), ['options' => [$italiener->id]])->assertSessionHas('error');
        $this->assertSame(0, $room->choices()->count());

        $this->actingAs($room->owner)->post(route('rooms.open-all', $room))->assertRedirect();
        auth()->logout();

        $this->as($room, $token)->get(route('play.show', $room))->assertOk()->assertSee('Wähl deinen Favoriten.');
        $this->as($room, $token)->post(route('play.choose', $room), ['options' => [$italiener->id]])->assertSessionHasNoErrors();
        $this->as($room, $token)->post(route('play.choose', $room), ['options' => [$grieche->id]])->assertSessionHasNoErrors();

        $this->assertSame([$grieche->id], $room->choices()->pluck('station_id')->all());
        $this->assertSame(1, $room->voterCount());
    }

    public function test_poll_enforces_max_choices(): void
    {
        $room = $this->poll(['max_choices' => 2]);
        $ids = $room->stations->pluck('id')->all();
        $token = $this->join($room, 'Lena');
        $room->update(['voting_open' => true]);

        $this->as($room, $token)->post(route('play.choose', $room), ['options' => $ids])->assertSessionHasErrors('options');
        $this->as($room, $token)->post(route('play.choose', $room), ['options' => [$ids[0], $ids[1]]])->assertSessionHasNoErrors();
        $this->assertSame(2, $room->choices()->count());
    }

    public function test_poll_respects_own_option_exclusion(): void
    {
        $room = $this->poll(['exclude_own' => '1']);
        [$italiener, $grieche] = $room->stations;
        $token = $this->join($room, 'Giuseppe', $italiener);
        $room->update(['voting_open' => true]);

        $this->as($room, $token)->post(route('play.choose', $room), ['options' => [$italiener->id]])->assertSessionHasErrors();
        $this->as($room, $token)->post(route('play.choose', $room), ['options' => [$grieche->id]])->assertSessionHasNoErrors();
    }

    public function test_poll_results_rank_by_votes(): void
    {
        $room = $this->poll();
        [$italiener, $grieche, $chinese] = $room->stations;
        $room->update(['voting_open' => true]);

        foreach ([['Anna', $grieche], ['Bernd', $grieche], ['Clara', $italiener]] as [$name, $option]) {
            $this->as($room, $this->join($room, $name))->post(route('play.choose', $room), ['options' => [$option->id]]);
        }

        $this->actingAs($room->owner)->post(route('rooms.reveal', $room), ['action' => 'all']);
        $this->assertFalse($room->fresh()->voting_open);

        $this->get(route('play.results', $room))
            ->assertOk()
            ->assertSeeInOrder(['Grieche', '67 %', 'Italiener', '33 %', 'Chinese', '0 %']);
    }

    public function test_join_ignores_own_entry_when_exclusion_is_off(): void
    {
        $room = $this->poll();
        $this->post(route('play.join', $room), ['name' => 'Paul', 'station_id' => $room->stations[0]->id]);

        $this->assertNull($room->participants()->first()->station_id);
    }

    public function test_contest_can_open_all_entries_at_once(): void
    {
        $room = $this->makeRoom(null, ['one_at_a_time' => '0', 'item_label' => 'Beitrag', 'items_label' => 'Beiträge']);
        [$a, $b, $c] = $room->stations;
        $token = $this->join($room, 'Jury');

        $this->actingAs($room->owner)->post(route('rooms.open-all', $room))->assertRedirect();
        auth()->logout();
        $this->assertSame(3, $room->stations()->where('status', 'open')->count());

        $this->as($room, $token)->post(route('play.vote', [$room, $a]), $this->scores($room, [5, 5]))
            ->assertRedirect(route('play.show', [$room, 'station' => $b->id]));
        $this->as($room, $token)->post(route('play.vote', [$room, $c]), $this->scores($room, [7, 7]))
            ->assertRedirect(route('play.show', [$room, 'station' => $b->id]));

        $this->as($room, $token)->get(route('play.show', $room))->assertOk()->assertSee($b->name)->assertSee('Alle Beiträge');
        $this->assertSame(2, Ballot::count());
    }

    public function test_one_at_a_time_rooms_cannot_open_all(): void
    {
        $room = $this->makeRoom();

        $this->actingAs($room->owner)->post(route('rooms.open-all', $room))->assertStatus(422);
    }

    public function test_photos_can_be_disabled(): void
    {
        Storage::fake('local');

        $room = $this->makeRoom(null, ['photos_enabled' => '0']);
        $token = $this->join($room, 'Fotograf');

        $this->as($room, $token)
            ->post(route('photos.store', [$room, $room->stations[0]]), ['photo' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json'])
            ->assertForbidden();
    }

    public function test_owner_can_change_labels_and_settings(): void
    {
        $room = $this->makeRoom();

        $this->actingAs($room->owner)->patch(route('rooms.settings', $room), [
            'item_label' => 'Haushalt',
            'items_label' => 'Haushalte',
            'photos_enabled' => '1',
        ])->assertSessionHasNoErrors();

        $room->refresh();
        $this->assertSame('Haushalte', $room->items_label);
        $this->assertFalse($room->exclude_own);
        $this->assertFalse($room->one_at_a_time);
        $this->actingAs($room->owner)->get(route('rooms.manage', $room))->assertSee('Haushalte');
    }

    public function test_duplicate_entries_show_one_message_per_value(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('rooms.create'))->post(route('rooms.store'), [
            'name' => 'Test', 'theme' => 'dark', 'mode' => 'score',
            'item_label' => 'Test', 'items_label' => 'Test',
            'criteria' => ['Test', 'test', 'Test'],
            'stations' => ['Test', 'Test', 'TEST'],
        ]);

        $response->assertSessionHasErrors(['stations.0', 'criteria.0']);
        $this->assertSame(0, Room::count());

        $html = $this->actingAs($user)->from(route('rooms.create'))->followingRedirects()->post(route('rooms.store'), [
            'name' => 'Test', 'theme' => 'dark', 'mode' => 'score',
            'item_label' => 'Test', 'items_label' => 'Test',
            'criteria' => ['Test', 'test', 'Test'],
            'stations' => ['Test', 'Test', 'TEST'],
        ])->getContent();

        $this->assertSame(1, substr_count($html, '<li>„Test“ steht mehrfach in der Liste.</li>'));
        $this->assertSame(1, substr_count($html, '<li>Das Kriterium „Test“ steht mehrfach in der Liste.</li>'));
    }

    public function test_super_admin_sets_site_theme(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $normal = User::factory()->create();

        $this->get(route('home'))->assertSee('data-theme="christmas"', false);

        $this->actingAs($normal)->put(route('admin.settings.update'), ['site_theme' => 'light'])->assertForbidden();
        $this->actingAs($admin)->put(route('admin.settings.update'), ['site_theme' => 'nope'])->assertSessionHasErrors('site_theme');
        $this->actingAs($admin)->put(route('admin.settings.update'), ['site_theme' => 'light'])->assertSessionHasNoErrors();

        $this->anonymous();
        auth()->logout();
        $this->get(route('home'))->assertSee('data-theme="light"', false);

        $room = $this->makeRoom(null, ['theme' => 'dark']);
        $this->anonymous()->get(route('play.show', $room))->assertSee('data-theme="dark"', false);
    }

    public function test_pages_have_preview_metadata_and_are_not_indexed(): void
    {
        $this->get(route('home'))
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertSee('<meta name="robots" content="noindex, nofollow, noarchive">', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('og-image.png', false);

        $room = $this->makeRoom();

        $this->anonymous()->get(route('play.show', $room))
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertSee('<meta property="og:title" content="'.e($room->name.' – Zettelfrei').'">', false)
            ->assertSee('Du wurdest zu „'.$room->name.'“ eingeladen.', false);
    }

    public function test_rating_style_is_chosen_per_room(): void
    {
        $room = $this->makeRoom(null, ['rating_style' => 'slider']);
        $this->assertSame('slider', $room->fresh()->rating_style);

        [$first] = $room->stations;
        $token = $this->join($room, 'Regler');
        $this->open($room, $first);

        $this->as($room, $token)->get(route('play.show', $room))->assertSee('rating-slider', false)->assertDontSee('class="lights', false);

        $this->actingAs($room->owner)->patch(route('rooms.settings', $room), [
            'item_label' => 'Familie', 'items_label' => 'Familien', 'one_at_a_time' => '1', 'rating_style' => 'stars',
        ])->assertSessionHasNoErrors();
        $this->assertSame('stars', $room->fresh()->rating_style);

        $this->actingAs($room->owner)->patch(route('rooms.settings', $room), [
            'item_label' => 'Familie', 'items_label' => 'Familien', 'rating_style' => 'dice',
        ])->assertSessionHasErrors('rating_style');
        auth()->logout();

        $this->as($room, $token)->post(route('play.vote', [$room, $first]), $this->scores($room, [3, 9]))->assertSessionHasNoErrors();
        $this->assertEqualsCanonicalizing([3, 9], Ballot::first()->ratings->pluck('score')->all());
    }

    public function test_super_admin_creates_demo_rooms(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs(User::factory()->create())->post(route('admin.demo'), ['template' => 'trees', 'state' => 'ready'])->assertForbidden();

        foreach (['trees', 'contest', 'poll'] as $template) {
            foreach (['running', 'ready'] as $state) {
                $response = $this->actingAs($admin)->post(route('admin.demo'), ['template' => $template, 'state' => $state]);
                $room = Room::latest('id')->first();
                $response->assertRedirect(route('rooms.manage', $room));

                $this->assertStringStartsWith('Demo:', $room->name);
                $this->assertSame(8, $room->participants()->count());
                $this->assertSame(5, $room->stations()->count());

                if ($room->isChoice()) {
                    $this->assertSame($state === 'running' ? 5 : 8, $room->voterCount());
                    $this->assertSame($state === 'running', $room->voting_open);
                } else {
                    $this->assertGreaterThan(0, $room->criteria()->count());
                    $this->assertGreaterThan(0, Ballot::whereIn('station_id', $room->stations()->select('id'))->count());
                    $open = $room->stations()->where('status', 'open')->count();
                    $this->assertSame($state === 'ready' ? 0 : ($room->one_at_a_time ? 1 : 5), $open);
                }

                if ($state === 'ready') {
                    $this->actingAs($admin)->post(route('rooms.reveal', $room), ['action' => 'all']);
                    $this->actingAs($admin)->get(route('play.results', $room))->assertOk()->assertSee('Das Ergebnis');
                }
            }
        }

        $trees = Room::where('name', 'Demo: Baumbewertung')->latest('id')->first();
        foreach ($trees->stations as $station) {
            $this->assertSame($station->eligibleVoterCount(), $station->ballots()->count());
        }
    }

    public function test_room_reset_clears_votes_and_optionally_keeps_people_and_photos(): void
    {
        Storage::fake('local');

        $room = $this->makeRoom();
        [$mueller, $schmidt] = [$room->stations[0], $room->stations[1]];
        $token = $this->join($room, 'Oma', $schmidt);
        $this->open($room, $mueller);
        $this->as($room, $token)->post(route('play.vote', [$room, $mueller]), $this->scores($room, [7, 9]))->assertRedirect();
        $this->as($room, $token)->post(route('photos.store', [$room, $mueller]), ['photo' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json'])->assertCreated();
        $this->actingAs($room->owner)->post(route('rooms.reveal', $room), ['action' => 'next'])->assertRedirect();

        $this->actingAs($room->owner)->post(route('rooms.reset', $room), ['keep_participants' => '1', 'keep_photos' => '1'])->assertRedirect(route('rooms.manage', $room));

        $room->refresh();
        $this->assertFalse($room->hasBallots());
        $this->assertSame(0, $room->reveal_step);
        $this->assertSame(['pending'], $room->stations()->pluck('status')->unique()->values()->all());
        $this->assertSame(1, $room->participants()->count());
        $this->assertSame(1, $room->photos()->count());

        $path = $room->photos()->first()->path;
        $this->actingAs($room->owner)->post(route('rooms.reset', $room))->assertRedirect();

        $this->assertSame(0, $room->participants()->count());
        $this->assertSame(0, $room->photos()->count());
        Storage::disk('local')->assertMissing($path);
        $this->assertSame(3, $room->stations()->count());
        $this->assertSame(2, $room->criteria()->count());
    }

    public function test_only_room_managers_can_reset(): void
    {
        $room = $this->makeRoom();

        $this->anonymous()->post(route('rooms.reset', $room))->assertForbidden();
        $this->actingAs(User::factory()->create())->post(route('rooms.reset', $room))->assertForbidden();
    }

    public function test_join_question_is_configurable(): void
    {
        $room = $this->makeRoom(null, ['own_question' => 'Zu welcher Familie gehörst du?', 'own_none_label' => 'Zu keiner davon']);

        $this->anonymous()->get(route('play.show', $room))->assertOk()
            ->assertSee('Zu welcher Familie gehörst du?')
            ->assertSee('Zu keiner davon')
            ->assertDontSee('Gehörst du selbst zu einem Eintrag?');

        $this->actingAs($room->owner)->patch(route('rooms.settings', $room), [
            'item_label' => 'Familie', 'items_label' => 'Familien', 'exclude_own' => '1',
            'own_question' => '', 'own_none_label' => '',
        ])->assertSessionHasNoErrors();
        auth()->logout();

        $this->anonymous()->get(route('play.show', $room))->assertSee('Gehörst du selbst zu einem Eintrag?');
    }

    public function test_photos_are_limited_per_entry(): void
    {
        Storage::fake('local');

        $room = $this->makeRoom();
        $station = $room->stations[0];
        $token = $this->join($room, 'Knipser');

        foreach (range(1, 3) as $i) {
            $this->as($room, $token)->post(route('photos.store', [$room, $station]), ['photo' => UploadedFile::fake()->image("$i.jpg")], ['Accept' => 'application/json'])->assertCreated();
        }

        $this->as($room, $token)->post(route('photos.store', [$room, $station]), ['photo' => UploadedFile::fake()->image('4.jpg')], ['Accept' => 'application/json'])->assertStatus(422);
        $this->assertSame(3, $station->photos()->count());
    }

    public function test_photos_can_be_limited_to_own_entry(): void
    {
        Storage::fake('local');

        $room = $this->makeRoom(null, ['photos_own_only' => '1']);
        [$mueller, $schmidt] = [$room->stations[0], $room->stations[1]];
        $own = $this->join($room, 'Müller-Oma', $mueller);
        $none = $this->join($room, 'Gast');
        $photo = fn () => ['photo' => UploadedFile::fake()->image('a.jpg')];

        $this->as($room, $own)->post(route('photos.store', [$room, $mueller]), $photo(), ['Accept' => 'application/json'])->assertCreated();
        $this->as($room, $own)->post(route('photos.store', [$room, $schmidt]), $photo(), ['Accept' => 'application/json'])->assertForbidden();
        $this->as($room, $none)->post(route('photos.store', [$room, $schmidt]), $photo(), ['Accept' => 'application/json'])->assertForbidden();

        $admin = User::factory()->create(['is_admin' => true]);
        $this->anonymous()->actingAs($admin)->post(route('photos.store', [$room, $schmidt]), $photo(), ['Accept' => 'application/json'])->assertCreated();
    }

    public function test_school_grades_rank_lower_grades_higher(): void
    {
        $room = $this->makeRoom(null, ['rating_style' => 'grades', 'criteria' => ['Gesamt'], 'exclude_own' => '0', 'one_at_a_time' => '0']);
        [$mueller, $schmidt] = [$room->stations[0], $room->stations[1]];
        $token = $this->join($room, 'Oma');
        $this->actingAs($room->owner)->post(route('rooms.open-all', $room))->assertRedirect();
        auth()->logout();

        $this->as($room, $token)->get(route('play.show', $room))->assertSee('sehr gut');
        $this->as($room, $token)->post(route('play.vote', [$room, $mueller]), $this->scores($room, [Room::gradeToScore(1)]))->assertRedirect();
        $this->as($room, $token)->post(route('play.vote', [$room, $schmidt]), $this->scores($room, [Room::gradeToScore(4)]))->assertRedirect();
        $this->as($room, $token)->post(route('play.vote', [$room, $room->stations[2]]), $this->scores($room, [7]))->assertSessionHasErrors();

        $results = \App\Support\Results::for($room->fresh());
        $this->assertSame('Müller', $results->first()['station']->name);
        $this->assertSame('1,00', $room->displayScore($results->first()['average']));

        $this->actingAs($room->owner)->post(route('rooms.reveal', $room), ['action' => 'all']);
        auth()->logout();
        $this->as($room, $token)->get(route('play.results', $room))->assertSee('Notenschnitt')->assertSee('4,00');

        $this->actingAs($room->owner)->patch(route('rooms.settings', $room), [
            'item_label' => 'Familie', 'items_label' => 'Familien', 'rating_style' => 'stars',
        ])->assertSessionHasErrors('rating_style');
        $this->assertTrue($room->fresh()->usesGrades());
    }
}

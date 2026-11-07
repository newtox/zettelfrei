<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int $station_id
 * @property string $voter_hash
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Rating> $ratings
 * @property-read int|null $ratings_count
 * @property-read \App\Models\Station $station
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ballot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ballot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ballot query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ballot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ballot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ballot whereStationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ballot whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ballot whereVoterHash($value)
 */
	class Ballot extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $room_id
 * @property int $station_id
 * @property string $voter_hash
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Room $room
 * @property-read \App\Models\Station $station
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Choice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Choice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Choice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Choice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Choice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Choice whereRoomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Choice whereStationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Choice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Choice whereVoterHash($value)
 */
	class Choice extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $room_id
 * @property string $name
 * @property int $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Room $room
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Criterion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Criterion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Criterion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Criterion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Criterion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Criterion whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Criterion wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Criterion whereRoomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Criterion whereUpdatedAt($value)
 */
	class Criterion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $room_id
 * @property int|null $station_id
 * @property string $name
 * @property string $token_hash
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Room $room
 * @property-read \App\Models\Station|null $station
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Participant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Participant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Participant query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Participant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Participant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Participant whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Participant whereRoomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Participant whereStationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Participant whereTokenHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Participant whereUpdatedAt($value)
 */
	class Participant extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $station_id
 * @property int|null $participant_id
 * @property string $path
 * @property int|null $width
 * @property int|null $height
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Participant|null $participant
 * @property-read \App\Models\Station $station
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Photo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Photo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Photo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Photo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Photo whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Photo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Photo whereParticipantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Photo wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Photo whereStationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Photo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Photo whereWidth($value)
 */
	class Photo extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property array<array-key, mixed> $criteria
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Preset availableFor(?\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Preset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Preset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Preset query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Preset whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Preset whereCriteria($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Preset whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Preset whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Preset whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Preset whereUserId($value)
 */
	class Preset extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $ballot_id
 * @property int $criterion_id
 * @property int $score
 * @property-read \App\Models\Ballot $ballot
 * @property-read \App\Models\Criterion $criterion
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating whereBallotId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating whereCriterionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating whereScore($value)
 */
	class Rating extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property string $code
 * @property string $admin_token
 * @property bool $keep
 * @property \Illuminate\Support\Carbon|null $last_activity_at
 * @property string $theme
 * @property string $mode
 * @property string $item_label
 * @property string $items_label
 * @property bool $exclude_own
 * @property bool $photos_enabled
 * @property bool $one_at_a_time
 * @property bool $voting_open
 * @property int $max_choices
 * @property int $reveal_step
 * @property bool $join_locked
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Choice> $choices
 * @property-read int|null $choices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Criterion> $criteria
 * @property-read int|null $criteria_count
 * @property-read \App\Models\User|null $owner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Participant> $participants
 * @property-read int|null $participants_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Photo> $photos
 * @property-read int|null $photos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Station> $stations
 * @property-read int|null $stations_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereAdminToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereExcludeOwn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereItemLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereItemsLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereJoinLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereKeep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereLastActivityAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereMaxChoices($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereOneAtATime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room wherePhotosEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereRevealStep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereTheme($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room whereVotingOpen($value)
 */
	class Room extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $room_id
 * @property string $name
 * @property int $position
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ballot> $ballots
 * @property-read int|null $ballots_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Choice> $choices
 * @property-read int|null $choices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Participant> $members
 * @property-read int|null $members_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Photo> $photos
 * @property-read int|null $photos_count
 * @property-read \App\Models\Room $room
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Station newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Station newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Station query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Station whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Station whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Station whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Station wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Station whereRoomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Station whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Station whereUpdatedAt($value)
 */
	class Station extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $is_admin
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Preset> $presets
 * @property-read int|null $presets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Room> $rooms
 * @property-read int|null $rooms_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}


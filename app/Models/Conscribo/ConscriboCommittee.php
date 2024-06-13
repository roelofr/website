<?php

declare(strict_types=1);

namespace App\Models\Conscribo;

use App\Models\Google\GoogleMailList;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Permission\Traits\HasRoles;

/**
 * A committee in Conscribo.
 *
 * @property int $id
 * @property string $conscribo_id
 * @property string $name
 * @property string $email
 * @property mixed $aliases
 * @property null|\Illuminate\Support\Carbon $created_at
 * @property null|\Illuminate\Support\Carbon $updated_at
 * @property-read null|GoogleMailList $mailList
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conscribo\ConscriboUser> $members
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @method static \Database\Factories\Conscribo\ConscriboCommitteeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ConscriboCommittee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ConscriboCommittee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ConscriboCommittee permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|ConscriboCommittee query()
 * @method static \Illuminate\Database\Eloquent\Builder|ConscriboCommittee role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|ConscriboCommittee withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|ConscriboCommittee withoutRole($roles, $guard = null)
 * @mixin \Eloquent
 */
class ConscriboCommittee extends Model
{
    use HasFactory;
    use HasRoles;

    /**
     * Ensure web guards are used on these non-user-models.
     */
    protected static string $guard_name = 'web';

    protected $attributes = [
        'aliases' => '[]',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'conscribo_id',
        'name',
        'email',
        'aliases',
    ];

    /**
     * Returns the members of this committee.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(ConscriboUser::class, 'conscribo_committee_user')
            ->using(CommitteeUser::class);
    }

    /**
     * Returns the mailing list created for this committee, if any.
     * @return HasOne<GoogleMailList>
     */
    public function mailList(): HasOne
    {
        return $this->hasOne(GoogleMailList::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aliases' => 'collection',
        ];
    }
}

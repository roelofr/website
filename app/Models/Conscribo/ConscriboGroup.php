<?php

declare(strict_types=1);

namespace App\Models\Conscribo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Traits\HasRoles;

/**
 * A Group in Conscribo. Conscribo allows them to contain any type of entity,
 * but we're only tying them to users.
 *
 * @property int $id
 * @property string $conscribo_id
 * @property string $name
 * @property null|\Illuminate\Support\Carbon $created_at
 * @property null|\Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conscribo\ConscriboUser> $users
 * @method static \Database\Factories\Conscribo\ConscriboGroupFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ConscriboGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ConscriboGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ConscriboGroup permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|ConscriboGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder|ConscriboGroup role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|ConscriboGroup withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|ConscriboGroup withoutRole($roles, $guard = null)
 * @mixin \Eloquent
 */
class ConscriboGroup extends Model
{
    use HasFactory;
    use HasRoles;

    /**
     * Ensure web guards are used on these non-user-models.
     */
    protected static string $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'conscribo_id',
        'name',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(ConscriboUser::class, 'conscribo_group_user');
    }
}

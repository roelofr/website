<?php

declare(strict_types=1);

namespace App\Models\Conscribo;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * A user retrieved from Conscribo.
 *
 * @property int $id
 * @property string $conscribo_id
 * @property string $conscribo_selector
 * @property string $first_name
 * @property null|string $infix
 * @property string $last_name
 * @property string $email
 * @property null|string $address
 * @property null|\Illuminate\Support\Carbon $created_at
 * @property null|\Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conscribo\ConscriboCommittee> $committees
 * @property-read string $conscribo_name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conscribo\ConscriboGroup> $groups
 * @property-read string $name
 * @method static \Database\Factories\Conscribo\ConscriboUserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ConscriboUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ConscriboUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ConscriboUser query()
 * @mixin \Eloquent
 */
class ConscriboUser extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'conscribo_id',
        'conscribo_selector',
        'first_name',
        'infix',
        'last_name',
        'email',
        'address',
    ];

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(ConscriboGroup::class, 'conscribo_group_user');
    }

    public function committees(): BelongsToMany
    {
        return $this->belongsToMany(ConscriboCommittee::class, 'conscribo_committee_user')
            ->using(CommitteeUser::class);
    }

    /**
     * Returns an attribute that computes the full name.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): string => implode(' ', array_filter([
                $this->first_name,
                $this->infix,
                $this->last_name,
            ])),
        );
    }

    /**
     * Returns the computed Conscribo Name, which might differ from the actual name, but is used for certain
     * API calls.
     */
    protected function conscriboName(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): string => Str::of($attributes['conscribo_selector'])
                ->after("{$attributes['conscribo_id']}:")
                ->trim()
                ->value(),
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'address' => 'encrypted:json',
        ];
    }
}

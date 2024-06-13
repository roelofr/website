<?php

declare(strict_types=1);

namespace App\Models\Google;

use App\Models\Conscribo\ConscriboCommittee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Google\GoogleMailList.
 *
 * @property int $id
 * @property null|string $directory_id
 * @property null|int $conscribo_id
 * @property null|int $conscribo_committee_id
 * @property string $name
 * @property string $email
 * @property array $aliases
 * @property null|\Illuminate\Support\Carbon $created_at
 * @property null|\Illuminate\Support\Carbon $updated_at
 * @property null|\Illuminate\Support\Carbon $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Google\GoogleMailListChange> $changes
 * @property-read null|ConscriboCommittee $conscriboCommittee
 * @method static \Database\Factories\Google\GoogleMailListFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleMailList newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleMailList newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleMailList onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleMailList query()
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleMailList withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleMailList withoutTrashed()
 * @mixin \Eloquent
 */
class GoogleMailList extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'aliases' => 'json',
    ];

    protected $fillable = [
        'directory_id',
        'conscribo_id',
        'name',
        'email',
        'aliases',
    ];

    public function conscriboCommittee(): BelongsTo
    {
        return $this->belongsTo(ConscriboCommittee::class);
    }

    /**
     * @return HasMany<GoogleMailListChange>
     */
    public function changes(): HasMany
    {
        return $this->hasMany(GoogleMailListChange::class);
    }
}

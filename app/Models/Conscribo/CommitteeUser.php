<?php

declare(strict_types=1);

namespace App\Models\Conscribo;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Describes a link between a Conscribo User and a Conscribo Committee, to
 * keep track of the owners of the committee.
 *
 * @property int $conscribo_user_id
 * @property int $conscribo_committee_id
 * @property null|string $role
 * @property int $is_owner
 * @method static \Illuminate\Database\Eloquent\Builder|CommitteeUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CommitteeUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CommitteeUser query()
 * @mixin \Eloquent
 */
class CommitteeUser extends Pivot
{
    protected $table = 'conscribo_committee_user';
}

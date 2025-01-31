<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\MemberReferral.
 *
 * @property int $id
 * @property null|\Illuminate\Support\Carbon $created_at
 * @property null|\Illuminate\Support\Carbon $updated_at
 * @property string $subject
 * @property string $referred_by
 * @property null|int $user_id
 * @property-read null|\App\Models\User $user
 * @method static \Database\Factories\MemberReferralFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|MemberReferral newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberReferral newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MemberReferral query()
 * @mixin \Eloquent
 */
class MemberReferral extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'subject',
        'referred_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

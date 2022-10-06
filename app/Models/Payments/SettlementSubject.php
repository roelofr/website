<?php

declare(strict_types=1);

namespace App\Models\Payments;

use App\Casts\MoneyCast;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Metadata of payment settlements.
 *
 * @property int $id
 * @property int $settlement_id
 * @property string $subject_type
 * @property int $subject_id
 * @property Money $amount
 * @method static \Illuminate\Database\Eloquent\Builder|SettlementSubject newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SettlementSubject newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SettlementSubject query()
 * @mixin \Eloquent
 */
class SettlementSubject extends Pivot
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payment_settlement_subject';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => MoneyCast::class,
    ];
}

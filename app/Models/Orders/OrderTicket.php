<?php

declare(strict_types=1);

namespace App\Models\Orders;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * App\Models\Orders\OrderTicket.
 *
 * @property int $id
 * @property int $order_id
 * @property int $ticket_id
 * @property int $event_id
 * @property null|string $barcode
 * @property string $barcode_type
 * @property null|\Brick\Money\Money $price
 * @property array $data
 * @property null|\Illuminate\Support\Carbon $created_at
 * @property null|\Illuminate\Support\Carbon $updated_at
 * @property null|string $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|OrderTicket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderTicket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderTicket query()
 * @mixin \Eloquent
 */
class OrderTicket extends Pivot
{
    public $incrementing = true;

    protected $table = 'order_ticket';

    protected $casts = [
        'price' => MoneyCast::class,
        'data' => 'encrypted:json',
    ];
}

<?php

declare(strict_types=1);

namespace App\Models\Orders;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * App\Models\Orders\OrderProduct.
 *
 * @property int $id
 * @property null|\Illuminate\Support\Carbon $created_at
 * @property null|\Illuminate\Support\Carbon $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|OrderProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderProduct query()
 * @mixin \Eloquent
 */
class OrderProduct extends Pivot
{
    public $incrementing = true;

    protected $table = 'order_product';
}

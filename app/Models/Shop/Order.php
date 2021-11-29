<?php

declare(strict_types=1);

namespace App\Models\Shop;

use App\Contracts\Payments\Payable;
use App\Enums\PaymentStatus;
use App\Fluent\Payment as PaymentFluent;
use App\Models\Traits\HasPayments;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use RuntimeException;

/**
 * App\Models\Shop\Order.
 *
 * @property int $id
 * @property string $number
 * @property int $user_id
 * @property null|string $payment_id
 * @property null|\Illuminate\Support\Carbon $created_at
 * @property null|\Illuminate\Support\Carbon $updated_at
 * @property null|\Illuminate\Support\Carbon $expires_at
 * @property null|\Illuminate\Support\Carbon $paid_at
 * @property null|\Illuminate\Support\Carbon $shipped_at
 * @property null|\Illuminate\Support\Carbon $cancelled_at
 * @property int $price
 * @property int $fee
 * @property-read string $payment_status
 * @property-read string $status
 * @property-read \App\Models\Payment[]|\Illuminate\Database\Eloquent\Collection $payments
 * @property-read User $user
 * @property-read \App\Models\Shop\ProductVariant[]|\Illuminate\Database\Eloquent\Collection $variants
 * @method static Builder|Order cancelled()
 * @method static Builder|Order newModelQuery()
 * @method static Builder|Order newQuery()
 * @method static Builder|Order paid()
 * @method static Builder|Order query()
 * @method static Builder|Order unpaid()
 * @method static Builder|Order whereCancelled()
 * @method static Builder|Order whereExpired()
 * @method static Builder|Order wherePaid()
 * @mixin \Eloquent
 */
class Order extends Model implements Payable
{
    use HasPayments;

    protected $table = 'shop_orders';

    protected $casts = [
        'price' => 'int',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $fillable = [
        'price',
        'fee',
    ];

    /**
     * Bind invoice ID handling.
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function (self $order) {
            // Set expiration
            $order->expires_at ??= Date::now()->addDay();

            // Set order number
            $order->number = self::determineOrderNumber($order);
        });
    }

    /**
     * Assigns an order number if not yet set.
     * @return void
     */
    public static function determineOrderNumber(self $order): string
    {
        $targetDate = $order->created_at ?? Date::now();

        // Get invoice number
        $startOfMonth = Date::now()->firstOfMonth();

        $orderCount = self::query()
            ->whereBetween('created_at', [$startOfMonth, $targetDate])
            ->when($order->id, fn (Builder $query) => $query->where('id', '<', $order->id))
            ->count();

        // Set invoice ID
        return sprintf(
            '%02d.%02d.%03d',
            $targetDate->century % 100,
            $targetDate->month,
            $orderCount + 1,
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'shop_order_product_variant')
            ->withoutGlobalScopes()
            ->using(OrderProduct::class)
            ->withPivot(['quantity', 'price']);
    }

    public function getStatusAttribute(): string
    {
        if ($this->paid_at) {
            return PaymentStatus::PAID;
        }
        if ($this->cancelled_at) {
            return PaymentStatus::CANCELLED;
        }
        if ($this->expires_at !== null && $this->expires_at < Date::now()) {
            return PaymentStatus::EXPIRED;
        }

        return PaymentStatus::OPEN;
    }

    /**
     * Eagerly load data used in common views.
     * @return Order
     */
    public function hungry(): self
    {
        return $this->loadMissing([
            'user',
            'variants',
            'variants.product',
        ]);
    }

    public function toPayment(): PaymentFluent
    {
        $payment = PaymentFluent::make()
            ->withDescription("Webshop bestelling {$this->number}")
            ->withModel($this)
            ->withNumber($this->number)
            ->withUser($this->user);

        foreach ($this->variants as $variant) {
            $payment->addLine(
                $variant->display_name,
                $variant->pivot->price,
                $variant->pivot->quantity,
            );
        }

        $payment->addLine(__('Fees'), (int) $this->fee);

        throw_unless($payment->getSum() === $this->price, RuntimeException::class, 'Price mismatch');

        return $payment;
    }

    public function scopeUnpaid(Builder $query): void
    {
        $query
            ->whereNull('paid_at')
            ->whereNull('cancelled_at');
    }

    public function scopePaid(Builder $query): void
    {
        $query
            ->whereNotNull('paid_at')
            ->whereNull('cancelled_at');
    }

    public function scopeCancelled(Builder $query): void
    {
        $query
            ->whereNotNull('cancelled_at');
    }
}

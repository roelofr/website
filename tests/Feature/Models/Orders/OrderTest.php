<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Orders;

use App\Models\Orders\Order;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class OrderTest extends TestCase
{
    /**
     * Happy trail test for order number creation.
     */
    public function test_order_number_creation(): void
    {
        Date::setTestNow('2024-12-24 12:00:00');

        $order = Order::factory()->create();
        $order2 = Order::factory()->create();

        $this->assertInstanceOf(Order::class, $order);
        $this->assertInstanceOf(Order::class, $order2);

        $this->assertNotNull($order->number);
        $this->assertNotNull($order2->number);
        $this->assertStringStartsWith('2412.', $order->number);
        $this->assertStringStartsWith('2412.', $order2->number);
        $this->assertNotEquals($order->number, $order2->number);
    }

    /**
     * Edge case where no order numbers could be generated.
     */
    public function test_order_number_creation_picking(): void
    {
        Date::setTestNow('2024-12-24 12:00:00');

        $orders = Order::factory()
            ->times(Order::ORDER_CREATION_ATTEMPTS)
            ->create()
            ->pluck('number')
            ->map(fn (string $number) => Str::after($number, '.'))
            ->all();

        Str::createRandomStringsUsingSequence($orders);

        $order = Order::factory()->make();
        $this->assertInstanceOf(Order::class, $order);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not determine a unique order number.');

        $order->save();
    }

    /**
     * Test that order numbers are always assigned, even when somehow set.
     */
    public function test_order_numbers_are_always_assigned(): void
    {
        $order = Order::factory()->make();
        $order->number = 'test';
        $order->save();

        $this->assertNotEquals('test', $order->number);
    }
}

<?php

declare(strict_types=1);

namespace App\Notifications\Shop;

use App\Helpers\Str;
use App\Models\Shop\Order;
use Illuminate\Notifications\Messages\MailMessage;

class OrderRefunded extends ShopNotification
{
    protected int $amount;

    protected string $account;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Order $order, int $amount, string $account)
    {
        parent::__construct($order);

        $this->amount = $amount;
        $this->account = $account;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $order = $this->order;
        $amount = Str::price($this->amount);

        return (new MailMessage())
            ->subject("Terugbetaling voor bestelling {$order->number} verzonden")
            ->greeting("Beste {$notifiable->first_name},")
            ->line("Er is een terugbetaling verstuurd naar Mollie voor bestelling {$order->number}.")
            ->line("Binnen enkele dagen krijg je het bedrag van {$amount} teruggestort op je bankrekening eindigend op {$this->account}.")
            ->action(
                'Bekijk bestelling',
                route('shop.order.show', $order),
            )
            ->salutation('Tot snel!');
    }
}

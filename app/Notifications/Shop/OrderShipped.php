<?php

declare(strict_types=1);

namespace App\Notifications\Shop;

use Illuminate\Notifications\Messages\MailMessage;

class OrderShipped extends ShopNotification
{
    /**
     * Get the mail representation of the notification.
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $order = $this->order;
        $date = $order->created_at->isoFormat('dddd D MMMM');

        return (new MailMessage())
            ->subject("Bestelling {$order->number} afgerond")
            ->greeting("Beste {$notifiable->first_name},")
            ->line("Bij deze even een bevestiging dat je bestelling van {$date} (bestelnummer {$order->number}) is afgerond.")
            ->line('Mochten er nog problemen zijn met je aankoop, neem dan contact op met het bestuur.')
            ->action(
                'Bekijk bestelling',
                route('shop.order.show', $order),
            )
            ->salutation('Tot snel!');
    }
}

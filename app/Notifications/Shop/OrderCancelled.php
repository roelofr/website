<?php

declare(strict_types=1);

namespace App\Notifications\Shop;

use Illuminate\Notifications\Messages\MailMessage;

class OrderCancelled extends ShopNotification
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
            ->subject("Bestelling {$order->number} geannuleerd")
            ->greeting("Beste {$notifiable->first_name},")
            ->line("Je bestelling van {$date} (bestelnummer {$order->number}) is geannuleerd.")
            ->line('Als je toch de producten wil kopen, kan je hieronder de bestelling bekijken ter referentie.')
            ->action(
                'Bekijk bestelling',
                route('shop.order.show', $order),
            )
            ->salutation('Tot snel!');
    }
}

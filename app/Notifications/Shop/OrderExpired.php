<?php

declare(strict_types=1);

namespace App\Notifications\Shop;

use Illuminate\Notifications\Messages\MailMessage;

class OrderExpired extends ShopNotification
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
            ->subject("Bestelling {$order->number} verlopen")
            ->greeting("Beste {$notifiable->first_name},")
            ->line("Je hebt op {$date} een bestelling geplaatst (nummertje {$order->number}), maar deze niet op tijd betaald.")
            ->line('Omdat je met jouw bestelling ook een stukje voorraad dibst, moet je op tijd betalen.')
            ->line("**BUT YOU DIDN'T, DID YOU NOW!?**")
            ->line('Dus, om deze reden is jouw bestelling het raam uit gegooit. Als je toch de producten wil kopen, mag je opnieuw naar de webshop.')
            ->action(
                'Bekijk bestelling',
                route('shop.order.show', $order),
            )
            ->salutation('Tot snel!');
    }
}

<?php

declare(strict_types=1);

namespace App\Notifications\Shop;

use App\Helpers\Str;
use Illuminate\Notifications\Messages\MailMessage;

class ExpiresSoonReminder extends ShopNotification
{
    /**
     * Get the mail representation of the notification.
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $order = $this->order;
        $value = Str::price($this->order->price);

        $expiration = $order->expires_at->isoFormat('dddd D MMMM, \o\m HH:mm');
        $orderDate = $this->order->created_at->isoFormat('dddd D MMMM');
        $orderTime = $this->order->created_at->isoFormat('HH:mm');

        return (new MailMessage())
            ->subject('Je webwinkel bestelling verloopt bijna!')
            ->greeting("Beste {$notifiable->first_name},")
            ->line("Op {$orderDate} om {$orderTime} heb je een bestelling geplaatst in de Gumbo webwinkel ter waarde van {$value}.")
            ->line("Deze bestelling moet je betaald hebben voor {$expiration}, anders annuleren we deze.")
            ->action('Nu betalen', url('/'))
            ->line('Als je geen behoefte hebt om deze bestelling af te ronden, dan hoef je niks te doen.')
            ->salutation('Tot snel!');
    }
}

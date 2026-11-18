<?php

namespace App\Notifications;

use App\Services\Notifier;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification standard de la plateforme : un titre, un message, un lien.
 * Stockée en base ; envoyée par e-mail seulement si GKAS_MAIL_NOTIFICATIONS=true.
 */
abstract class GkasNotification extends Notification
{
    /** @return array{title: string, message: string, url: ?string, level?: string} */
    abstract protected function content(object $notifiable): array;

    public function via(object $notifiable): array
    {
        return Notifier::channels($notifiable);
    }

    public function toArray(object $notifiable): array
    {
        return $this->content($notifiable) + ['level' => 'info'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->content($notifiable);
        $mail = (new MailMessage)
            ->subject($data['title'].' — '.config('app.name'))
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line($data['message']);

        if (! empty($data['url'])) {
            $mail->action('Ouvrir', $data['url']);
        }

        return $mail->salutation('L\'équipe GKAS Shop');
    }
}

<?php

namespace App\Notifications;

use App\Models\UserBan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserBannedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly UserBan $ban) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Your account has been banned')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your account has been banned, so you can no longer sign in or use the app.')
            ->line('Reason: '.$this->ban->reason);

        if ($this->ban->expires_at) {
            $message->line('The ban expires on '.$this->ban->expires_at->toDayDateTimeString().'.');
        } else {
            $message->line('This ban is permanent.');
        }

        return $message->line(
            'If you believe this ban is a mistake, you can respond in the app, in the ban conversation.'
        );
    }
}

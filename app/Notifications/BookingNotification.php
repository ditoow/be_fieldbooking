<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingNotification extends Notification
{
    use Queueable;

    protected string $title;
    protected string $message;
    protected string $type;
    protected ?int $bookingId;

    public function __construct(string $title, string $message, string $type, ?int $bookingId = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->bookingId = $bookingId;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'booking_id' => $this->bookingId,
        ];
    }
}

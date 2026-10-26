<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

class DeviceStatusNotification extends Notification
{
    use Queueable;

    public $device;
    public $status;
    public $time;

    /**
     * Create a new notification instance.
     */
    public function __construct($device, $status, $time)
    {
        $this->device = $device;
        $this->status = $status; // 'offline' or 'online'
        $this->time = $time;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // For standard setup without installing complex packages,
        // we'll format a custom string array, but typically you'd use Telegram notification channel.
        // We'll prepare it for a simple HTTP request instead if needed, but let's assume Laravel generic integration:
        return ['telegram'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toTelegram($notifiable)
    {
        // This method structure assumes using `laravel-notification-channels/telegram`
        // but if it's not installed, we'll manually send it via Controller.
        // To be safe and minimal per requirements, we will handle the actual HTTP post
        // in the Controller if this fails, or just return an array if we write a custom channel.

        $emoji = $this->status === 'online' ? '✅' : '❌';
        $message = "{$emoji} <b>Device {$this->status}</b>\n";
        $message .= "<b>Name:</b> {$this->device->name}\n";
        $message .= "<b>IP:</b> {$this->device->ip_address}\n";
        $message .= "<b>Time:</b> {$this->time->format('Y-m-d H:i:s')}";

        return $message;
    }
}

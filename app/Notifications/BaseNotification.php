<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification
{
    protected array $dynamicChannels = [];

    protected array $defaultChannels = ['database'];

    public function setDynamicChannels(array $channels): void
    {
        $this->dynamicChannels = $channels;
    }

    public function via($notifiable): array
    {
        return ! empty($this->dynamicChannels)
            ? $this->dynamicChannels
            : $this->defaultChannels;
    }
}

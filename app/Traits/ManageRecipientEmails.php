<?php

namespace App\Traits;

trait ManageRecipientEmails
{
    protected function getRecipientEmail($notifiable)
    {
        return app()->environment('production')
            ? $notifiable->email // Use actual recipient's email in production
            : config('mail.test_recipient'); // Use test email in non-production environments
    }
}

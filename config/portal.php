<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kernel configs
    |--------------------------------------------------------------------------
    |
    | Toggle active/inactive kernel command for task scheduling.
    |
    */
    'kernel' => [
        'process_default_job' => true,
        'process_in_app_notification_job' => true,
        'process_mail_job' => true,
        'process_royalty_job' => true,
        'process_notify_reminder_job' => true,
        'process_generate_report' => true,
    ],
];

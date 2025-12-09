<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Default job
Schedule::call(function () {
    if (config('portal.kernel.process_default_job', false)) {
        Artisan::call('queue:work', [
            '--queue' => 'default',
            '--timeout' => 300,
            '--stop-when-empty' => true,
        ]);
    }
})->everyMinute();

// In-app notifications
Schedule::call(function () {
    if (config('portal.kernel.process_in_app_notification_job', false)) {
        Artisan::call('queue:work', [
            '--queue' => 'in-app-notification',
            '--timeout' => 60,
            '--stop-when-empty' => true,
        ]);
    }
})->everyMinute();

// Mail jobs
Schedule::call(function () {
    if (config('portal.kernel.process_mail_job', false)) {
        Artisan::call('queue:work', [
            '--queue' => 'mail-high-priority,mail',
            '--timeout' => 60,
            '--stop-when-empty' => true,
        ]);
    }
})->everyMinute();

// Royalty queue
Schedule::call(function () {
    if (config('portal.kernel.process_royalty_job', false)) {
        Artisan::call('queue:work', [
            '--queue' => 'royalty',
            '--timeout' => 600,
            '--stop-when-empty' => true,
        ]);
    }
})->everyMinute();

// Report generation: dispatch then process
Schedule::call(function () {
    if (config('portal.kernel.process_generate_report', false)) {
        // Step 1: Dispatch the report job
        Artisan::call('generate:reports');

        // Step 2: Process the generate-report queue
        Artisan::call('queue:work', [
            '--queue' => 'generate-report',
            '--timeout' => 600,
            '--stop-when-empty' => true,
        ]);
    }
})->everyMinute();

// Reminder job daily
Schedule::call(function () {
    if (config('portal.process_notify_reminder_job', false)) {
        Artisan::call('reminders:notify-due');
    }
})->dailyAt('09:00')->timezone('Asia/Manila');

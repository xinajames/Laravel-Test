<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;

class DateHelper
{
    private static function getUserTimezone(): string
    {
        return (auth()->check() && auth()->user()->timezone)
            ? auth()->user()->timezone
            : config('app.timezone');
    }

    public static function changeDateFormat($date, $format = 'M d, Y'): string
    {
        $timezone = self::getUserTimezone();

        return Carbon::parse($date, 'UTC') // assume $date is in UTC
            ->setTimezone($timezone) // convert to user's timezone
            ->format($format);
    }

    public static function changeDateTimeFormat(?string $date, string $format = 'M d, Y g:i A'): ?string
    {
        if ($date === null) {
            return null;
        }

        $timezone = self::getUserTimezone();

        return Carbon::parse($date, 'UTC')
            ->setTimezone($timezone)
            ->format($format);
    }

    public static function convertDateFormInput($date): string
    {
        $timezone = self::getUserTimezone();

        return Carbon::parse($date, $timezone) // interpret input as user's local time
            ->setTimezone('UTC') // convert to UTC for storage
            ->toDateTimeString();
    }

    public static function changeTimeFormat($date): string
    {
        $timezone = self::getUserTimezone();

        return Carbon::parse($date, 'UTC')
            ->setTimezone($timezone)
            ->format('g:i A');
    }
}

<?php

namespace App\Helper;

use Carbon\Carbon;

class ValueDateHelper
{
    /**
     * Determine the appropriate Value Date for a transaction.
     * 
     * Rules:
     * 1. If transaction create_date time is after business_hours_end (default: 16:00),
     *    the value date moves to the next calendar day.
     * 2. If the resulting date falls on a weekend (Friday or Saturday in Bangladesh)
     *    or a recognized bank holiday, advance day by day until the next working banking day.
     *
     * @param Carbon|string|null $createDate
     * @return Carbon
     */
    public static function resolve($createDate = null): Carbon
    {
        if (empty($createDate)) {
            $date = Carbon::now();
        } elseif ($createDate instanceof Carbon) {
            $date = $createDate->copy();
        } else {
            $date = Carbon::parse($createDate);
        }

        $businessHoursEnd = (string) config('bkash.business_hours_end', '16:00');
        [$cutOffHour, $cutOffMinute] = array_pad(explode(':', $businessHoursEnd), 2, 0);

        $cutOffToday = $date->copy()->setTime((int) $cutOffHour, (int) $cutOffMinute, 0);

        // If transaction happened after banking cut-off time, it is settled on next day
        if ($date->greaterThan($cutOffToday)) {
            $date->addDay()->startOfDay();
        }

        // Advance past weekends (Friday=5, Saturday=6) and listed bank holidays
        while (static::isWeekendOrHoliday($date)) {
            $date->addDay()->startOfDay();
        }

        return $date;
    }

    /**
     * Check if a given date falls on a weekend (Friday/Saturday) or configured bank holiday.
     */
    public static function isWeekendOrHoliday(Carbon $date): bool
    {
        // Bangladesh Bank weekend: Friday (5) and Saturday (6)
        if ($date->isFriday() || $date->isSaturday()) {
            return true;
        }

        $dateStr = $date->format('Y-m-d');
        $holidays = (array) config('bkash.bank_holidays', []);

        return in_array($dateStr, $holidays, true);
    }
}

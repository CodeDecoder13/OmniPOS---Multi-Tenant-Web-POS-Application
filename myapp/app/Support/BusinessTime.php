<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * "Today" for a shop is a local day, but timestamps are stored in UTC.
 *
 * Storage stays UTC (changing it would shift every existing row by hours). Instead, every question
 * about a local day converts its edges to UTC (startOfDay / endOfDay) and every GROUP BY day/hour uses
 * the column converted to local time (localSql). In Manila (UTC+8) a sale at 07:00 on the 5th
 * therefore counts on the 5th, not the 4th.
 */
final class BusinessTime
{
    public static function tz(): string
    {
        return config('app.business_timezone', 'Asia/Manila');
    }

    /** Local "now" (for picking today's date). */
    public static function now(): Carbon
    {
        return Carbon::now(self::tz());
    }

    /** Local midnight today, in the business timezone. */
    public static function today(): Carbon
    {
        return self::now()->startOfDay();
    }

    /** UTC instant for the start of the local calendar day containing the given date (or Y-m-d string). */
    public static function startOfDay(CarbonInterface|string $date): Carbon
    {
        return Carbon::parse(self::dateOnly($date), self::tz())->startOfDay()->utc();
    }

    /** UTC instant for the very end of that local calendar day. */
    public static function endOfDay(CarbonInterface|string $date): Carbon
    {
        return Carbon::parse(self::dateOnly($date), self::tz())->endOfDay()->utc();
    }

    /**
     * SQL expression turning a stored UTC column into local time, for GROUP BY day/hour/weekday.
     * Only Postgres can do it; other drivers (dev sqlite) get the column unchanged.
     */
    public static function localSql(string $column): string
    {
        if (DB::getDriverName() !== 'pgsql') {
            return $column;
        }

        $tz = str_replace("'", '', self::tz());

        return "(({$column} AT TIME ZONE 'UTC') AT TIME ZONE '{$tz}')";
    }

    private static function dateOnly(CarbonInterface|string $date): string
    {
        return $date instanceof CarbonInterface ? $date->toDateString() : Carbon::parse($date)->toDateString();
    }
}

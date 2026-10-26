<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Text search that behaves the same for people: "coke" finds "Coke", and a typed % or _ is just a character.
 *
 * Postgres LIKE is case sensitive, so it needs ILIKE. Other drivers (sqlite in dev) are already case insensitive.
 *
 *   $q->where('name', Like::op(), Like::contains($search));
 */
final class Like
{
    public static function op(): string
    {
        return DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    public static function contains(string $search): string
    {
        return '%'.addcslashes($search, '%_\\').'%';
    }
}

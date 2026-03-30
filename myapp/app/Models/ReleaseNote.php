<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReleaseNote extends Model
{
    protected $fillable = [
        'title',
        'version',
        'summary',
        'items',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeNewerThan(Builder $query, ?int $id): Builder
    {
        if ($id) {
            return $query->where('id', '>', $id);
        }

        return $query;
    }
}

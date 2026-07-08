<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nav extends Model
{
    protected $table = 'pd_navs';

    public $timestamps = false;

    protected $fillable = [
        'title',
        'url',
        'icon_type',
        'icon_value',
        'display_order',
        'is_enabled',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Nav $nav): void {
            if (empty($nav->created_at)) {
                $nav->created_at = now();
            }
        });
    }
}

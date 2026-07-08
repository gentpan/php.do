<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    protected $table = 'pd_ads';

    const CREATED_AT = null;

    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'position',
        'title',
        'image_path',
        'link_url',
        'width',
        'height',
        'is_enabled',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'updated_at' => 'datetime',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OauthAccount extends Model
{
    protected $table = 'pd_oauth';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_uid',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}

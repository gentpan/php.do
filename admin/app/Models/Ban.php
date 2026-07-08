<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ban extends Model
{
    protected $table = 'pd_bans';

    public $timestamps = false;

    protected $fillable = [
        'ip',
        'reason',
        'expires_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Ban $ban): void {
            if (empty($ban->created_at)) {
                $ban->created_at = now();
            }
        });
    }
}

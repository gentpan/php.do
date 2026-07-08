<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineDaily extends Model
{
    protected $table = 'pd_online_daily';

    protected $primaryKey = 'day_date';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'day_date',
        'peak_total',
        'peak_members',
        'peak_guests',
        'peak_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'peak_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineSession extends Model
{
    protected $table = 'qf_online';

    protected $primaryKey = 'session_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'user_id',
        'ip',
        'user_agent',
        'last_seen',
    ];

    protected function casts(): array
    {
        return [
            'last_seen' => 'datetime',
        ];
    }
}

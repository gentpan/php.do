<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointsLog extends Model
{
    protected $table = 'pd_points_log';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'delta',
        'balance',
        'reason',
        'ref_type',
        'ref_id',
        'note',
        'operator_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

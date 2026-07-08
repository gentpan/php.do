<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Thread extends Model
{
    protected $table = 'qf_threads';

    public $timestamps = false;

    protected $fillable = [
        'forum_id',
        'user_id',
        'topic_category',
        'title',
        'content',
        'views',
        'replies',
        'is_top',
        'is_good',
        'is_deleted',
        'ip',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_top' => 'integer',
            'is_good' => 'boolean',
            'is_deleted' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class, 'forum_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

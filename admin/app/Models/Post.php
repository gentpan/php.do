<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    protected $table = 'pd_posts';

    public $timestamps = false;

    protected $fillable = [
        'thread_id',
        'user_id',
        'content',
        'is_deleted',
        'upvotes',
        'downvotes',
        'ip',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_deleted' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class, 'thread_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Forum extends Model
{
    protected $table = 'qf_forums';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'banner',
        'topic_category_enabled',
        'topic_categories',
        'post_user_limit_enabled',
        'post_user_ids',
        'display_order',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'topic_category_enabled' => 'boolean',
            'post_user_limit_enabled' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Forum $forum): void {
            if (empty($forum->created_at)) {
                $forum->created_at = now();
            }
        });
    }

    public function threads(): HasMany
    {
        return $this->hasMany(Thread::class, 'forum_id');
    }
}

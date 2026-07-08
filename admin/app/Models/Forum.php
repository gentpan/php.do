<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Forum extends Model
{
    protected $table = 'pd_forums';

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
        'show_in_nav',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'topic_category_enabled' => 'boolean',
            'post_user_limit_enabled' => 'boolean',
            'show_in_nav' => 'boolean',
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

    /** @return array<string, string> */
    public static function slugMap(): array
    {
        return [
            '站务公告' => 'announcements',
            '技术问答' => 'qa',
            '框架生态' => 'frameworks',
            '程序发布' => 'release',
            '数据库与缓存' => 'database',
            '部署运维' => 'ops',
            '安全审计' => 'security',
            '综合闲聊' => 'chat',
        ];
    }

    public function navUrl(): string
    {
        $slug = self::slugMap()[(string) $this->name] ?? '';

        return $slug !== '' ? '/'.$slug : '/forum/'.$this->id;
    }
}

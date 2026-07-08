<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchemaPrefixMigrator
{
    /** @var list<string> */
    private const TABLES = [
        'users',
        'passkeys',
        'forums',
        'threads',
        'thread_votes',
        'thread_reactions',
        'posts',
        'post_votes',
        'bans',
        'security_logs',
        'moderator_logs',
        'moderator_forums',
        'attachments',
        'post_comments',
        'notifications',
        'settings',
        'signins',
        'ads',
        'navs',
        'invites',
        'oauth',
        'user_groups',
        'points_log',
        'pm_threads',
        'pm_messages',
        'online',
        'online_daily',
    ];

    private static bool $verified = false;

    public static function ensure(): void
    {
        // 同一 worker 进程内确认过一次即短路，避免每个请求重复查库。
        if (self::$verified) {
            return;
        }

        if (! self::databaseReady()) {
            return;
        }

        if (self::isMigrated()) {
            self::$verified = true;

            return;
        }

        $renamed = false;

        foreach (self::TABLES as $name) {
            $from = 'qf_' . $name;
            $to = 'pd_' . $name;

            if (! Schema::hasTable($from) || Schema::hasTable($to)) {
                continue;
            }

            try {
                DB::statement("RENAME TABLE `{$from}` TO `{$to}`");
                $renamed = true;
            } catch (\Throwable) {
                // 并发首启时另一个 worker 可能已抢先重命名，跳过即可。
            }
        }

        if ($renamed || Schema::hasTable('pd_settings')) {
            self::markMigrated();
            self::$verified = true;
        }
    }

    private static function databaseReady(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private static function isMigrated(): bool
    {
        if (! Schema::hasTable('pd_settings')) {
            return false;
        }

        try {
            $value = DB::table('pd_settings')
                ->where('setting_key', 'schema_prefix_pd')
                ->value('setting_value');

            return $value === '1';
        } catch (\Throwable) {
            return false;
        }
    }

    private static function markMigrated(): void
    {
        if (! Schema::hasTable('pd_settings')) {
            return;
        }

        DB::table('pd_settings')->updateOrInsert(
            ['setting_key' => 'schema_prefix_pd'],
            ['setting_value' => '1'],
        );
    }
}

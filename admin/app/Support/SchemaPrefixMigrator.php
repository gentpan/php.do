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

    public static function ensure(): void
    {
        if (! self::databaseReady()) {
            return;
        }

        if (self::isMigrated()) {
            return;
        }

        $renamed = false;

        foreach (self::TABLES as $name) {
            $from = 'qf_' . $name;
            $to = 'pd_' . $name;

            if (! Schema::hasTable($from) || Schema::hasTable($to)) {
                continue;
            }

            DB::statement("RENAME TABLE `{$from}` TO `{$to}`");
            $renamed = true;
        }

        if ($renamed || Schema::hasTable('pd_settings')) {
            self::markMigrated();
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

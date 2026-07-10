<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchemaVersionGuard
{
    private const CURRENT_VERSION = '20260710';

    public static function ensure(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        try {
            $ready = Schema::hasTable('pd_settings')
                && DB::table('pd_settings')->where('setting_key', 'schema_version')->value('setting_value') === self::CURRENT_VERSION;
        } catch (\Throwable) {
            $ready = false;
        }

        abort_unless($ready, 503, '数据库结构需要升级，请先运行受维护令牌保护的 install/upgrade.php。');
    }
}

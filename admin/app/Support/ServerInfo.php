<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class ServerInfo
{
    /** @return array<string, mixed> */
    public static function snapshot(): array
    {
        return [
            'hostname' => gethostname() ?: '—',
            'os' => trim(php_uname('s') . ' ' . php_uname('r')),
            'arch' => php_uname('m'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'sapi' => php_sapi_name(),
            'server_software' => self::serverSoftware(),
            'timezone' => date_default_timezone_get(),
            'memory' => self::memory(),
            'disk' => self::disk(),
            'load' => self::loadAverage(),
            'uptime' => self::uptime(),
            'database' => self::database(),
            'opcache' => self::opcache(),
            'forum_root' => realpath(base_path('..')) ?: base_path('..'),
        ];
    }

    public static function serverSoftware(): string
    {
        if (defined('FRANKENPHP_VERSION')) {
            return 'FrankenPHP ' . FRANKENPHP_VERSION;
        }

        $software = $_SERVER['SERVER_SOFTWARE'] ?? '';

        return $software !== '' ? $software : php_sapi_name();
    }

    /** @return array{used: int, total: int, free: int, percent: float}|null */
    public static function memory(): ?array
    {
        if (! is_readable('/proc/meminfo')) {
            $used = memory_get_usage(true);
            $peak = memory_get_peak_usage(true);

            return [
                'used' => $used,
                'total' => $peak > 0 ? $peak * 2 : $used,
                'free' => max(0, ($peak > 0 ? $peak * 2 : $used) - $used),
                'percent' => $peak > 0 ? round($used / ($peak * 2) * 100, 1) : 0.0,
            ];
        }

        $info = file_get_contents('/proc/meminfo');
        if ($info === false) {
            return null;
        }

        $values = [];
        foreach (explode("\n", $info) as $line) {
            if (preg_match('/^(\w+):\s+(\d+)/', $line, $m)) {
                $values[$m[1]] = (int) $m[2] * 1024;
            }
        }

        $total = $values['MemTotal'] ?? 0;
        $available = $values['MemAvailable'] ?? ($values['MemFree'] ?? 0);
        $used = max(0, $total - $available);

        if ($total <= 0) {
            return null;
        }

        return [
            'used' => $used,
            'total' => $total,
            'free' => $available,
            'percent' => round($used / $total * 100, 1),
        ];
    }

    /** @return array{used: int, total: int, free: int, percent: float}|null */
    public static function disk(?string $path = null): ?array
    {
        $path = $path ?? (realpath(base_path('..')) ?: base_path('..'));

        $total = @disk_total_space($path);
        $free = @disk_free_space($path);

        if ($total === false || $free === false || $total <= 0) {
            return null;
        }

        $used = $total - $free;

        return [
            'used' => (int) $used,
            'total' => (int) $total,
            'free' => (int) $free,
            'percent' => round($used / $total * 100, 1),
        ];
    }

    /** @return array{1: float, 5: float, 15: float}|null */
    public static function loadAverage(): ?array
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if (is_array($load) && count($load) >= 3) {
                return [
                    '1' => round($load[0], 2),
                    '5' => round($load[1], 2),
                    '15' => round($load[2], 2),
                ];
            }
        }

        return null;
    }

    public static function cpuCount(): int
    {
        if (is_readable('/proc/cpuinfo')) {
            $info = file_get_contents('/proc/cpuinfo');
            if ($info !== false) {
                return max(1, substr_count($info, 'processor'));
            }
        }

        return 1;
    }

    /** @param array{1: float, 5: float, 15: float}|null $load */
    public static function loadPercent(?array $load): ?float
    {
        if ($load === null) {
            return null;
        }

        return min(100, round($load['1'] / self::cpuCount() * 100, 1));
    }

    public static function gaugeLevel(float $percent): string
    {
        if ($percent >= 85) {
            return 'danger';
        }
        if ($percent >= 60) {
            return 'warning';
        }

        return 'success';
    }

    public static function formatPercent(float $percent): string
    {
        $formatted = number_format($percent, 1);

        return rtrim(rtrim($formatted, '0'), '.');
    }

    public static function uptime(): ?string
    {
        if (! is_readable('/proc/uptime')) {
            return null;
        }

        $raw = file_get_contents('/proc/uptime');
        if ($raw === false) {
            return null;
        }

        $seconds = (int) floor((float) explode(' ', trim($raw))[0]);

        return self::formatDuration($seconds);
    }

    /** @return array<string, mixed> */
    public static function database(): array
    {
        $result = ['version' => '—', 'size_mb' => null, 'connected' => false];

        try {
            $row = DB::selectOne('SELECT VERSION() AS version');
            $result['version'] = $row->version ?? '—';
            $result['connected'] = true;

            $dbName = DB::connection()->getDatabaseName();
            $sizeRow = DB::selectOne(
                'SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                 FROM information_schema.tables WHERE table_schema = ?',
                [$dbName],
            );
            $result['size_mb'] = $sizeRow->size_mb ?? null;
        } catch (\Throwable) {
            //
        }

        return $result;
    }

    /** @return array<string, mixed> */
    public static function opcache(): array
    {
        if (! function_exists('opcache_get_status')) {
            return ['enabled' => false];
        }

        $status = @opcache_get_status(false);
        if (! is_array($status)) {
            return ['enabled' => false];
        }

        $mem = $status['memory_usage'] ?? [];
        $stats = $status['opcache_statistics'] ?? [];

        return [
            'enabled' => (bool) ($status['opcache_enabled'] ?? false),
            'hit_rate' => isset($stats['opcache_hit_rate']) ? round((float) $stats['opcache_hit_rate'], 1) : null,
            'used_memory_mb' => isset($mem['used_memory']) ? round($mem['used_memory'] / 1048576, 1) : null,
            'cached_scripts' => $stats['num_cached_scripts'] ?? null,
        ];
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        return round($bytes / 1073741824, 2) . ' GB';
    }

    public static function formatDuration(int $seconds): string
    {
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . ' 天';
        }
        if ($hours > 0) {
            $parts[] = $hours . ' 小时';
        }
        if ($minutes > 0 || $parts === []) {
            $parts[] = $minutes . ' 分钟';
        }

        return implode(' ', $parts);
    }
}

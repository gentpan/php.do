<?php

namespace App\Filament\Pages;

use App\Support\ServerInfo as ServerInfoHelper;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ServerInfo extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static ?string $navigationLabel = '服务器信息';

    protected static string|UnitEnum|null $navigationGroup = '系统';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = '服务器信息';

    protected static ?string $slug = 'server-info';

    /** @var array<string, mixed> */
    public array $info = [];

    public function mount(): void
    {
        $this->info = ServerInfoHelper::snapshot();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'default' => 1,
                    'lg' => 2,
                ])->schema([
                    Section::make('运行环境')
                        ->schema($this->environmentEntries())
                        ->columns(2),
                    Section::make('数据库与缓存')
                        ->schema($this->databaseEntries())
                        ->columns(2),
                ]),
                Section::make('资源使用')
                    ->schema($this->resourceEntries())
                    ->columns(2),
            ]);
    }

    /** @return array<TextEntry> */
    protected function environmentEntries(): array
    {
        return [
            TextEntry::make('hostname')
                ->label('主机名')
                ->state(fn (): string => (string) ($this->info['hostname'] ?? '—')),
            TextEntry::make('os')
                ->label('操作系统')
                ->state(fn (): string => (string) ($this->info['os'] ?? '—')),
            TextEntry::make('arch')
                ->label('架构')
                ->state(fn (): string => (string) ($this->info['arch'] ?? '—')),
            TextEntry::make('php_version')
                ->label('PHP')
                ->state(fn (): string => (string) ($this->info['php_version'] ?? '—')),
            TextEntry::make('laravel_version')
                ->label('Laravel')
                ->state(fn (): string => (string) ($this->info['laravel_version'] ?? '—')),
            TextEntry::make('server_software')
                ->label('Web 服务')
                ->state(fn (): string => (string) ($this->info['server_software'] ?? '—')),
            TextEntry::make('sapi')
                ->label('SAPI')
                ->state(fn (): string => (string) ($this->info['sapi'] ?? '—')),
            TextEntry::make('timezone')
                ->label('时区')
                ->state(fn (): string => (string) ($this->info['timezone'] ?? '—')),
            TextEntry::make('forum_root')
                ->label('站点目录')
                ->state(fn (): string => (string) ($this->info['forum_root'] ?? '—'))
                ->columnSpanFull()
                ->copyable(),
        ];
    }

    /** @return array<TextEntry> */
    protected function databaseEntries(): array
    {
        return [
            TextEntry::make('db_connected')
                ->label('连接状态')
                ->state(fn (): string => ($this->info['database']['connected'] ?? false) ? '正常' : '异常')
                ->badge()
                ->color(fn (): string => ($this->info['database']['connected'] ?? false) ? 'success' : 'danger'),
            TextEntry::make('db_version')
                ->label('MySQL 版本')
                ->state(fn (): string => (string) ($this->info['database']['version'] ?? '—')),
            TextEntry::make('db_size')
                ->label('库大小')
                ->state(function (): string {
                    $size = $this->info['database']['size_mb'] ?? null;

                    return $size !== null ? $size . ' MB' : '—';
                }),
            TextEntry::make('opcache_status')
                ->label('OPcache')
                ->state(fn (): string => ($this->info['opcache']['enabled'] ?? false) ? '已启用' : '未启用')
                ->badge()
                ->color(fn (): string => ($this->info['opcache']['enabled'] ?? false) ? 'success' : 'gray'),
            TextEntry::make('opcache_hit_rate')
                ->label('命中率')
                ->state(function (): string {
                    $rate = $this->info['opcache']['hit_rate'] ?? null;

                    return $rate !== null ? $rate . '%' : '—';
                })
                ->visible(fn (): bool => (bool) ($this->info['opcache']['enabled'] ?? false)),
            TextEntry::make('opcache_memory')
                ->label('已用内存')
                ->state(function (): string {
                    $mb = $this->info['opcache']['used_memory_mb'] ?? null;

                    return $mb !== null ? $mb . ' MB' : '—';
                })
                ->visible(fn (): bool => (bool) ($this->info['opcache']['enabled'] ?? false)),
            TextEntry::make('opcache_scripts')
                ->label('缓存脚本')
                ->state(fn (): string => (string) ($this->info['opcache']['cached_scripts'] ?? '—'))
                ->visible(fn (): bool => (bool) ($this->info['opcache']['enabled'] ?? false)),
        ];
    }

    /** @return array<TextEntry> */
    protected function resourceEntries(): array
    {
        return [
            TextEntry::make('memory_usage')
                ->label('内存')
                ->state(function (): string {
                    $memory = $this->info['memory'] ?? null;
                    if (! is_array($memory)) {
                        return '—';
                    }

                    return sprintf(
                        '%s%%（已用 %s / 共 %s）',
                        $memory['percent'],
                        ServerInfoHelper::formatBytes($memory['used']),
                        ServerInfoHelper::formatBytes($memory['total']),
                    );
                })
                ->visible(fn (): bool => is_array($this->info['memory'] ?? null)),
            TextEntry::make('disk_usage')
                ->label('磁盘（站点目录）')
                ->state(function (): string {
                    $disk = $this->info['disk'] ?? null;
                    if (! is_array($disk)) {
                        return '—';
                    }

                    return sprintf(
                        '%s%%（已用 %s / 共 %s）',
                        $disk['percent'],
                        ServerInfoHelper::formatBytes($disk['used']),
                        ServerInfoHelper::formatBytes($disk['total']),
                    );
                })
                ->visible(fn (): bool => is_array($this->info['disk'] ?? null)),
            TextEntry::make('load_average')
                ->label('系统负载（1 / 5 / 15 分钟）')
                ->state(function (): string {
                    $load = $this->info['load'] ?? null;
                    if (! is_array($load)) {
                        return '—';
                    }

                    return $load['1'] . ' · ' . $load['5'] . ' · ' . $load['15'];
                })
                ->visible(fn (): bool => is_array($this->info['load'] ?? null)),
            TextEntry::make('uptime')
                ->label('系统运行时间')
                ->state(fn (): string => (string) ($this->info['uptime'] ?? '—'))
                ->visible(fn (): bool => filled($this->info['uptime'] ?? null)),
        ];
    }
}

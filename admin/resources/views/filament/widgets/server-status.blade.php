@php
    use App\Support\ServerInfo;

    $memory = $memory ?? null;
    $disk = $disk ?? null;
    $load = $load ?? null;
    $database = $database ?? [];
@endphp

<x-filament-widgets::widget>
    <x-filament::section heading="服务器状态" description="实时资源概览">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                <p class="text-xs text-gray-500 dark:text-gray-400">运行环境</p>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                    PHP {{ $info['php_version'] ?? '—' }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $info['server_software'] ?? '—' }}
                </p>
            </div>

            <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                <p class="text-xs text-gray-500 dark:text-gray-400">数据库</p>
                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                    {{ $database['connected'] ?? false ? '已连接' : '未连接' }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    @if (($database['size_mb'] ?? null) !== null)
                        约 {{ $database['size_mb'] }} MB
                    @else
                        MySQL {{ $database['version'] ?? '—' }}
                    @endif
                </p>
            </div>

            @if ($memory)
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>内存</span>
                        <span>{{ $memory['percent'] }}%</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                        <div
                            class="h-full rounded-full bg-amber-500"
                            style="width: {{ min(100, $memory['percent']) }}%"
                        ></div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ ServerInfo::formatBytes($memory['used']) }} / {{ ServerInfo::formatBytes($memory['total']) }}
                    </p>
                </div>
            @endif

            @if ($disk)
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>磁盘</span>
                        <span>{{ $disk['percent'] }}%</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                        <div
                            class="h-full rounded-full bg-sky-500"
                            style="width: {{ min(100, $disk['percent']) }}%"
                        ></div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ ServerInfo::formatBytes($disk['used']) }} / {{ ServerInfo::formatBytes($disk['total']) }}
                    </p>
                </div>
            @endif

            @if ($load)
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5 sm:col-span-2">
                    <p class="text-xs text-gray-500 dark:text-gray-400">系统负载</p>
                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                        {{ $load['1'] }} / {{ $load['5'] }} / {{ $load['15'] }}
                        <span class="text-xs font-normal text-gray-500 dark:text-gray-400">（1 / 5 / 15 分钟）</span>
                    </p>
                    @if (($info['uptime'] ?? null) !== null)
                        <p class="text-xs text-gray-500 dark:text-gray-400">运行 {{ $info['uptime'] }}</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="mt-4">
            <x-filament::link :href="url('/admin/server-info')" icon="heroicon-m-arrow-top-right-on-square">
                查看完整服务器信息
            </x-filament::link>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

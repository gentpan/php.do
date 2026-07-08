@php
    use App\Support\ServerInfo;

    $info = $info ?? [];
    $memory = $info['memory'] ?? null;
    $disk = $info['disk'] ?? null;
    $load = $info['load'] ?? null;
    $database = $info['database'] ?? [];
    $opcache = $info['opcache'] ?? [];
@endphp

<div>
    <x-filament-panels::page>
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">运行环境</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">主机名</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $info['hostname'] ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">操作系统</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $info['os'] ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">架构</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $info['arch'] ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">PHP</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $info['php_version'] ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Laravel</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $info['laravel_version'] ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Web 服务</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $info['server_software'] ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">SAPI</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $info['sapi'] ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">时区</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $info['timezone'] ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">站点目录</dt>
                        <dd class="text-right font-mono text-xs text-gray-900 dark:text-white">{{ $info['forum_root'] ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">数据库</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">连接状态</dt>
                        <dd class="text-right font-medium {{ ($database['connected'] ?? false) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ ($database['connected'] ?? false) ? '正常' : '异常' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">MySQL 版本</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $database['version'] ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">库大小</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">
                            {{ ($database['size_mb'] ?? null) !== null ? $database['size_mb'] . ' MB' : '—' }}
                        </dd>
                    </div>
                </dl>

                <h3 class="mt-8 text-base font-semibold text-gray-950 dark:text-white">OPcache</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">状态</dt>
                        <dd class="text-right font-medium text-gray-900 dark:text-white">
                            {{ ($opcache['enabled'] ?? false) ? '已启用' : '未启用' }}
                        </dd>
                    </div>
                    @if ($opcache['enabled'] ?? false)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">命中率</dt>
                            <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $opcache['hit_rate'] ?? '—' }}%</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">已用内存</dt>
                            <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $opcache['used_memory_mb'] ?? '—' }} MB</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">缓存脚本</dt>
                            <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $opcache['cached_scripts'] ?? '—' }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 lg:col-span-2">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">资源使用</h3>
                <div class="mt-4 grid gap-6 md:grid-cols-2">
                    @if ($memory)
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-300">内存</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $memory['percent'] }}%</span>
                            </div>
                            <div class="mt-2 h-3 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                <div class="h-full rounded-full bg-amber-500" style="width: {{ min(100, $memory['percent']) }}%"></div>
                            </div>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                已用 {{ ServerInfo::formatBytes($memory['used']) }}，可用 {{ ServerInfo::formatBytes($memory['free']) }}，共 {{ ServerInfo::formatBytes($memory['total']) }}
                            </p>
                        </div>
                    @endif

                    @if ($disk)
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-300">磁盘（站点目录）</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $disk['percent'] }}%</span>
                            </div>
                            <div class="mt-2 h-3 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                <div class="h-full rounded-full bg-sky-500" style="width: {{ min(100, $disk['percent']) }}%"></div>
                            </div>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                已用 {{ ServerInfo::formatBytes($disk['used']) }}，可用 {{ ServerInfo::formatBytes($disk['free']) }}，共 {{ ServerInfo::formatBytes($disk['total']) }}
                            </p>
                        </div>
                    @endif
                </div>

                @if ($load || ($info['uptime'] ?? null))
                    <div class="mt-6 grid gap-4 border-t border-gray-200 pt-6 dark:border-white/10 sm:grid-cols-2">
                        @if ($load)
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-300">系统负载（1 / 5 / 15 分钟）</p>
                                <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $load['1'] }} · {{ $load['5'] }} · {{ $load['15'] }}
                                </p>
                            </div>
                        @endif
                        @if ($info['uptime'] ?? null)
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-300">系统运行时间</p>
                                <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $info['uptime'] }}</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </x-filament-panels::page>
</div>

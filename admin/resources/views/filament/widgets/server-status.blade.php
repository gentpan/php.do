@php
    use App\Support\ServerInfo;

    $memory = $memory ?? null;
    $disk = $disk ?? null;
    $load = $load ?? null;
    $database = $database ?? [];
@endphp

<x-filament-widgets::widget>
    <x-filament::section heading="服务器状态" description="实时资源概览">
        <dl class="pd-server-status">
            <div class="pd-server-status-item">
                <dt>运行环境</dt>
                <dd>
                    <strong>PHP {{ $info['php_version'] ?? '—' }}</strong>
                    <span>{{ $info['server_software'] ?? '—' }}</span>
                </dd>
            </div>

            <div class="pd-server-status-item">
                <dt>数据库</dt>
                <dd>
                    <strong>{{ ($database['connected'] ?? false) ? '已连接' : '未连接' }}</strong>
                    <span>
                        @if (($database['size_mb'] ?? null) !== null)
                            约 {{ $database['size_mb'] }} MB
                        @else
                            MySQL {{ $database['version'] ?? '—' }}
                        @endif
                    </span>
                </dd>
            </div>

            @if ($memory)
                <div class="pd-server-status-item">
                    <dt>内存 {{ $memory['percent'] }}%</dt>
                    <dd>
                        <div class="pd-server-meter">
                            <div class="pd-server-meter-bar pd-server-meter-bar--memory" style="width: {{ min(100, $memory['percent']) }}%"></div>
                        </div>
                        <span>{{ ServerInfo::formatBytes($memory['used']) }} / {{ ServerInfo::formatBytes($memory['total']) }}</span>
                    </dd>
                </div>
            @endif

            @if ($disk)
                <div class="pd-server-status-item">
                    <dt>磁盘 {{ $disk['percent'] }}%</dt>
                    <dd>
                        <div class="pd-server-meter">
                            <div class="pd-server-meter-bar pd-server-meter-bar--disk" style="width: {{ min(100, $disk['percent']) }}%"></div>
                        </div>
                        <span>{{ ServerInfo::formatBytes($disk['used']) }} / {{ ServerInfo::formatBytes($disk['total']) }}</span>
                    </dd>
                </div>
            @endif

            @if ($load)
                <div class="pd-server-status-item pd-server-status-item--wide">
                    <dt>系统负载</dt>
                    <dd>
                        <strong>{{ $load['1'] }} / {{ $load['5'] }} / {{ $load['15'] }}</strong>
                        <span>1 / 5 / 15 分钟</span>
                        @if (($info['uptime'] ?? null) !== null)
                            <span>运行 {{ $info['uptime'] }}</span>
                        @endif
                    </dd>
                </div>
            @endif
        </dl>

        <div class="pd-server-status-link">
            <x-filament::link :href="url('/admin/server-info')" icon="heroicon-m-arrow-top-right-on-square">
                查看完整服务器信息
            </x-filament::link>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

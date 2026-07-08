@php
    use App\Support\ServerInfo;

    $pollingInterval = $pollingInterval ?? '5s';
@endphp

<x-filament-widgets::widget
    :attributes="
        (new \Illuminate\View\ComponentAttributeBag)
            ->merge([
                'wire:poll.' . $pollingInterval => $pollingInterval ? true : null,
            ], escape: false)
            ->class(['pd-resource-gauges-widget'])
    "
>
    <div class="pd-resource-gauges">
        @if ($memory)
            @include('filament.widgets.partials.resource-gauge', [
                'title' => '内存',
                'percent' => (float) $memory['percent'],
                'detail' => ServerInfo::formatBytes($memory['used']) . ' / ' . ServerInfo::formatBytes($memory['total']),
                'tone' => 'memory',
            ])
        @endif

        @if ($disk)
            @include('filament.widgets.partials.resource-gauge', [
                'title' => '磁盘',
                'percent' => (float) $disk['percent'],
                'detail' => ServerInfo::formatBytes($disk['used']) . ' / ' . ServerInfo::formatBytes($disk['total']),
                'tone' => 'disk',
            ])
        @endif

        @if ($loadPercent !== null)
            @include('filament.widgets.partials.resource-gauge', [
                'title' => 'CPU 负载',
                'percent' => (float) $loadPercent,
                'detail' => ($load['1'] ?? '—') . ' / ' . ($load['5'] ?? '—') . ' / ' . ($load['15'] ?? '—') . '（' . $cpuCount . ' 核）',
                'tone' => 'load',
            ])
        @endif
    </div>

    <div class="pd-resource-gauges-meta">
        <span>更新于 {{ $refreshedAt }}</span>
        <span>每 {{ $pollingInterval }} 自动刷新</span>
        @if ($uptime)
            <span>运行 {{ $uptime }}</span>
        @endif
    </div>
</x-filament-widgets::widget>

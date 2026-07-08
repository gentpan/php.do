<?php

namespace App\Filament\Widgets;

use App\Support\ServerInfo;
use Filament\Widgets\Concerns\CanPoll;
use Filament\Widgets\Widget;

class ServerResourceGauges extends Widget
{
    use CanPoll;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.server-resource-gauges';

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '5s';

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        // 仅取仪表盘需要的资源项，避免 5 秒轮询触发 snapshot() 里的
        // information_schema 全库统计与 VERSION() 等无用查询。
        $memory = ServerInfo::memory();
        $disk = ServerInfo::disk();
        $load = ServerInfo::loadAverage();

        return [
            'memory' => $memory,
            'disk' => $disk,
            'load' => $load,
            'loadPercent' => ServerInfo::loadPercent($load),
            'cpuCount' => ServerInfo::cpuCount(),
            'uptime' => ServerInfo::uptime(),
            'refreshedAt' => now()->format('H:i:s'),
            'pollingInterval' => $this->getPollingInterval(),
        ];
    }
}

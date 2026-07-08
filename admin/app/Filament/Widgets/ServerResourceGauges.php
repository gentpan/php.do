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
        $info = ServerInfo::snapshot();
        $memory = $info['memory'] ?? null;
        $disk = $info['disk'] ?? null;
        $load = $info['load'] ?? null;
        $loadPercent = ServerInfo::loadPercent(is_array($load) ? $load : null);

        return [
            'memory' => is_array($memory) ? $memory : null,
            'disk' => is_array($disk) ? $disk : null,
            'load' => is_array($load) ? $load : null,
            'loadPercent' => $loadPercent,
            'cpuCount' => ServerInfo::cpuCount(),
            'uptime' => $info['uptime'] ?? null,
            'refreshedAt' => now()->format('H:i:s'),
            'pollingInterval' => $this->getPollingInterval(),
        ];
    }
}

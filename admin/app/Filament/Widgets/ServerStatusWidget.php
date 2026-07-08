<?php

namespace App\Filament\Widgets;

use App\Support\ServerInfo;
use Filament\Widgets\Widget;

class ServerStatusWidget extends Widget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 1;

    protected string $view = 'filament.widgets.server-status';

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $info = ServerInfo::snapshot();

        return [
            'info' => $info,
            'memory' => $info['memory'],
            'disk' => $info['disk'],
            'load' => $info['load'],
            'database' => $info['database'],
        ];
    }
}

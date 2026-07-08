<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Dashboard extends BaseDashboard
{
    protected static bool $isDiscovered = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = '仪表盘';

    protected static string|UnitEnum|null $navigationGroup = '概览';

    /**
     * @return int | array<string, ?int>
     */
    public function getColumns(): int | array
    {
        return [
            'default' => 1,
            'md' => 2,
        ];
    }
}

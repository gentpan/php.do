<?php

namespace App\Filament\Pages;

use App\Support\ServerInfo as ServerInfoHelper;
use BackedEnum;
use Filament\Pages\Page;
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

    protected string $view = 'filament.pages.server-info';

    /** @var array<string, mixed> */
    public array $info = [];

    public function mount(): void
    {
        $this->info = ServerInfoHelper::snapshot();
    }
}

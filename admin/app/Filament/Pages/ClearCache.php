<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ClearCache extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrash;

    protected static ?string $navigationLabel = '清理缓存';

    protected static string|UnitEnum|null $navigationGroup = '系统';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = '清理缓存';

    protected static ?string $slug = 'clear-cache';

    protected string $view = 'filament.pages.clear-cache';

    /** @var list<string> */
    public array $messages = [];

    public function mount(): void
    {
        //
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clear')
                ->label('立即清理')
                ->action(function (): void {
                    $messages = [];
                    if (function_exists('opcache_reset')) {
                        $messages[] = @opcache_reset() ? 'OPcache 已清理。' : 'OPcache 无法重置。';
                    } else {
                        $messages[] = '当前环境未开启 OPcache。';
                    }
                    try {
                        \Artisan::call('optimize:clear');
                        $messages[] = 'Laravel 缓存已清理。';
                    } catch (\Throwable $e) {
                        $messages[] = 'Laravel 缓存清理失败：' . $e->getMessage();
                    }
                    $this->messages = $messages;
                    Notification::make()->title('缓存清理完成')->success()->send();
                }),
        ];
    }
}

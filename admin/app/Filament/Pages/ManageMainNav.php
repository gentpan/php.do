<?php

namespace App\Filament\Pages;

use App\Models\Forum;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ManageMainNav extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static ?string $navigationLabel = '主导航';

    protected static string|UnitEnum|null $navigationGroup = '运营展示';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = '主导航';

    protected static ?string $slug = 'main-nav';

    protected string $view = 'filament.pages.manage-main-nav';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Forum::query()->orderBy('display_order')->orderBy('id'))
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label('版块'),
                TextInputColumn::make('display_order')
                    ->label('排序')
                    ->type('number')
                    ->rules(['integer', 'min:0', 'max:9999']),
                ToggleColumn::make('show_in_nav')->label('显示在导航栏'),
                TextColumn::make('navUrl')
                    ->label('前台链接')
                    ->state(fn (Forum $record): string => $record->navUrl())
                    ->url(fn (Forum $record): string => $record->navUrl())
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('display_order');
    }

    /** @return list<string> */
    public function navPreview(): array
    {
        return Forum::query()
            ->where('show_in_nav', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->pluck('name')
            ->all();
    }
}

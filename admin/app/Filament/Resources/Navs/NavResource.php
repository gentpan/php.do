<?php

namespace App\Filament\Resources\Navs;

use App\Filament\Resources\Navs\Pages\ManageNavs;
use App\Models\Nav;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class NavResource extends Resource
{
    protected static ?string $model = Nav::class;

    protected static ?string $navigationLabel = '主导航';

    protected static ?string $modelLabel = '导航';

    protected static ?string $pluralModelLabel = '导航';

    protected static string|UnitEnum|null $navigationGroup = '运营展示';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('标题')->required()->maxLength(40),
            TextInput::make('url')->label('链接')->required()->maxLength(255),
            Select::make('icon_type')->label('图标类型')->options([
                'fa' => 'Font Awesome 类名',
                'svg' => 'SVG/自定义',
                'none' => '无',
            ])->default('fa'),
            Textarea::make('icon_value')->label('图标值')->rows(2),
            TextInput::make('display_order')->label('排序')->numeric()->default(10),
            Toggle::make('is_enabled')->label('启用')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('display_order')
            ->columns([
                TextColumn::make('title')->label('标题')->searchable(),
                TextColumn::make('url')->label('链接')->limit(40),
                TextColumn::make('icon_type')->label('图标类型'),
                TextColumn::make('display_order')->label('排序')->sortable(),
                IconColumn::make('is_enabled')->label('启用')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageNavs::route('/')];
    }
}

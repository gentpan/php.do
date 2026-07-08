<?php

namespace App\Filament\Resources\Ads;

use App\Filament\Resources\Ads\Pages\ManageAds;
use App\Models\Ad;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AdResource extends Resource
{
    protected static ?string $model = Ad::class;

    protected static ?string $navigationLabel = '广告位置';

    protected static ?string $modelLabel = '广告';

    protected static ?string $pluralModelLabel = '广告';

    protected static string|UnitEnum|null $navigationGroup = '运营展示';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('position')->label('位置标识')->required()->maxLength(30),
            TextInput::make('title')->label('标题')->maxLength(80),
            TextInput::make('image_path')->label('图片路径')->maxLength(255),
            TextInput::make('link_url')->label('链接')->maxLength(255),
            TextInput::make('width')->label('宽')->maxLength(20),
            TextInput::make('height')->label('高')->maxLength(20),
            Toggle::make('is_enabled')->label('启用')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('position')->label('位置')->searchable(),
                TextColumn::make('title')->label('标题'),
                TextColumn::make('link_url')->label('链接')->limit(30),
                IconColumn::make('is_enabled')->label('启用')->boolean(),
                TextColumn::make('updated_at')->label('更新')->dateTime(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAds::route('/')];
    }
}

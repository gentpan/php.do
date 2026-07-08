<?php

namespace App\Filament\Resources\OnlineDailies;

use App\Filament\Resources\OnlineDailies\Pages\ManageOnlineDailies;
use App\Models\OnlineDaily;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class OnlineDailyResource extends Resource
{
    protected static ?string $model = OnlineDaily::class;

    protected static ?string $navigationLabel = '在线峰值';

    protected static ?string $modelLabel = '峰值';

    protected static ?string $pluralModelLabel = '每日峰值';

    protected static string|UnitEnum|null $navigationGroup = '概览';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('day_date', 'desc')
            ->columns([
                TextColumn::make('day_date')->label('日期')->sortable(),
                TextColumn::make('peak_total')->label('峰值总数')->sortable(),
                TextColumn::make('peak_members')->label('会员峰值'),
                TextColumn::make('peak_guests')->label('访客峰值'),
                TextColumn::make('peak_at')->label('峰值时间')->dateTime(),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageOnlineDailies::route('/')];
    }
}

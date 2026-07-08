<?php

namespace App\Filament\Resources\PointsLogs;

use App\Filament\Resources\PointsLogs\Pages\ManagePointsLogs;
use App\Models\PointsLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PointsLogResource extends Resource
{
    protected static ?string $model = PointsLog::class;

    protected static ?string $navigationLabel = '积分流水';

    protected static ?string $modelLabel = '流水';

    protected static ?string $pluralModelLabel = '积分流水';

    protected static string|UnitEnum|null $navigationGroup = '用户';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

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
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('user.username')->label('用户')->searchable(),
                TextColumn::make('delta')->label('变动'),
                TextColumn::make('balance')->label('余额'),
                TextColumn::make('reason')->label('原因')->badge(),
                TextColumn::make('note')->label('备注')->limit(30),
                TextColumn::make('created_at')->label('时间')->dateTime()->sortable(),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePointsLogs::route('/')];
    }
}

<?php

namespace App\Filament\Resources\SecurityLogs;

use App\Filament\Resources\SecurityLogs\Pages\ManageSecurityLogs;
use App\Models\SecurityLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SecurityLogResource extends Resource
{
    protected static ?string $model = SecurityLog::class;

    protected static ?string $navigationLabel = '安全日志';

    protected static ?string $modelLabel = '日志';

    protected static ?string $pluralModelLabel = '安全日志';

    protected static string|UnitEnum|null $navigationGroup = '系统';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

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
                TextColumn::make('ip')->label('IP')->searchable(),
                TextColumn::make('uri')->label('URI')->searchable()->limit(80),
                TextColumn::make('created_at')->label('时间')->dateTime()->sortable(),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSecurityLogs::route('/')];
    }
}

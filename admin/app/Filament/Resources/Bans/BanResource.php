<?php

namespace App\Filament\Resources\Bans;

use App\Filament\Resources\Bans\Pages\ManageBans;
use App\Models\Ban;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class BanResource extends Resource
{
    protected static ?string $model = Ban::class;

    protected static ?string $navigationLabel = 'IP 禁封';

    protected static ?string $modelLabel = '禁封';

    protected static ?string $pluralModelLabel = '禁封';

    protected static string|UnitEnum|null $navigationGroup = '系统';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('ip')->label('IP')->required()->maxLength(45),
            TextInput::make('reason')->label('原因')->maxLength(255),
            DateTimePicker::make('expires_at')->label('到期时间')->helperText('留空表示永久')->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('ip')->label('IP')->searchable(),
                TextColumn::make('reason')->label('原因')->limit(40),
                TextColumn::make('expires_at')->label('到期')->dateTime()->placeholder('永久'),
                TextColumn::make('created_at')->label('创建')->dateTime(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageBans::route('/')];
    }
}

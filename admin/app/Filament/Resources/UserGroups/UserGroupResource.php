<?php

namespace App\Filament\Resources\UserGroups;

use App\Filament\Resources\UserGroups\Pages\ManageUserGroups;
use App\Models\UserGroup;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class UserGroupResource extends Resource
{
    protected static ?string $model = UserGroup::class;

    protected static ?string $navigationLabel = '用户组';

    protected static ?string $modelLabel = '用户组';

    protected static ?string $pluralModelLabel = '用户组';

    protected static string|UnitEnum|null $navigationGroup = '用户';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('名称')->required()->maxLength(60),
            TextInput::make('slug')->label('标识')->required()->maxLength(40),
            ColorPicker::make('color')->label('颜色')->default('#505b93'),
            TextInput::make('min_points')->label('最低积分')->numeric()->default(0),
            TextInput::make('display_order')->label('排序')->numeric()->default(100),
            Toggle::make('is_system')->label('系统组')->disabledOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('min_points')
            ->columns([
                TextColumn::make('name')->label('名称')->searchable(),
                TextColumn::make('slug')->label('标识'),
                ColorColumn::make('color')->label('颜色'),
                TextColumn::make('min_points')->label('最低积分')->sortable(),
                TextColumn::make('display_order')->label('排序'),
                IconColumn::make('is_system')->label('系统')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn (UserGroup $record) => ! $record->is_system),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageUserGroups::route('/')];
    }
}

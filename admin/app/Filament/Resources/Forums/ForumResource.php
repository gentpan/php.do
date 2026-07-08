<?php

namespace App\Filament\Resources\Forums;

use App\Filament\Resources\Forums\Pages\ManageForums;
use App\Models\Forum;
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

class ForumResource extends Resource
{
    protected static ?string $model = Forum::class;

    protected static ?string $navigationLabel = '版块管理';

    protected static ?string $modelLabel = '版块';

    protected static ?string $pluralModelLabel = '版块';

    protected static string|UnitEnum|null $navigationGroup = '帖子管理';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('名称')->required()->maxLength(60),
                TextInput::make('description')->label('简介')->maxLength(255),
                TextInput::make('banner')->label('Banner 路径')->maxLength(255),
                Toggle::make('topic_category_enabled')->label('开启主题分类'),
                TextInput::make('topic_categories')->label('主题分类')->helperText('逗号分隔，例如：意见,BUG')->maxLength(255),
                Toggle::make('post_user_limit_enabled')->label('限制指定用户发帖'),
                TextInput::make('post_user_ids')->label('允许发帖用户 ID')->helperText('逗号分隔用户 ID')->maxLength(255),
                TextInput::make('display_order')->label('排序')->numeric()->default(10),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('display_order')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('name')->label('名称')->searchable(),
                TextColumn::make('description')->label('简介')->limit(40),
                IconColumn::make('topic_category_enabled')->label('分类')->boolean(),
                IconColumn::make('post_user_limit_enabled')->label('限发帖')->boolean(),
                TextColumn::make('display_order')->label('排序')->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageForums::route('/'),
        ];
    }
}

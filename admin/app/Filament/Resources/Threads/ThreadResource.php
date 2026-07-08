<?php

namespace App\Filament\Resources\Threads;

use App\Filament\Resources\Threads\Pages\ManageThreads;
use App\Models\Forum;
use App\Models\Thread;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class ThreadResource extends Resource
{
    protected static ?string $model = Thread::class;

    protected static ?string $navigationLabel = '帖子管理';

    protected static ?string $modelLabel = '帖子';

    protected static ?string $pluralModelLabel = '帖子';

    protected static string|UnitEnum|null $navigationGroup = '帖子管理';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('标题')->required()->maxLength(200),
            Textarea::make('content')->label('内容')->rows(10)->required(),
            Toggle::make('is_top')->label('置顶'),
            Toggle::make('is_good')->label('加精'),
            Toggle::make('is_deleted')->label('已删除'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('title')->label('标题')->searchable()->limit(40)
                    ->url(fn (Thread $record): string => '/thread/'.$record->id.'.html', shouldOpenInNewTab: true),
                TextColumn::make('forum.name')->label('版块')->sortable(),
                TextColumn::make('user.username')->label('作者')->searchable(),
                TextColumn::make('is_top')->label('置顶')->formatStateUsing(fn ($state): string => match ((int) $state) {
                    1 => '全站',
                    2 => '版块',
                    default => '-',
                }),
                IconColumn::make('is_good')->label('加精')->boolean(),
                IconColumn::make('is_deleted')->label('删除')->boolean(),
                TextColumn::make('replies')->label('回复')->sortable(),
                TextColumn::make('updated_at')->label('更新')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('forum_id')
                    ->label('版块')
                    ->options(fn (): array => Forum::query()->orderBy('display_order')->pluck('name', 'id')->all()),
                TernaryFilter::make('is_deleted')->label('已删除'),
                TernaryFilter::make('is_top')->label('置顶'),
                TernaryFilter::make('is_good')->label('加精'),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('前台查看')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Thread $record): string => '/thread/'.$record->id.'.html')
                    ->openUrlInNewTab(),
                EditAction::make(),
                Action::make('toggleTop')
                    ->label(fn (Thread $r) => (int) $r->is_top > 0 ? '取消置顶' : '版块置顶')
                    ->action(function (Thread $record): void {
                        $record->is_top = (int) $record->is_top > 0 ? 0 : 2;
                        $record->save();
                        Notification::make()->title('已更新置顶')->success()->send();
                    }),
                Action::make('toggleGlobalTop')
                    ->label(fn (Thread $r) => (int) $r->is_top === 1 ? '取消全站置顶' : '全站置顶')
                    ->action(function (Thread $record): void {
                        $record->is_top = (int) $record->is_top === 1 ? 0 : 1;
                        $record->save();
                        Notification::make()->title('已更新全站置顶')->success()->send();
                    }),
                Action::make('toggleGood')
                    ->label(fn (Thread $r) => $r->is_good ? '取消加精' : '加精')
                    ->action(function (Thread $record): void {
                        $record->is_good = ! $record->is_good;
                        $record->save();
                        Notification::make()->title('已更新加精')->success()->send();
                    }),
                Action::make('softDelete')
                    ->label('软删除')
                    ->color('danger')
                    ->visible(fn (Thread $r) => ! $r->is_deleted)
                    ->requiresConfirmation()
                    ->action(function (Thread $record): void {
                        $record->is_deleted = true;
                        $record->save();
                        Notification::make()->title('主题已删除')->success()->send();
                    }),
                DeleteAction::make()->label('硬删除'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageThreads::route('/')];
    }
}

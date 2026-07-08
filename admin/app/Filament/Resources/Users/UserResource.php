<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = '用户管理';

    protected static ?string $modelLabel = '用户';

    protected static ?string $pluralModelLabel = '用户';

    protected static string|UnitEnum|null $navigationGroup = '用户';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('username')->label('用户名')->required()->maxLength(32),
                TextInput::make('nickname')->label('昵称')->maxLength(32),
                TextInput::make('email')->label('邮箱')->email()->maxLength(190),
                TextInput::make('password')->label('新密码')->password()
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create'),
                Toggle::make('is_admin')->label('管理员'),
                Toggle::make('is_moderator')->label('版主'),
                TextInput::make('moderator_delete_limit')->label('版主删帖上限')->numeric()->default(0),
                Toggle::make('status')->label('账号启用')->default(true),
                DateTimePicker::make('mute_until')->label('禁言至'),
                TextInput::make('points')->label('积分')->numeric()->default(0),
                TextInput::make('coins')->label('金币')->numeric()->default(0),
                Select::make('group_id')->label('用户组')->relationship('group', 'name'),
                TextInput::make('signature')->label('签名')->maxLength(255),
                TextInput::make('avatar')->label('头像路径')->maxLength(255),
                TextInput::make('timezone')->label('时区')->maxLength(64),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('username')->label('用户名')->searchable(),
                TextColumn::make('nickname')->label('昵称')->searchable(),
                TextColumn::make('points')->label('积分')->sortable(),
                TextColumn::make('group.name')->label('用户组'),
                IconColumn::make('is_admin')->label('管理员')->boolean(),
                IconColumn::make('is_moderator')->label('版主')->boolean(),
                IconColumn::make('status')->label('启用')->boolean(),
                TextColumn::make('mute_until')->label('禁言至')->dateTime()->placeholder('—'),
                TextColumn::make('created_at')->label('注册时间')->dateTime()->sortable(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                Action::make('mute')
                    ->label('禁言')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->form([
                        TextInput::make('days')->label('天数')->numeric()->default(7)->required()->minValue(1),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->mute_until = now()->addDays((int) $data['days']);
                        $record->save();
                        Notification::make()->title('已禁言')->success()->send();
                    }),
                Action::make('unmute')
                    ->label('解除禁言')
                    ->icon(Heroicon::OutlinedCheck)
                    ->visible(fn (User $record) => $record->mute_until !== null)
                    ->action(function (User $record): void {
                        $record->mute_until = null;
                        $record->save();
                        Notification::make()->title('已解除禁言')->success()->send();
                    }),
                DeleteAction::make()->visible(fn (User $record) => ! $record->is_admin),
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
            'index' => ManageUsers::route('/'),
        ];
    }
}

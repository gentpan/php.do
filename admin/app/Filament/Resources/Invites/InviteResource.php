<?php

namespace App\Filament\Resources\Invites;

use App\Filament\Resources\Invites\Pages\ManageInvites;
use App\Models\Invite;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class InviteResource extends Resource
{
    protected static ?string $model = Invite::class;

    protected static ?string $navigationLabel = '邀请码';

    protected static ?string $modelLabel = '邀请码';

    protected static ?string $pluralModelLabel = '邀请码';

    protected static string|UnitEnum|null $navigationGroup = '用户';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label('邀请码')->default(fn () => strtoupper(Str::random(10)))->required()->maxLength(32),
            TextInput::make('note')->label('备注')->maxLength(100),
            DateTimePicker::make('expires_at')->label('过期时间'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('code')->label('邀请码')->searchable()->copyable(),
                TextColumn::make('note')->label('备注'),
                TextColumn::make('used_by')->label('使用者 ID')->placeholder('未使用'),
                TextColumn::make('used_at')->label('使用时间')->dateTime()->placeholder('—'),
                TextColumn::make('expires_at')->label('过期')->dateTime()->placeholder('不过期'),
                TextColumn::make('created_at')->label('创建')->dateTime(),
            ])
            ->recordActions([DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageInvites::route('/')];
    }
}

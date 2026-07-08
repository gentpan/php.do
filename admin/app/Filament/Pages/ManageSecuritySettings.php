<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageSecuritySettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = '防 CC 设置';

    protected static string|UnitEnum|null $navigationGroup = '系统';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = '防 CC 设置';

    protected static ?string $slug = 'security-settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'cc_enabled' => Setting::getValue('cc_enabled', '0') === '1',
            'cc_window_seconds' => Setting::getValue('cc_window_seconds', '60'),
            'cc_limit_count' => Setting::getValue('cc_limit_count', '60'),
            'cc_ban_hours' => Setting::getValue('cc_ban_hours', '2'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('cc_enabled')->label('开启防 CC'),
                TextInput::make('cc_window_seconds')->label('统计时间窗口（秒）')->numeric()->required(),
                TextInput::make('cc_limit_count')->label('允许访问次数')->numeric()->required(),
                TextInput::make('cc_ban_hours')->label('超过限制后封禁小时数')->numeric()->required(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        Setting::putValue('cc_enabled', ! empty($state['cc_enabled']) ? '1' : '0');
        Setting::putValue('cc_window_seconds', (string) ($state['cc_window_seconds'] ?? 60));
        Setting::putValue('cc_limit_count', (string) ($state['cc_limit_count'] ?? 60));
        Setting::putValue('cc_ban_hours', (string) ($state['cc_ban_hours'] ?? 2));

        Notification::make()->title('安全设置已保存')->success()->send();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('保存安全设置')
                                ->submit('save'),
                        ])->alignment(Alignment::Start),
                    ]),
            ]);
    }
}

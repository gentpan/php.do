<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\InteractsWithSettingsForm;
use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageSocialLogin extends Page
{
    use InteractsWithSettingsForm;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = '社交登录';

    protected static string|UnitEnum|null $navigationGroup = '安全';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = '社交登录';

    protected static ?string $slug = 'social-login';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'github_enabled' => Setting::getValue('oauth_github_enabled', '0') === '1',
            'github_callback' => $this->callbackUrl('github'),
            'github_client_id' => Setting::getValue('oauth_github_client_id', ''),
            // 出于安全，密钥不回显到浏览器；下方 helperText 会提示是否已配置
            'github_client_secret' => '',
            'google_enabled' => Setting::getValue('oauth_google_enabled', '0') === '1',
            'google_callback' => $this->callbackUrl('google'),
            'google_client_id' => Setting::getValue('oauth_google_client_id', ''),
            'google_client_secret' => '',
        ]);
    }

    /** 与前台 pd_oauth_redirect_uri() 保持一致的回调地址 */
    protected function callbackUrl(string $provider): string
    {
        $base = rtrim((string) (Setting::getValue('site_public_url', '') ?: config('app.url')), '/');

        return $base . '/api/oauth/' . $provider . '/callback';
    }

    /** 密钥字段提示：告知是否已配置，但不回显内容 */
    protected function secretHint(string $key): string
    {
        $v = (string) Setting::getValue($key, '');

        return $v === ''
            ? '尚未配置。'
            : '已配置（' . mb_strlen($v) . ' 位，结尾 ' . mb_substr($v, -4) . '）。留空表示不修改。';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('GitHub')->schema([
                    Toggle::make('github_enabled')->label('启用 GitHub 登录'),
                    TextInput::make('github_callback')
                        ->label('回调地址')
                        ->helperText('复制这一整串填到 GitHub OAuth App 的 Authorization callback URL，必须完全一致')
                        ->readOnly()
                        ->dehydrated(false),
                    TextInput::make('github_client_id')->label('Client ID'),
                    TextInput::make('github_client_secret')
                        ->label('Client Secret')
                        ->password()
                        ->revealable()
                        ->helperText($this->secretHint('oauth_github_client_secret')),
                ]),
                Section::make('Google')->schema([
                    Toggle::make('google_enabled')->label('启用 Google 登录'),
                    TextInput::make('google_callback')
                        ->label('回调地址')
                        ->helperText('复制这一整串填到 Google Cloud Console 的「已获授权的重定向 URI」，Google 为精确匹配')
                        ->readOnly()
                        ->dehydrated(false),
                    TextInput::make('google_client_id')->label('Client ID'),
                    TextInput::make('google_client_secret')
                        ->label('Client Secret')
                        ->password()
                        ->revealable()
                        ->helperText($this->secretHint('oauth_google_client_secret')),
                ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        Setting::putValue('oauth_github_enabled', ! empty($state['github_enabled']) ? '1' : '0');
        Setting::putValue('oauth_github_client_id', (string) ($state['github_client_id'] ?? ''));
        if (filled($state['github_client_secret'] ?? null)) {
            Setting::putValue('oauth_github_client_secret', (string) $state['github_client_secret']);
        }
        Setting::putValue('oauth_google_enabled', ! empty($state['google_enabled']) ? '1' : '0');
        Setting::putValue('oauth_google_client_id', (string) ($state['google_client_id'] ?? ''));
        if (filled($state['google_client_secret'] ?? null)) {
            Setting::putValue('oauth_google_client_secret', (string) $state['google_client_secret']);
        }

        Notification::make()->title('社交登录设置已保存')->success()->send();
    }

    protected function getSaveButtonLabel(): string
    {
        return '保存社交登录设置';
    }
}

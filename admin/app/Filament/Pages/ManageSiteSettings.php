<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\InteractsWithSettingsForm;
use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageSiteSettings extends Page
{
    use InteractsWithSettingsForm;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = '站点设置';

    protected static string|UnitEnum|null $navigationGroup = '运营展示';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = '站点设置';

    protected static ?string $slug = 'site-settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $keys = [
            'site_name', 'site_slogan', 'site_desc', 'site_keywords', 'icp_code', 'stats_code',
            'upload_max_mb', 'upload_allowed_exts', 'guest_download_enabled',
            'home_threads_per_page', 'forum_threads_per_page', 'thread_page_chars', 'reply_max_chars',
            'signin_base_coins', 'signin_streak_bonus',
            'register_ip_daily_limit', 'captcha_enabled', 'captcha_reply_free_count', 'require_invite', 'require_email_verify',
            'mail_method', 'mail_from_email', 'mail_from_name', 'smtp_host', 'smtp_port', 'smtp_secure', 'smtp_username', 'smtp_password', 'resend_api_key',
            's3_enabled', 's3_endpoint', 's3_region', 's3_bucket', 's3_access_key', 's3_secret_key', 's3_cdn_domain', 's3_path_prefix',
            'friend_links_enabled', 'friend_links', 'rewrite_enabled', 'rewrite_nginx_rules',
            'points_thread', 'points_reply', 'points_floor_reply', 'points_good_bonus', 'download_points_cost',
            'level_thresholds', 'level_names',
        ];

        $data = [];
        $secretKeys = ['smtp_password', 'resend_api_key', 's3_secret_key'];
        foreach ($keys as $key) {
            $value = Setting::getValue($key, '');
            if (in_array($key, [
                'guest_download_enabled', 'captcha_enabled', 's3_enabled',
                'friend_links_enabled', 'rewrite_enabled', 'require_invite', 'require_email_verify',
            ], true)) {
                $data[$key] = $value === '1';
            } elseif (in_array($key, $secretKeys, true)) {
                $data[$key] = '';
            } else {
                $data[$key] = $value;
            }
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('settings')->tabs([
                    Tab::make('基本信息')->schema([
                        TextInput::make('site_name')->label('站点标题'),
                        TextInput::make('site_slogan')->label('副标题')->helperText('显示在浏览器标签标题与首页“关于社区”卡片'),
                        Textarea::make('site_desc')->label('网站简介')->rows(3)->helperText('用于搜索引擎 meta description'),
                        TextInput::make('site_keywords')->label('关键词'),
                        Textarea::make('icp_code')->label('备案信息')->rows(2),
                        Textarea::make('stats_code')->label('统计代码')->rows(4),
                    ]),
                    Tab::make('上传附件')->schema([
                        TextInput::make('upload_max_mb')->label('上传大小 MB')->numeric(),
                        TextInput::make('upload_allowed_exts')->label('允许后缀'),
                        Toggle::make('guest_download_enabled')->label('允许游客下载压缩包'),
                    ]),
                    Tab::make('论坛发帖')->schema([
                        TextInput::make('home_threads_per_page')->label('首页每页帖数')->numeric(),
                        TextInput::make('forum_threads_per_page')->label('版块每页帖数')->numeric(),
                        TextInput::make('thread_page_chars')->label('主题分页字数')->numeric(),
                        TextInput::make('reply_max_chars')->label('回复最大字数')->numeric(),
                    ]),
                    Tab::make('积分等级')->schema([
                        TextInput::make('points_thread')->label('发帖分')->numeric(),
                        TextInput::make('points_reply')->label('回帖分')->numeric(),
                        TextInput::make('points_floor_reply')->label('楼中楼分')->numeric(),
                        TextInput::make('points_good_bonus')->label('加精奖励')->numeric(),
                        TextInput::make('download_points_cost')->label('下载扣积分')->numeric()->helperText('每个附件首次下载扣的积分，0=不扣费；上传者本人与管理员免费，扣的分转给上传者'),
                        Textarea::make('level_thresholds')->label('等级阈值')->rows(8)->helperText('每行 等级:积分'),
                        Textarea::make('level_names')->label('等级名称')->rows(8)->helperText('每行 等级:名称'),
                    ]),
                    Tab::make('金币签到')->schema([
                        TextInput::make('signin_base_coins')->label('基础金币')->numeric(),
                        TextInput::make('signin_streak_bonus')->label('连续奖励')->numeric(),
                    ]),
                    Tab::make('注册验证')->schema([
                        TextInput::make('register_ip_daily_limit')->label('同 IP 日注册上限')->numeric(),
                        Toggle::make('captcha_enabled')->label('开启验证码'),
                        TextInput::make('captcha_reply_free_count')->label('免验证码回复次数')->numeric(),
                        Toggle::make('require_invite')->label('注册需要邀请码')->helperText('开启后新用户必须填写有效邀请码才能注册（严格控制注册）'),
                        Toggle::make('require_email_verify')->label('注册需要邮箱验证码')->helperText('开启后注册须通过邮箱验证码（需先在「邮件」配置好发送方式）'),
                    ]),
                    Tab::make('邮件')->schema([
                        Select::make('mail_method')->label('邮件发送方式')->options([
                            'none' => '关闭（不发信）',
                            'smtp' => 'SMTP',
                            'resend' => 'Resend（API）',
                            'mail' => 'PHP mail()（本机 MTA）',
                        ])->default('none')->native(false),
                        TextInput::make('mail_from_email')->label('发件邮箱 From')->helperText('如 no-reply@php.do'),
                        TextInput::make('mail_from_name')->label('发件人名称')->helperText('留空则用站点名称'),
                        TextInput::make('resend_api_key')->label('Resend API Key')->password()->helperText('留空表示不修改；mail_method 选 Resend 时填写'),
                        TextInput::make('smtp_host')->label('SMTP 主机'),
                        TextInput::make('smtp_port')->label('SMTP 端口')->numeric()->helperText('465=SSL，587=TLS'),
                        Select::make('smtp_secure')->label('SMTP 加密')->options([
                            'tls' => 'STARTTLS（587）',
                            'ssl' => 'SSL/TLS（465）',
                            'none' => '无',
                        ])->default('tls')->native(false),
                        TextInput::make('smtp_username')->label('SMTP 用户名'),
                        TextInput::make('smtp_password')->label('SMTP 密码')->password()->helperText('留空表示不修改'),
                    ]),
                    Tab::make('对象存储')->schema([
                        Toggle::make('s3_enabled')->label('启用 S3/R2'),
                        TextInput::make('s3_endpoint')->label('Endpoint'),
                        TextInput::make('s3_region')->label('Region'),
                        TextInput::make('s3_bucket')->label('Bucket'),
                        TextInput::make('s3_access_key')->label('Access Key'),
                        TextInput::make('s3_secret_key')->label('Secret Key')->password()->helperText('留空表示不修改'),
                        TextInput::make('s3_cdn_domain')->label('CDN 域名'),
                        TextInput::make('s3_path_prefix')->label('路径前缀'),
                    ]),
                    Tab::make('友链伪静态')->schema([
                        Toggle::make('friend_links_enabled')->label('开启友情链接'),
                        Textarea::make('friend_links')->label('友情链接')->rows(5)->helperText('每行 名称|URL'),
                        Toggle::make('rewrite_enabled')->label('开启伪静态'),
                        Textarea::make('rewrite_nginx_rules')->label('Nginx 规则')->rows(8),
                    ]),
                ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testMail')
                ->label('发送测试邮件')
                ->icon('heroicon-o-envelope')
                ->color('gray')
                ->url('/api/admin-test-mail.php')
                ->openUrlInNewTab(),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $secretKeys = ['smtp_password', 'resend_api_key', 's3_secret_key'];
        foreach ($state as $key => $value) {
            if (in_array($key, $secretKeys, true) && blank($value)) {
                continue;
            }
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            Setting::putValue((string) $key, $value === null ? '' : (string) $value);
        }

        Notification::make()->title('设置已保存')->success()->send();
    }
}

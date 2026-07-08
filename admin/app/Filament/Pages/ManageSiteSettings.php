<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\InteractsWithSettingsForm;
use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
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
            'register_ip_daily_limit', 'captcha_enabled', 'captcha_reply_free_count',
            's3_enabled', 's3_endpoint', 's3_region', 's3_bucket', 's3_access_key', 's3_secret_key', 's3_cdn_domain', 's3_path_prefix',
            'friend_links_enabled', 'friend_links', 'rewrite_enabled', 'rewrite_nginx_rules',
            'points_thread', 'points_reply', 'points_floor_reply', 'points_good_bonus',
            'level_thresholds', 'level_names',
        ];

        $data = [];
        foreach ($keys as $key) {
            $value = Setting::getValue($key, '');
            if (in_array($key, [
                'guest_download_enabled', 'captcha_enabled', 's3_enabled',
                'friend_links_enabled', 'rewrite_enabled',
            ], true)) {
                $data[$key] = $value === '1';
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
                        TextInput::make('site_name')->label('站点名称'),
                        TextInput::make('site_slogan')->label('站点副标题')->helperText('显示在浏览器标签标题与首页“关于社区”卡片'),
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
                    ]),
                    Tab::make('对象存储')->schema([
                        Toggle::make('s3_enabled')->label('启用 S3/R2'),
                        TextInput::make('s3_endpoint')->label('Endpoint'),
                        TextInput::make('s3_region')->label('Region'),
                        TextInput::make('s3_bucket')->label('Bucket'),
                        TextInput::make('s3_access_key')->label('Access Key'),
                        TextInput::make('s3_secret_key')->label('Secret Key')->password(),
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

    public function save(): void
    {
        $state = $this->form->getState();
        foreach ($state as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            Setting::putValue((string) $key, $value === null ? '' : (string) $value);
        }

        Notification::make()->title('设置已保存')->success()->send();
    }
}

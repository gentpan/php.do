# php.do Admin（Filament）

Laravel 12 + Filament 后台，目录即论坛仓库内的 `admin/`。

- 入口：https://php.do/admin
- 前台管理操作（置顶/加精/删帖等）仍走同目录下的 `action.php`（经论坛会话鉴权）

## 本地开发

```bash
# 论坛库隧道示例
ssh -i ~/.ssh/gentpan.pem -N -L 3307:127.0.0.1:3306 root@65.109.62.100 &

cd admin
cp .env.example .env   # 或复用已有 .env
composer install
php artisan serve --host=127.0.0.1 --port=8001
```

打开：http://127.0.0.1:8001/admin/login

## 生产

- 代码路径：`/var/www/php.do/admin`
- **由 FrankenPHP 直接托管** `admin/public`（Caddy 对 `/admin*`、`/livewire*`、`/css|js|fonts/filament*` 设 root）
- 不使用 `php artisan serve` / 反代
- 前台管理接口仍走 `/admin/action.php`（论坛 PHP，优先于 Laravel 处理）

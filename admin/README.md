# php.do Admin（Filament）

Laravel 13 + Filament 5 后台，位于论坛仓库内的 `admin/`。

- 入口：`/admin`
- 前台管理操作（置顶/加精/删帖等）走同目录下的 `action.php`（经论坛会话鉴权）

## 本地开发

```bash
cd admin
cp .env.example .env   # 或复用已有 .env
composer install
php artisan serve --host=127.0.0.1 --port=8001
```

打开：http://127.0.0.1:8001/admin/login

> 后台与论坛共用同一个 MySQL 库；本地开发如需连接远程库，可自行用 SSH 隧道映射端口（主机信息见你自己的私有部署记录，勿写入仓库）。

## 生产

- 由 FrankenPHP 直接托管 `admin/public`
- 不使用 `php artisan serve` / 反向代理
- 前台管理接口走 `/admin/action.php`（论坛 PHP，优先于 Laravel 处理）

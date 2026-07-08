# php.do（LiteBBS）

轻量 PHP 论坛程序，线上站点：[https://php.do](https://php.do)

前台为原生 PHP + mysqli，后台为 Laravel Filament，统一部署在 FrankenPHP 之上。

---

## 技术栈

| 层级 | 技术 |
|------|------|
| Web 服务器 | **FrankenPHP**（内置 **Caddy 2.x**，`php_server` 模式） |
| 语言 | **PHP 8.5+** |
| 数据库 | **MySQL 8.x / 9.x**（`mysqli` 原生 SQL，无框架 ORM） |
| 前台 UI | **Alpine.js** + **Preline UI** + 手写 CSS（无 Tailwind、无前端构建链） |
| 后台 | **Laravel 12** + **Filament 3**（`admin/` 目录） |
| 会话 / 鉴权 | PHP Session；前台管理操作走 `admin/action.php` |

前台静态资源：`assets/main.css`、`assets/main.js`（生产环境自动优先加载 `main.min.js`）。

---

## 环境要求

- PHP **8.5+**（不再支持 8.4 及以下）
- 扩展：`mysqli`、`json`（必须）；`mbstring`（建议）；`curl`（启用 S3/R2 上传时需要）
- MySQL **8.x** 或 **9.x**（不支持 MySQL 5.7 / MariaDB）
- 安装与运行时由 `compat.php` 自动校验版本

---

## 本地安装

1. 克隆仓库，将 `config.example.php` 复制为 `config.php` 并填写数据库信息。
2. 浏览器访问 `install/install.php` 自动建表。
3. 默认管理员：`admin` / `admin123`（登录后请立即修改密码）。
4. 安装完成后删除 `install/install.php`。
5. （可选）配置 `admin/.env` 后进入 Filament 后台：`php artisan serve` 或按下方生产部署说明。

若已安装旧版本，覆盖文件后访问一次 `install/upgrade.php` 升级表结构；完成后建议删除该文件。

---

## 服务器部署（FrankenPHP）

生产环境以 **FrankenPHP 单进程** 同时托管论坛前台与 Filament 后台，不使用 Nginx 反代或 `php artisan serve`。

### 线上架构（php.do 示例）

| 项目 | 路径 / 说明 |
|------|-------------|
| 站点根目录 | `/var/www/php.do` |
| Git 裸仓库 | `/var/www/php.do.git` |
| Caddy 配置 | `/etc/frankenphp/Caddyfile` |
| systemd 服务 | `frankenphp.service`（用户 `www-data`） |
| 文件属主 | `debian:www-data`（上传目录需 `www-data` 可写） |

### Caddy 路由要点

```text
php.do {
    root * /var/www/php.do

    # 论坛原生管理接口（不进 Laravel）
    /admin/action.php  →  php_server

    # Filament / Laravel
    /admin* /livewire* /css|js|fonts/filament*  →  root admin/public + php_server

    # 其余请求
    php_server
}
```

伪静态（版块 slug、帖子 `/thread/{id}.html`、API `/api/*` 等）由 Caddy `rewrite` 与前台 `index.php` 路由共同完成。

FrankenPHP 全局 `php_ini` 示例：`memory_limit 256M`、`upload_max_filesize 20M`、`session.save_path` 指向服务器 session 目录。

### Git 推送自动部署

本地配置 remote：

```text
origin  https://github.com/gentpan/php.do.git
server  root@65.109.62.100:/var/www/php.do.git
```

日常发布：

```bash
git add …
git commit -m "type: 中文描述"
GIT_SSH_COMMAND="ssh -i ~/.ssh/gentpan.pem" git push server main
```

`post-receive` hook 会自动：

1. `git checkout -f main` 到 `/var/www/php.do`
2. 创建并修正 `assets/avatars`、`uploads/`、`storage/sessions` 权限
3. 在 `admin/` 执行 `composer install --no-dev`、`php artisan filament:assets`、`php artisan optimize`
4. `systemctl reload frankenphp`

仅同步 Filament 目录调试时，可使用 `admin/deploy/deploy.sh`（rsync + composer + reload）。

### 首次上线检查清单

- [ ] `config.php`（论坛库）与 `admin/.env`（Laravel 库，通常同一 MySQL）已配置
- [ ] `install/install.php` 已执行，安装文件已删除
- [ ] FrankenPHP 服务已启用：`systemctl enable --now frankenphp`
- [ ] Caddy 已阻止直接访问 `config.php`、`install/*`、`.env` 等敏感路径
- [ ] HTTPS 证书由 Caddy 自动申请（域名指向服务器）

---

## 目录结构（摘要）

```text
index.php          # 前台路由入口
pages/             # 论坛页面
api/               # AJAX 接口
functions.php      # 核心函数
header.php footer.php
assets/            # CSS / JS / 头像等
admin/             # Laravel + Filament 后台
install/           # 安装与升级脚本
config.php         # 数据库配置（不入库，参考 config.example.php）
```

---

## 主要功能

- 用户注册、登录、OAuth、个人中心、私信
- 版块导航、发帖回帖、搜索、标签、投票与表情
- 签到、积分、排行榜、系统通知
- 伪静态 URL、RSS、深浅色主题（含跟随系统）
- 后台：站点设置、版块、用户、广告、安全、邀请码等（Filament）
- 附件上传；可选 S3/R2 对象存储
- 高级验证码、IP 记录与封禁表

Filament 后台开发说明见 [admin/README.md](admin/README.md)。

---

## 许可证

专有软件（proprietary），见 `composer.json`。

# php.do（LiteBBS）

轻量 PHP 论坛程序，线上站点：[https://php.do](https://php.do)

前台为原生 PHP + mysqli，后台为 Laravel Filament，生产环境统一部署在 **FrankenPHP + PHP 8.5 + MySQL + Caddy** 之上。

---

## 部署环境

php.do 线上生产栈（php.do 服务器当前版本示例）：

| 组件 | 版本 / 说明 |
|------|-------------|
| **FrankenPHP** | v1.12.x，`frankenphp run` + `php_server` 工作模式 |
| **PHP** | **8.5+**（CLI / 内置运行时，扩展：`mysqli`、`json` 等） |
| **MySQL** | **8.x / 9.x**（`pd_forum` 库，`mysqli` 直连，无 ORM） |
| **Caddy** | **2.11.x**（内置于 FrankenPHP，配置文件 `/etc/frankenphp/Caddyfile`） |

关系简述：

```text
FrankenPHP
├── 内置 Caddy 2.x     → HTTPS、路由、gzip、伪静态 rewrite
├── 内置 PHP 8.5       → 论坛前台 + Filament 后台同进程执行
└── 连接 MySQL 8.x     → 数据存储
```

**不需要**单独安装 Nginx、PHP-FPM 或 `php artisan serve` 反代；Caddy 与 PHP 均由 FrankenPHP 统一提供。

---

## 技术栈

| 层级 | 技术 |
|------|------|
| 运行环境 | **FrankenPHP** + **PHP 8.5** + **MySQL** + **Caddy** |
| Web 模式 | Caddy `php_server`（非 `php_fastcgi` / PHP-FPM） |
| 前台 | 原生 PHP + `mysqli`（`pages/`、`api/`、`functions.php`） |
| 前台 UI | **Alpine.js**（移动端菜单、头像切换等）+ 手写 CSS（无 Tailwind、无构建链）；国旗用 flag-icons |
| 后台 | **Laravel 13** + **Filament 5**（`admin/`） |
| 会话 / 鉴权 | PHP Session；前台管理操作走 `admin/action.php` |

前台静态资源：`assets/main.css`、`assets/main.js`（生产环境自动优先加载 `main.min.js`）。

代码命名：统一使用 `pd_` / `pd-` 前缀（表、函数、样式）。详见「命名约定」。

---

## 环境要求

开发与生产均需满足：

- **PHP 8.5+**（不再支持 8.4 及以下）
- **MySQL 8.x** 或 **9.x**（不支持 MySQL 5.7 / MariaDB）
- PHP 扩展：`mysqli`、`json`（必须）；`mbstring`（建议）；`curl`（S3/R2 上传时需要）
- 生产推荐：**FrankenPHP + Caddy**（与线上保持一致）
- 安装与运行时由 `compat.php` 自动校验 PHP / MySQL 版本

---

## 本地安装

1. 克隆仓库，将 `config.example.php` 复制为 `config.php` 并填写 MySQL 信息。
2. 浏览器访问 `install/install.php` 自动建表。
3. 默认管理员：`admin` / `admin123`（登录后请立即修改密码）。
4. 安装完成后删除 `install/install.php`。
5. （可选）配置 `admin/.env` 后本地调试 Filament：`cd admin && php artisan serve`

若已安装旧版本，覆盖文件后访问一次 `install/upgrade.php` 升级表结构；完成后建议删除该文件。

本地若无 FrankenPHP，可用任意 PHP 8.5 内置服务器或传统 FPM 临时跑通；**上线请以 FrankenPHP + Caddy 为准**。

---

## 服务器部署（FrankenPHP + PHP 8.5 + MySQL + Caddy）

### 线上架构（php.do 示例）

| 项目 | 路径 / 说明 |
|------|-------------|
| 域名 | `php.do`（`www.php.do` 301 跳转至裸域） |
| 站点根目录 | `/var/www/php.do` |
| Git 裸仓库 | `/var/www/php.do.git` |
| Caddy 配置 | `/etc/frankenphp/Caddyfile` |
| systemd 服务 | `frankenphp.service`（`User=www-data`） |
| MySQL 数据库 | `pd_forum`（库名；表前缀 `pd_*`） |
| 文件属主 | `debian:www-data`（`uploads/`、`storage/` 需 `www-data` 可写） |

### FrankenPHP 全局 PHP 配置（Caddyfile 片段）

```caddyfile
{
    frankenphp {
        php_ini memory_limit 256M
        php_ini upload_max_filesize 20M
        php_ini post_max_size 21M
        php_ini max_execution_time 60
        php_ini session.save_path /var/lib/php/sessions
    }
}
```

### Caddy 站点路由要点

```caddyfile
# www 统一 301 跳转到裸域（后台 APP_URL 固定为 https://php.do，避免跨源）
www.php.do {
    redir https://php.do{uri} permanent
}

php.do {
    root * /var/www/php.do
    encode gzip

    # 论坛原生管理接口（不进 Laravel）
    handle /admin/action.php {
        rewrite * /admin/action.php
        php_server
    }

    # Filament / Laravel → admin/public
    handle /admin* /livewire* /css/filament* /js/filament* /fonts/filament* {
        root * /var/www/php.do/admin/public
        php_server {
            try_files {path} index.php
        }
    }

    # 伪静态 rewrite（版块 slug、/thread/{id}.html、/api/* 等）
    # …

    php_server
}
```

HTTPS 由 Caddy 自动申请与续期；敏感路径（`config.php`、`install/*`、`.env`）在 Caddy 层 `respond 403`。

### Git 推送自动部署

本地 remote 示例：

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
4. `systemctl reload frankenphp`（热重载 Caddy 配置）

仅同步 Filament 目录调试时，可使用 `admin/deploy/deploy.sh`（rsync + composer + reload）。

### 首次上线检查清单

- [ ] 服务器已安装 **FrankenPHP**、**PHP 8.5**、**MySQL 8.x**
- [ ] `/etc/frankenphp/Caddyfile` 已配置站点与 `php_server`
- [ ] `systemctl enable --now frankenphp` 服务正常
- [ ] `config.php`（论坛）与 `admin/.env`（Laravel，通常同一 MySQL）已配置
- [ ] `install/install.php` 已执行，安装文件已删除
- [ ] Caddy 已拦截 `config.php`、`install/*`、`.env` 等敏感路径
- [ ] 域名 DNS 已指向服务器，HTTPS 证书生效

---

## 命名约定

| 前缀 | 含义 | 示例 |
|------|------|------|
| `pd_` / `pd-` | 论坛核心与主题 UI 统一前缀 | 表 `pd_users`、函数 `pd_url_page()`、类 `.pd-thread-row` |
| `PD_` | PHP 常量 | `PD_START`（页面计时起点） |

线上若仍存在 `qf_*` 数据表，首次访问会由 `pd_migrate_schema_prefix_from_qf()` 自动重命名为 `pd_*`。

站点对外品牌仍为 **php.do**（域名与展示名称不变）。

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
- 独立信息页（关于 / 帮助 / 规则 / 隐私政策），采用精简 banner + 内容布局；规则页含社区规则与使用条款
- 后台：站点设置、版块、用户、广告、安全、邀请码等（Filament）
- 附件上传；可选 S3/R2 对象存储
- 高级验证码、IP 记录与封禁表

Filament 后台开发说明见 [admin/README.md](admin/README.md)。

---

## 许可证

专有软件（proprietary），见 `composer.json`。

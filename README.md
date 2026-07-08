# php.do（LiteBBS）

轻量 PHP 论坛程序 · 线上站点：[https://php.do](https://php.do)

前台为原生 PHP + `mysqli`，后台为 Laravel + Filament，应用运行在 **FrankenPHP + PHP 8.5 + MySQL** 之上（单进程内置 HTTPS、路由与伪静态，无需 Nginx / PHP-FPM）。

---

## 技术栈

| 层级 | 技术 |
|------|------|
| 运行环境 | **FrankenPHP** + **PHP 8.5** + **MySQL** |
| 前台 | 原生 PHP + `mysqli`（`pages/`、`api/`、`core/`、`functions.php`） |
| 前台 UI | Alpine.js（移动端菜单、头像切换等）+ 手写 CSS（无 Tailwind、无构建链）；国旗用 flag-icons |
| 后台 | **Laravel 13** + **Filament 5**（`admin/`） |
| 会话 / 鉴权 | PHP Session；支持密码、OAuth（GitHub/Google/X/Discord）、Passkey；可选邮箱验证码 |

前台静态资源：`assets/main.css`、`assets/main.js`（生产环境自动优先加载 `main.min.js`）。

命名约定：核心与主题统一使用 `pd_` / `pd-` 前缀（表、函数、样式）。

---

## 环境要求

- **PHP 8.5+**
- **MySQL 8.x** 或 **9.x**
- PHP 扩展：`mysqli`、`json`（必须）；`mbstring`（建议）；`curl`（S3/R2、Resend 发信时需要）
- 应用服务器：**FrankenPHP**（推荐；也可用任意 PHP 8.5 内置服务器或 FPM 临时跑通开发环境）
- 安装与运行时由 `compat.php` 自动校验 PHP / MySQL 版本

---

## 安装

1. 克隆仓库，将 `config.example.php` 复制为 `config.php` 并填写数据库信息。
2. 浏览器访问 `install/install.php` 自动建表。
3. 默认管理员：`admin` / `admin123`（登录后请立即修改密码）。
4. 安装完成后删除 `install/install.php`。
5. （可选）配置 `admin/.env` 后本地调试 Filament 后台：`cd admin && composer install && php artisan serve`

升级旧版本：覆盖文件后访问一次 `install/upgrade.php` 升级表结构，完成后删除该文件。

> 生产环境请确保对外屏蔽敏感路径（`config.php`、`install/*`、`admin/.env` 等），并为 `uploads/`、`storage/` 目录赋予运行用户写权限。

---

## 目录结构（摘要）

```text
index.php          # 前台路由入口
functions.php      # 引导加载器（config/compat/session → require core/*）
core/              # 核心函数按领域拆分的模块（util/user/content/mail/…）
core/vendor/       # 内置第三方库（Parsedown、Markdown 编辑器组件）
pages/             # 论坛页面（渲染 HTML）
api/               # 端点（AJAX / 文件下载 / RSS 等）
header.php footer.php
assets/            # CSS / JS / 头像 / 品牌 logo 等
admin/             # Laravel + Filament 后台
install/           # 安装与升级脚本
config.php         # 数据库配置（不入库，参考 config.example.php）
```

---

## 主要功能

- 用户注册（可选邀请码 / 邮箱验证码）、登录、找回密码、OAuth、Passkey、个人中心、私信
- 版块导航、发帖回帖、搜索、标签、投票与表情
- 签到、积分、金币、排行榜、系统通知
- 附件上传与下载（可配置下载扣积分）；可选 S3/R2 对象存储
- 可配置邮件系统（SMTP / Resend）
- 独立信息页（关于 / 帮助 / 规则 / 隐私政策），精简 banner + 内容布局
- 伪静态 URL、RSS、深浅色主题（含跟随系统）
- 后台：站点设置、版块、用户、广告、安全、邀请码、邮件等（Filament）

Filament 后台开发说明见 [admin/README.md](admin/README.md)。

---

## 许可证

专有软件（proprietary），见 `composer.json`。

# 部署记录（php.do）

生产环境基础设施配置，纳入版本管理以便追踪。修改后需同步到服务器并 reload。

## 服务器

| 项目 | 值 |
|------|-----|
| 站点 | https://php.do 、https://www.php.do 、后台 https://php.do/admin |
| IP | `65.109.62.100` |
| SSH | `ssh -i ~/.ssh/gentpan.pem root@65.109.62.100` |
| 代码根目录 | `/var/www/php.do`（后台在 `/var/www/php.do/admin`） |

## 技术栈

| 组件 | 版本 | 说明 |
|------|------|------|
| FrankenPHP | v1.12.4 | `/usr/local/bin/frankenphp`，内嵌 Caddy + PHP |
| Caddy | v2.11.4 | FrankenPHP 内嵌，无独立 caddy 服务 |
| PHP | 8.5.x | CLI 8.5.4 / FrankenPHP 内嵌 8.5.8 |
| 进程管理 | systemd | `frankenphp.service`（User=www-data） |

> 注意：本机没有独立的 Caddy 进程，Web 层由 FrankenPHP 内嵌的 Caddy 提供。
> `/etc/caddy/` 下的文件是历史遗留备份，**生效的是 `/etc/frankenphp/Caddyfile`**。

## 文件对应关系

| 仓库文件 | 服务器路径 |
|----------|-----------|
| `deploy/Caddyfile` | `/etc/frankenphp/Caddyfile`（生效配置） |
| `deploy/frankenphp.service` | `/etc/systemd/system/frankenphp.service` |

## 常用操作

```bash
# 日常部署（推荐）：post-receive 钩子自动 composer + optimize + reload
git push server main

# 单独同步后台代码（rsync 调试用）
./admin/deploy/deploy.sh

# 更新 Caddyfile 后同步并热加载
scp -i ~/.ssh/gentpan.pem deploy/Caddyfile root@65.109.62.100:/etc/frankenphp/Caddyfile
ssh -i ~/.ssh/gentpan.pem root@65.109.62.100 'systemctl reload frankenphp'

# 更新 systemd unit 后
scp -i ~/.ssh/gentpan.pem deploy/frankenphp.service root@65.109.62.100:/etc/systemd/system/frankenphp.service
ssh -i ~/.ssh/gentpan.pem root@65.109.62.100 'systemctl daemon-reload && systemctl restart frankenphp'

# 查看状态 / 日志
ssh -i ~/.ssh/gentpan.pem root@65.109.62.100 'systemctl status frankenphp'
ssh -i ~/.ssh/gentpan.pem root@65.109.62.100 'journalctl -u frankenphp -n 100 --no-pager'
```

<?php
/* Tailwind v4 + daisyUI 试点预览页（登录/注册）——独立布局，不含 main.css/共享 chrome。
   仅用于验证 CDN 接法与视觉方向，确认后再正式替换 login/register。 */
require_once __DIR__ . '/../functions.php';
$login_action = h(pd_url_page('api/auth.php', array('action' => 'login')));
$register_action = h(pd_url_page('api/auth.php', array('action' => 'register')));
?><!doctype html>
<html lang="zh-CN" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>UI 预览：登录/注册（Tailwind + daisyUI）</title>
    <link rel="stylesheet" href="https://static.bluecdn.com/libs/fontawesome/7.3.0/css/all.min.css">
    <link rel="stylesheet" href="assets/fonts/fira.css">
    <!-- daisyUI 组件（纯 CSS） -->
    <link rel="stylesheet" href="https://static.bluecdn.com/npm/daisyui@5/daisyui.css">
    <!-- Tailwind v4 浏览器运行时（仅生成工具类；浏览器版不支持 @plugin/config） -->
    <script src="https://static.bluecdn.com/npm/@tailwindcss/browser@4/dist/index.global.js"></script>
    <style>
        /* 覆盖 daisyUI light 主题配色，对齐现有站点（普通 CSS，非 @plugin） */
        [data-theme="light"] {
            --color-base-100: #ffffff;
            --color-base-200: #f7f7f8;
            --color-base-300: #eceff3;
            --color-base-content: #333333;
            --color-primary: #505b93;
            --color-primary-content: #ffffff;
            --color-secondary: #ff674f;
            --color-secondary-content: #ffffff;
            --color-accent: #f5a623;
            --color-accent-content: #ffffff;
            --radius-box: 0.5rem;
            --radius-field: 0.5rem;
        }
        body { font-family: "Fira Sans", -apple-system, "PingFang SC", sans-serif; }
        .phpdo-banner { background: linear-gradient(120deg, #5a6aa8 0%, #505b93 58%, #3f4874 100%); }
    </style>
</head>
<body class="min-h-screen bg-base-200 text-base-content">

    <header class="phpdo-banner">
        <div class="mx-auto max-w-5xl px-5 h-40 flex items-end pb-6">
            <a href="<?php echo h(pd_url_page('index.php')); ?>" class="inline-flex items-center">
                <img src="assets/logo-white.svg" alt="php.do" class="h-12 w-auto">
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-5 py-10">
        <div class="mb-6 rounded-lg bg-warning/15 text-warning-content px-4 py-3 text-sm">
            这是 Tailwind + daisyUI 的<strong>试点预览</strong>（登录/注册），不影响正式页面。请查看外观并反馈。
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <!-- 登录卡 -->
            <section class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <h1 class="card-title text-2xl font-extrabold">登录</h1>
                    <p class="text-sm opacity-60 -mt-1">进入你的 php.do 账号，继续发帖、回复和管理资料。</p>
                    <form method="post" action="<?php echo $login_action; ?>" class="mt-3 flex flex-col gap-3">
                        <label class="form-control w-full">
                            <span class="label-text mb-1 block text-sm font-medium">用户名</span>
                            <input name="username" required autocomplete="username" class="input input-bordered w-full">
                        </label>
                        <label class="form-control w-full">
                            <span class="label-text mb-1 block text-sm font-medium">密码</span>
                            <input type="password" name="password" required autocomplete="current-password" class="input input-bordered w-full">
                        </label>
                        <button type="submit" class="btn btn-primary w-full">登录</button>
                        <button type="button" class="btn btn-outline w-full"><i class="fa-solid fa-key mr-1"></i> 使用 Passkey 登录</button>
                    </form>
                    <div class="divider text-xs opacity-50">或使用第三方账号</div>
                    <div class="flex flex-col gap-2">
                        <a class="btn btn-ghost border border-base-300 w-full justify-start"><i class="fa-brands fa-github mr-2"></i> 使用 GitHub 登录</a>
                        <a class="btn btn-ghost border border-base-300 w-full justify-start"><i class="fa-brands fa-google mr-2"></i> 使用 Google 登录</a>
                    </div>
                </div>
            </section>

            <!-- 注册卡 -->
            <section class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <h1 class="card-title text-2xl font-extrabold">注册</h1>
                    <p class="text-sm opacity-60 -mt-1">创建 php.do 账号，加入技术讨论。</p>
                    <form method="post" action="<?php echo $register_action; ?>" class="mt-3 flex flex-col gap-3">
                        <label class="form-control w-full">
                            <span class="label-text mb-1 block text-sm font-medium">用户名</span>
                            <input name="username" required class="input input-bordered w-full">
                        </label>
                        <label class="form-control w-full">
                            <span class="label-text mb-1 block text-sm font-medium">电子邮箱</span>
                            <input type="email" name="email" required class="input input-bordered w-full">
                        </label>
                        <label class="form-control w-full">
                            <span class="label-text mb-1 block text-sm font-medium">密码</span>
                            <input type="password" name="password" required class="input input-bordered w-full">
                        </label>
                        <label class="form-control w-full">
                            <span class="label-text mb-1 block text-sm font-medium">验证码</span>
                            <div class="flex gap-2">
                                <input name="captcha_code" maxlength="4" class="input input-bordered flex-1" placeholder="4 位字符">
                                <span class="btn btn-ghost border border-base-300 font-mono">A7c9</span>
                            </div>
                        </label>
                        <button type="submit" class="btn btn-primary w-full">注册</button>
                    </form>
                    <p class="text-sm opacity-70 mt-1">已有账号？<a class="link link-primary" href="<?php echo h(pd_url_page('login.php')); ?>">去登录</a></p>
                </div>
            </section>
        </div>

        <p class="mt-8 text-center text-xs opacity-50">Tailwind v4（@tailwindcss/browser）+ daisyUI 5 · 通过 static.bluecdn.com 加载</p>
    </main>

</body>
</html>

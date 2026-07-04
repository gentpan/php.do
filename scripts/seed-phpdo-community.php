<?php
require_once __DIR__ . '/../functions.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('404 Not Found');
}

mt_srand(20260705);

function seed_phpdo_pick($items, $index) {
    return $items[$index % count($items)];
}

function seed_phpdo_sql($value) {
    return esc($value);
}

function seed_phpdo_forum($name, $description, $categories, $order, $preferred_id = 0) {
    $name_sql = seed_phpdo_sql($name);
    $desc_sql = seed_phpdo_sql($description);
    $cats_sql = seed_phpdo_sql(implode("\n", $categories));
    $enabled = count($categories) > 0 ? 1 : 0;
    if ($preferred_id > 0) {
        $rs = mysqli_query(db(), "SELECT id FROM qf_forums WHERE id=" . intval($preferred_id) . " LIMIT 1");
        if ($rs && mysqli_fetch_assoc($rs)) {
            mysqli_query(db(), "UPDATE qf_forums SET name='{$name_sql}', description='{$desc_sql}', topic_category_enabled={$enabled}, topic_categories='{$cats_sql}', display_order={$order} WHERE id=" . intval($preferred_id));
            return intval($preferred_id);
        }
    }
    $rs = mysqli_query(db(), "SELECT id FROM qf_forums WHERE name='{$name_sql}' LIMIT 1");
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    if ($row) {
        $id = intval($row['id']);
        mysqli_query(db(), "UPDATE qf_forums SET description='{$desc_sql}', topic_category_enabled={$enabled}, topic_categories='{$cats_sql}', display_order={$order} WHERE id={$id}");
        return $id;
    }
    mysqli_query(db(), "INSERT INTO qf_forums (name,description,topic_category_enabled,topic_categories,display_order,created_at) VALUES ('{$name_sql}','{$desc_sql}',{$enabled},'{$cats_sql}',{$order},NOW())");
    return intval(mysqli_insert_id(db()));
}

$forum_specs = array(
    array('站务公告', 'php.do 公告、规则、功能更新和反馈处理。', array('公告', '规则', '反馈', '更新'), 10, 1),
    array('PHP 技术讨论', 'PHP 语言、运行时、工程实践和疑难问题。', array('PHP 8.x', '语法特性', '错误排查', '最佳实践'), 20, 2),
    array('程序发布', '发布 PHP 程序、开源项目、插件、主题和版本更新。', array('开源项目', '商业程序', '插件扩展', '版本更新'), 30, 3),
    array('框架生态', 'Laravel、Symfony、ThinkPHP、Hyperf、Yii 等框架交流。', array('Laravel', 'Symfony', 'ThinkPHP', 'Hyperf', '框架选型'), 40, 0),
    array('Composer 与依赖', 'Composer、Packagist、自动加载、依赖升级和包维护。', array('Composer', 'Packagist', '依赖升级', '包开发'), 50, 0),
    array('扩展与性能', 'PHP 扩展、Opcache、Swoole、Redis、性能分析和压测。', array('Opcache', 'Swoole', 'Redis', '性能调优', '扩展开发'), 60, 0),
    array('数据库与缓存', 'MySQL、MariaDB、PostgreSQL、Redis、队列和缓存设计。', array('MySQL', 'PostgreSQL', 'Redis', '队列', '索引优化'), 70, 0),
    array('部署与运维', 'Nginx、PHP-FPM、Docker、CI/CD、证书和线上排障。', array('Nginx', 'PHP-FPM', 'Docker', 'CI/CD', 'HTTPS'), 80, 0),
    array('安全审计', '登录认证、权限控制、XSS、CSRF、SQL 注入和依赖安全。', array('认证授权', 'XSS', 'CSRF', 'SQL 注入', '依赖安全'), 90, 0),
    array('代码求助', '贴代码、贴报错、说环境，互相帮忙定位问题。', array('报错求助', '代码审查', '环境配置', '疑难杂症'), 100, 0),
);

$forums = array();
$forum_categories = array();
foreach ($forum_specs as $spec) {
    $forum_id = seed_phpdo_forum($spec[0], $spec[1], $spec[2], $spec[3], $spec[4]);
    $forums[] = $forum_id;
    $forum_categories[$forum_id] = $spec[2];
}

$nicknames = array(
    'Opcode观察员', 'Composer搬运工', 'Laravel老周', 'Symfony小林', 'FPM调参师', '扩展编译员',
    'Redis队列工', 'Nginx木匠', '安全审计猫', 'PHP八点四', 'MariaDB船长', '日志追踪者',
    '单元测试君', '容器发布员', '缓存击穿者', '类型声明派', '开源维护者', '包管理阿澈',
    '框架选型师', '线上救火队', '接口设计师', '异常收集员', '慢查询猎人', '会话守门员',
    '文档翻译者', '脚本自动化', '消息队列员', 'WebSocket客', 'ORM怀疑者', '配置洁癖'
);
$signatures = array(
    '写 PHP，也写部署文档。',
    '先看 error_log，再看代码。',
    'Composer update 前先备份。',
    '喜欢小而清晰的程序。',
    '线上问题优先复现。',
    '让 PHP-FPM 安静工作。',
);
$usernames = array(
    'opcode_watcher', 'composer_guy', 'zhou_laravel', 'lin_symfony', 'fpm_tuner', 'ext_builder',
    'redis_queue', 'nginx_worker', 'audit_notes', 'php84_user', 'mariadb_captain', 'trace_log',
    'unit_test', 'container_shipper', 'cache_guard', 'typed_php', 'oss_keeper', 'packagist_chen',
    'framework_picker', 'prod_firefight', 'api_designer', 'exception_hunter', 'slow_query', 'session_guard',
    'doc_translator', 'script_auto', 'queue_runner', 'websocket_user', 'orm_skeptic', 'config_cleaner'
);

$users = array();
for ($i = 1; $i <= 30; $i++) {
    $username = $usernames[$i - 1];
    $nickname = $nicknames[$i - 1];
    $username_sql = seed_phpdo_sql($username);
    $rs = mysqli_query(db(), "SELECT id FROM qf_users WHERE username='{$username_sql}' LIMIT 1");
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    if ($row) {
        $users[] = intval($row['id']);
        continue;
    }
    $nickname_sql = seed_phpdo_sql($nickname);
    $signature_sql = seed_phpdo_sql(seed_phpdo_pick($signatures, $i));
    $password_sql = seed_phpdo_sql(qf_password_hash('phpdo123456'));
    $coins = 20 + mt_rand(0, 680);
    $ip = '10.80.' . intval($i % 255) . '.' . intval(30 + $i);
    mysqli_query(db(), "INSERT INTO qf_users (username,password,nickname,signature,status,coins,reply_count,ip,created_at) VALUES ('{$username_sql}','{$password_sql}','{$nickname_sql}','{$signature_sql}',1,{$coins},0,'{$ip}',DATE_SUB(NOW(), INTERVAL " . mt_rand(2, 160) . " DAY))");
    $user_id = intval(mysqli_insert_id(db()));
    $avatar = qf_generate_default_avatar($user_id, $username, $nickname);
    if ($avatar !== '') {
        $avatar_sql = seed_phpdo_sql($avatar);
        mysqli_query(db(), "UPDATE qf_users SET avatar='{$avatar_sql}' WHERE id={$user_id}");
    }
    $users[] = $user_id;
}

$topics = array(
    array(1, 'php.do 站点规则和发帖格式建议', '公告', '建议提问时写清 PHP 版本、运行环境、错误日志和最小复现代码。这样别人能更快定位问题。'),
    array(1, '新版分类已经整理成 PHP 技术方向', '更新', '版块现在围绕 PHP 技术讨论、程序发布、框架生态、部署运维和安全审计展开。'),
    array(2, 'PHP 8.4 属性钩子适合在哪些场景使用？', 'PHP 8.x', '最近在整理实体对象，属性钩子看起来能减少 getter/setter，但也担心隐藏逻辑过多。大家在生产里会怎么取舍？'),
    array(2, 'readonly class 和 DTO 的边界怎么划？', '最佳实践', '接口入参、配置对象和查询结果都想做成不可变对象，但遇到表单回填时会有点不方便。'),
    array(2, '线上偶发 Cannot modify header information 怎么查？', '错误排查', '错误不是每次出现，怀疑某个 include 文件提前输出了空白。有没有比较稳的排查流程？'),
    array(2, 'match 表达式替代 switch 后可读性提升明显吗？', '语法特性', '项目里很多状态流转还在用 switch，想逐步替换成 match，但担心团队维护成本。'),
    array(3, '发布一个轻量级 PHP 文件上传组件', '开源项目', '组件只依赖原生 PHP，支持后缀白名单、大小限制、MIME 检查和随机文件名。欢迎试用和提 issue。'),
    array(3, '我的论坛插件：Markdown 粘贴图片上传', '插件扩展', '插件监听编辑器粘贴事件，把图片上传后自动插入 markdown 链接，目前支持本地和 S3/R2。'),
    array(3, '开源一个 Composer 包：数组路径读取工具', '开源项目', '支持 data_get 风格点语法、默认值和通配符，适合配置读取和轻量数据转换。'),
    array(3, '程序发布帖应该包含哪些信息？', '版本更新', '建议包含运行环境、安装步骤、截图、演示地址、许可证、更新日志和联系方式。'),
    array(4, 'Laravel 队列失败任务堆积怎么定位？', 'Laravel', 'failed_jobs 一直增长，worker 没有明显报错。想确认是超时、内存还是第三方接口慢导致。'),
    array(4, 'Symfony Messenger 和 Laravel Queue 的体验差异', 'Symfony', '两个都用过一段时间，想听听大家在中大型项目里的选型经验。'),
    array(4, 'ThinkPHP 老项目升级 PHP 8.2 要注意什么？', 'ThinkPHP', '主要担心动态属性、字符串到数字比较、扩展兼容和第三方包不维护。'),
    array(4, 'Hyperf 适合做后台管理系统吗？', 'Hyperf', '长连接和协程很诱人，但普通后台 CRUD 是否会增加部署复杂度？'),
    array(5, 'Composer install 和 update 的团队规范', 'Composer', '线上只跑 install，锁文件必须进仓库。update 应该在开发分支做并 review composer.lock。'),
    array(5, '私有 Packagist 还是 Satis？', 'Packagist', '团队内部有十几个私有包，想找一个维护成本低的方案。'),
    array(5, '依赖升级前怎么评估破坏性变更？', '依赖升级', '除了看 CHANGELOG，还会跑哪些自动化检查？有没有好用的 composer 插件？'),
    array(5, '如何设计一个容易维护的 PHP SDK？', '包开发', '希望 SDK 既能支持 PSR-18，又不要让使用方安装一堆适配器。'),
    array(6, 'Opcache 参数怎么设置比较稳？', 'Opcache', '生产环境 validate_timestamps 是否关闭？部署时怎么触发 opcache reset 更优雅？'),
    array(6, 'Swoole 常驻内存项目如何避免状态污染？', 'Swoole', '遇到过单例对象缓存请求级数据的问题，想整理一套编码约束。'),
    array(6, 'Redis 连接池满了会有什么表现？', 'Redis', '偶发接口超时，日志里没有明显错误，只看到 Redis 延迟上升。'),
    array(6, '用 Blackfire 还是 Xdebug profile 做性能分析？', '性能调优', '本地能用 Xdebug，线上想找侵入更低的方案。'),
    array(7, 'MySQL 复合索引顺序怎么判断？', '索引优化', 'where 有 user_id、status、created_at，order by created_at。索引顺序怎么更合理？'),
    array(7, 'PostgreSQL JSONB 在 PHP 项目里适合放哪些数据？', 'PostgreSQL', '配置快照、审计日志和第三方回调原文是否适合直接放 JSONB？'),
    array(7, 'Redis 缓存穿透和击穿的简单处理方案', 'Redis', '空值短缓存、热点互斥锁、预热和随机过期时间，大家还会加什么？'),
    array(7, '异步队列失败重试应该怎么设计？', '队列', '重试次数、退避时间、死信队列和人工补偿后台都需要考虑。'),
    array(8, 'Nginx try_files 配错会导致 PHP 源码泄露吗？', 'Nginx', '如果把无扩展 URL 直接 try 到 $uri.php，但没有进入 PHP-FPM，就有源码泄露风险。'),
    array(8, 'PHP-FPM pm 动态模式参数怎么估算？', 'PHP-FPM', '服务器 8G 内存，单进程 60-90M，应该怎么设置 max_children 和 spare servers？'),
    array(8, 'Docker 部署 PHP 项目是否应该把 vendor 打进镜像？', 'Docker', '我倾向 CI 阶段 composer install，然后 vendor 和代码一起进镜像，线上不跑 composer。'),
    array(8, 'GitHub Actions 自动部署到 VPS 的安全做法', 'CI/CD', 'deploy key、known_hosts、rsync、回滚目录和健康检查都需要怎么安排？'),
    array(8, 'Cloudflare 橙云下源站证书怎么配？', 'HTTPS', 'Full strict + Let’s Encrypt DNS challenge 是不是最省心？续期失败如何监控？'),
    array(9, '登录表单 CSRF token 是否每次刷新都要变？', 'CSRF', '项目里 token 存 session，多个标签页打开时偶尔会冲突。'),
    array(9, '富文本内容如何防 XSS？', 'XSS', '只允许白名单标签，还是前端 markdown 转义后端再净化？'),
    array(9, 'SQL 注入防护只靠 mysqli_real_escape_string 够吗？', 'SQL 注入', '老项目大量拼 SQL，逐步迁移到预处理有什么策略？'),
    array(9, 'Composer audit 在 CI 里应该阻断发布吗？', '依赖安全', '如果是 dev 依赖有漏洞，是否也应该阻断主分支合并？'),
    array(10, '求助：上传大文件时 PHP-FPM 返回 502', '报错求助', 'Nginx client_max_body_size、post_max_size、upload_max_filesize 都调了，还是偶发 502。'),
    array(10, '这段递归数组合并代码有没有更清晰写法？', '代码审查', '现在函数能跑，但读起来很绕，想改成更容易维护的版本。'),
    array(10, '本地正常线上 session 丢失怎么查？', '环境配置', '怀疑是 cookie secure、SameSite 或反代 HTTPS 头没传对。'),
    array(10, '偶发数据库连接 too many connections', '疑难杂症', '慢查询不多，但连接数会突然打满，想确认是不是 PHP 进程数配置过高。'),
);

$reply_bank = array(
    '建议先贴一下 PHP 版本、扩展列表和最小复现代码，这样更容易判断。',
    '我在生产里遇到过类似问题，最后发现是配置缓存没有刷新。',
    '这个方向可行，但最好补一组自动化测试，尤其是边界输入。',
    '如果是线上问题，建议同时看 Nginx access/error log 和 PHP-FPM slowlog。',
    '可以把这个拆成两个步骤：先保证安全，再考虑性能。',
    'Composer 的锁文件一定要进仓库，否则环境差异会很难排查。',
    '如果有截图或完整报错堆栈，判断会更准确。',
    '我更倾向于显式配置，不要把太多行为藏在魔法方法里。',
    '这个场景用队列会更稳，失败重试和补偿也容易做。',
    '可以先在测试环境压测一下，再决定是否推广到全站。',
);

$comment_bank = array(
    '这个点我也踩过。',
    'mark，等楼主后续。',
    '建议补充环境信息。',
    '这个解释清楚。',
    '可以加到文档里。',
    '我倾向第二种方案。',
);

$created_threads = 0;
$created_posts = 0;
$created_comments = 0;
foreach ($topics as $i => $topic) {
    $forum_index = intval($topic[0]) - 1;
    $forum_id = $forums[$forum_index];
    $category = $topic[2];
    $title = $topic[1];
    $title_sql = seed_phpdo_sql($title);
    $rs = mysqli_query(db(), "SELECT id FROM qf_threads WHERE title='{$title_sql}' LIMIT 1");
    $existing = $rs ? mysqli_fetch_assoc($rs) : null;
    if ($existing) {
        continue;
    }
    $user_id = seed_phpdo_pick($users, $i * 3 + 1);
    $content = implode("\n\n", array(
        $topic[3],
        '我想听听大家在真实项目里的做法，尤其是踩坑、权衡和上线后的维护经验。',
        '环境可以按 PHP 8.3/8.4、Nginx、PHP-FPM、MariaDB/MySQL、Redis 这一类常见组合来讨论。'
    ));
    $category_sql = seed_phpdo_sql($category);
    $content_sql = seed_phpdo_sql($content);
    $views = 80 + mt_rand(0, 4200);
    $is_good = ($i % 7 === 0 || $forum_index === 2) ? 1 : 0;
    $is_top = ($i === 0 || $i === 1) ? 1 : 0;
    $days = mt_rand(0, 35);
    $ip = '172.20.' . intval($i % 255) . '.' . intval(40 + ($i % 180));
    mysqli_query(db(), "INSERT INTO qf_threads (forum_id,user_id,topic_category,title,content,views,replies,is_top,is_good,is_deleted,ip,created_at,updated_at) VALUES ({$forum_id},{$user_id},'{$category_sql}','{$title_sql}','{$content_sql}',{$views},0,{$is_top},{$is_good},0,'{$ip}',DATE_SUB(NOW(), INTERVAL {$days} DAY),DATE_SUB(NOW(), INTERVAL " . mt_rand(0, min(5, $days)) . " DAY))");
    $thread_id = intval(mysqli_insert_id(db()));
    $created_threads++;
    $reply_total = 3 + ($i % 5);
    $last_post_id = 0;
    for ($r = 1; $r <= $reply_total; $r++) {
        $reply_user = seed_phpdo_pick($users, $i + $r * 4);
        $reply = seed_phpdo_pick($reply_bank, $i + $r) . "\n\n" . '补充：这个回复用于模拟真实技术讨论，让列表、详情和楼中楼评论都有数据。';
        $reply_sql = seed_phpdo_sql($reply);
        mysqli_query(db(), "INSERT INTO qf_posts (thread_id,user_id,content,is_deleted,ip,created_at) VALUES ({$thread_id},{$reply_user},'{$reply_sql}',0,'{$ip}',DATE_SUB(NOW(), INTERVAL " . mt_rand(0, max(1, $days)) . " DAY))");
        $post_id = intval(mysqli_insert_id(db()));
        $last_post_id = $post_id;
        $created_posts++;
        if ($r <= 2) {
            $comment_count = 1 + (($i + $r) % 2);
            for ($c = 1; $c <= $comment_count; $c++) {
                $comment_user = seed_phpdo_pick($users, $i + $r + $c * 7);
                $comment = seed_phpdo_pick($comment_bank, $i + $r + $c);
                $comment_sql = seed_phpdo_sql($comment);
                mysqli_query(db(), "INSERT INTO qf_post_comments (thread_id,post_id,user_id,content,ip,is_deleted,created_at) VALUES ({$thread_id},{$post_id},{$comment_user},'{$comment_sql}','{$ip}',0,DATE_SUB(NOW(), INTERVAL " . mt_rand(0, max(1, $days)) . " DAY))");
                $created_comments++;
            }
        }
    }
    mysqli_query(db(), "UPDATE qf_threads SET replies={$reply_total}, updated_at=(SELECT created_at FROM qf_posts WHERE id={$last_post_id}) WHERE id={$thread_id}");
}

$user_id_list = implode(',', array_map('intval', $users));
if ($user_id_list !== '') {
    mysqli_query(db(), "UPDATE qf_users u SET reply_count=(SELECT COUNT(*) FROM qf_posts p WHERE p.user_id=u.id AND p.is_deleted=0) WHERE u.id IN ({$user_id_list})");
}
mysqli_query(db(), "REPLACE INTO qf_navs (id,title,url,display_order,is_enabled,created_at) VALUES
    (1,'技术讨论','forum.php?id=" . intval($forums[1]) . "',10,1,NOW()),
    (2,'程序发布','forum.php?id=" . intval($forums[2]) . "',20,1,NOW()),
    (3,'代码求助','forum.php?id=" . intval($forums[9]) . "',30,1,NOW())");

$summary = array();
foreach (array('qf_forums', 'qf_users', 'qf_threads', 'qf_posts', 'qf_post_comments') as $table) {
    $rs = mysqli_query(db(), "SELECT COUNT(*) AS c FROM {$table}");
    $row = $rs ? mysqli_fetch_assoc($rs) : array('c' => 0);
    $summary[$table] = intval($row['c']);
}

echo "php.do seed complete\n";
echo "created_threads={$created_threads} created_posts={$created_posts} created_comments={$created_comments}\n";
echo "forums={$summary['qf_forums']} users={$summary['qf_users']} threads={$summary['qf_threads']} posts={$summary['qf_posts']} comments={$summary['qf_post_comments']}\n";

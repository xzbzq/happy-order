<?php
/**
 * 幸福小厨 🏠 安装向导
 * 前端可视化安装，自动写入配置 + 初始化数据库
 */

// ---- 如果已安装，直接跳转到首页 ----
$configPath = __DIR__ . '/config/database.php';
if (file_exists($configPath) && filesize($configPath) > 100) {
    header('Location: index.php');
    exit;
}

// ---- AJAX 测试连接 ----
if (($_GET['action'] ?? '') === 'test') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $host = trim($input['host'] ?? '127.0.0.1');
        $port = trim($input['port'] ?? '3306');
        $user = trim($input['user'] ?? 'root');
        $pass = $input['pass'] ?? '';

        $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port);
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        echo json_encode(['success' => true, 'message' => '连接成功']);
        exit;
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ---- AJAX 安装处理 ----
if (($_GET['action'] ?? '') === 'install') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        $host = trim($input['host'] ?? '127.0.0.1');
        $port = trim($input['port'] ?? '3306');
        $name = trim($input['name'] ?? 'happy_kitchen');
        $user = trim($input['user'] ?? 'root');
        $pass = $input['pass'] ?? '';

        if (!$host || !$name || !$user) {
            throw new Exception('请填写数据库主机、数据库名和用户名');
        }

        // 1. 测试连接（先不指定数据库）
        $dsnNoDB = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port);
        $pdo = new PDO($dsnNoDB, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);

        // 2. 创建数据库
        $pdo->exec(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $name
        ));

        // 3. 连到新数据库
        $dsnDB = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
        $pdo = new PDO($dsnDB, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        // 4. 执行 schema.sql（跳过 CREATE DATABASE / USE 等语句）
        $schemaFile = __DIR__ . '/sql/schema.sql';
        if (!file_exists($schemaFile)) {
            throw new Exception('找不到 schema.sql 文件');
        }

        $sql = file_get_contents($schemaFile);
        // 按分号分割 SQL 语句
        $statements = explode(';', $sql);
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if (!$stmt) continue;
            $upper = strtoupper($stmt);
            // 跳过 CREATE DATABASE 和 USE 语句
            if (strpos($upper, 'CREATE DATABASE') !== false) continue;
            if (strpos($upper, 'USE ') !== false) continue;
            $pdo->exec($stmt);
        }

        // 5. 写入 config/database.php（需转义特殊字符）
        $safeHost = addslashes($host);
        $safePort = addslashes($port);
        $safeName = addslashes($name);
        $safeUser = addslashes($user);
        $safePass = addslashes($pass);
        $configContent = <<<PHP
<?php
/**
 * 幸福小厨 - 数据库配置
 * 由安装向导自动生成
 */

define('DB_HOST', '{$safeHost}');
define('DB_PORT', '{$safePort}');
define('DB_NAME', '{$safeName}');
define('DB_USER', '{$safeUser}');
define('DB_PASS', '{$safePass}');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO
{
    static \$pdo = null;
    if (\$pdo === null) {
        \$dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return \$pdo;
}

function jsonExit(array \$data, int \$code = 200): void
{
    http_response_code(\$code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(\$data, JSON_UNESCAPED_UNICODE);
    exit;
}

PHP;

        if (file_put_contents($configPath, $configContent) === false) {
            throw new Exception('无法写入配置文件，请检查 config/ 目录权限');
        }

        echo json_encode([
            'success' => true,
            'message' => '🎉 安装成功！欢迎使用幸福小厨！',
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (PDOException $e) {
        $msg = '数据库连接失败：' . $e->getMessage();
        if (strpos($msg, 'Access denied') !== false) {
            $msg = '数据库用户名或密码错误，请检查后重试 🙏';
        } elseif (strpos($msg, 'could not find driver') !== false) {
            $msg = 'PHP 缺少 PDO MySQL 扩展，请安装 php-mysql 🙏';
        } elseif (strpos($msg, 'Connection refused') !== false) {
            $msg = '数据库连接被拒绝，请检查主机和端口是否正确 🙏';
        }
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ---- 系统环境检查 ----
$configDir = __DIR__ . '/config';
if (!is_dir($configDir)) {
    @mkdir($configDir, 0755, true);
}
$env = [];
$env['php_version'] = PHP_VERSION;
$env['pdo_mysql'] = extension_loaded('pdo_mysql');
$env['gd'] = extension_loaded('gd');
$env['config_writable'] = is_writable($configDir);
$env['php_ok'] = version_compare(PHP_VERSION, '8.0', '>=');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🏠 安装向导 · 幸福小厨</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js">
    </script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js">
    </script>
    <style>
        /* ---- 安装向导专用样式 ---- */
        .install-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #FFF8F0 0%, #FFE8D6 50%, #FFD6B0 100%);
            padding: 20px;
        }
        .install-container {
            width: 100%;
            max-width: 640px;
        }
        /* 步骤指示器 */
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 0;
            margin-bottom: 32px;
            position: relative;
        }
        .step-dot {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            flex: 1;
            position: relative;
            z-index: 2;
        }
        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 700;
            background: var(--color-bg-card);
            border: 3px solid var(--color-border);
            color: var(--color-text-light);
            transition: all 0.4s;
        }
        .step-dot.active .step-circle {
            background: var(--color-primary);
            border-color: var(--color-primary);
            color: #fff;
            box-shadow: 0 4px 15px rgba(232, 131, 58, 0.35);
        }
        .step-dot.done .step-circle {
            background: var(--color-success);
            border-color: var(--color-success);
            color: #fff;
        }
        .step-label {
            font-size: 0.75rem;
            color: var(--color-text-light);
        }
        .step-dot.active .step-label { color: var(--color-primary); font-weight: 600; }
        .step-dot.done .step-label { color: var(--color-success); font-weight: 600; }
        .step-line {
            position: absolute;
            top: 20px;
            left: 10%;
            right: 10%;
            height: 3px;
            background: var(--color-border);
            z-index: 1;
        }
        .step-line-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--color-success), var(--color-primary));
            transition: width 0.5s;
            width: 0%;
        }
        /* 安装卡片 */
        .install-card {
            background: var(--color-bg-card);
            border-radius: var(--radius);
            padding: 40px;
            box-shadow: 0 10px 40px rgba(232, 131, 58, 0.15);
            border: 1px solid var(--color-border);
            position: relative;
            overflow: hidden;
        }
        .install-step {
            display: none;
        }
        .install-step.active {
            display: block;
        }
        /* Step 1 欢迎 */
        .welcome-icon {
            font-size: 4rem;
            text-align: center;
            margin-bottom: 16px;
        }
        .welcome-title {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 8px;
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .welcome-subtitle {
            text-align: center;
            color: var(--color-text-light);
            margin-bottom: 28px;
            line-height: 1.6;
        }
        .env-checks {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 28px;
        }
        .env-check {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: var(--radius-sm);
            background: var(--color-bg);
            font-size: 0.9rem;
        }
        .env-check.ok { background: #f0faf0; }
        .env-check.fail { background: #fef0f0; }
        .env-icon { font-size: 1.1rem; }
        /* Step 2 表单 */
        .db-form { }
        .form-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-align: center;
        }
        .form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 24px;
        }
        .btn-test {
            background: var(--color-accent);
            color: #fff;
        }
        .btn-test:hover { opacity: 0.9; }
        .btn-test:disabled { opacity: 0.5; cursor: not-allowed; }
        .test-result {
            text-align: center;
            padding: 10px;
            border-radius: var(--radius-sm);
            margin-top: 12px;
            font-size: 0.9rem;
        }
        .test-result.success { background: #f0faf0; color: #2e7d32; }
        .test-result.error { background: #fef0f0; color: #c62828; }
        /* Step 3 安装进度 */
        .progress-steps {
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding: 10px 0;
        }
        .progress-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 18px;
            border-radius: var(--radius-sm);
            background: var(--color-bg);
            transition: all 0.3s;
        }
        .progress-item.active {
            background: #FFF8E1;
            border: 1px solid var(--color-warning);
        }
        .progress-item.done {
            background: #f0faf0;
        }
        .progress-item.fail {
            background: #fef0f0;
        }
        .progress-status {
            font-size: 1.2rem;
            width: 28px;
            text-align: center;
        }
        .progress-text { flex: 1; font-size: 0.95rem; }
        .progress-spinner {
            width: 20px;
            height: 20px;
            border: 3px solid var(--color-border);
            border-top-color: var(--color-primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        /* Step 4 完成 */
        .success-icon {
            font-size: 5rem;
            text-align: center;
            margin-bottom: 16px;
        }
        .success-title {
            text-align: center;
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--color-success);
        }
        .success-desc {
            text-align: center;
            color: var(--color-text-light);
            margin-bottom: 28px;
            line-height: 1.6;
        }
        .success-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .error-msg {
            text-align: center;
            color: var(--color-danger);
            margin: 12px 0;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        @media (max-width: 600px) {
            .install-card { padding: 24px 20px; }
            .form-row-2 { grid-template-columns: 1fr; }
            .welcome-title { font-size: 1.4rem; }
        }
    </style>
</head>
<body>
<div id="install-app" class="install-page">
    <div class="install-container" ref="container">
        <!-- 步骤指示器 -->
        <div class="step-indicator" ref="stepIndicator">
            <div class="step-line"><div class="step-line-fill" ref="stepLineFill"></div></div>
            <div v-for="(s, i) in steps" :key="i"
                 :class="['step-dot', { active: step === i, done: step > i }]">
                <div class="step-circle">{{ step > i ? '✓' : i + 1 }}</div>
                <div class="step-label">{{ s }}</div>
            </div>
        </div>

        <!-- 安装卡片 -->
        <div class="install-card" ref="installCard">
            <!-- ===== Step 1: 欢迎 ===== -->
            <div :class="['install-step', { active: step === 0 }]" ref="step0">
                <div class="welcome-icon">🏠</div>
                <div class="welcome-title">欢迎来到幸福小厨</div>
                <div class="welcome-subtitle">
                    一个暖心可爱的家庭点单系统 ❤️<br>
                    在开始之前，先检查一下环境吧~
                </div>
                <div class="env-checks">
                    <div class="env-check" :class="env.php_ok ? 'ok' : 'fail'">
                        <span class="env-icon">{{ env.php_ok ? '✅' : '❌' }}</span>
                        <span>PHP 版本：{{ env.php_version }}（需要 ≥ 8.0）</span>
                    </div>
                    <div class="env-check" :class="env.pdo_mysql ? 'ok' : 'fail'">
                        <span class="env-icon">{{ env.pdo_mysql ? '✅' : '❌' }}</span>
                        <span>PDO MySQL 扩展：{{ env.pdo_mysql ? '已启用' : '未安装' }}</span>
                    </div>
                    <div class="env-check" :class="env.config_writable ? 'ok' : 'fail'">
                        <span class="env-icon">{{ env.config_writable ? '✅' : '❌' }}</span>
                        <span>config/ 目录可写：{{ env.config_writable ? '可写' : '不可写，请设置权限' }}</span>
                    </div>
                </div>
                <div style="text-align:center">
                    <button class="btn btn-primary btn-lg" @click="goStep(1)"
                            :disabled="!canInstall">
                        🚀 开始安装！
                    </button>
                    <p v-if="!canInstall" style="margin-top:10px;color:var(--color-danger);font-size:0.85rem">
                        请先解决以上环境问题后再安装 🙏
                    </p>
                </div>
            </div>

            <!-- ===== Step 2: 数据库配置 ===== -->
            <div :class="['install-step', { active: step === 1 }]" ref="step1">
                <div class="form-title">🔌 配置数据库连接</div>
                <div class="db-form">
                    <div class="form-group">
                        <label>数据库主机</label>
                        <input v-model="form.host" placeholder="127.0.0.1" />
                    </div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label>端口</label>
                            <input v-model="form.port" placeholder="3306" />
                        </div>
                        <div class="form-group">
                            <label>数据库名</label>
                            <input v-model="form.name" placeholder="happy_kitchen" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label>数据库用户名</label>
                        <input v-model="form.user" placeholder="root" />
                    </div>
                    <div class="form-group">
                        <label>数据库密码</label>
                        <input type="password" v-model="form.pass" placeholder="（留空表示无密码）" />
                    </div>
                    <div v-if="testResult" :class="['test-result', testResult.type]">
                        {{ testResult.msg }}
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-outline" @click="goStep(0)">← 上一步</button>
                        <button class="btn btn-test" @click="testConnection" :disabled="testing">
                            {{ testing ? '⏳ 测试中…' : '🔌 测试连接' }}
                        </button>
                        <button class="btn btn-primary" @click="startInstall"
                                :disabled="!connectionOk || installing">
                            ⚙️ 开始安装
                        </button>
                    </div>
                </div>
            </div>

            <!-- ===== Step 3: 安装中 ===== -->
            <div :class="['install-step', { active: step === 2 }]" ref="step2">
                <div class="form-title">⚙️ 正在安装…</div>
                <div class="progress-steps">
                    <div v-for="(p, i) in progressItems" :key="i"
                         :class="['progress-item', p.status]">
                        <span class="progress-status">
                            <span v-if="p.status === 'done'">✅</span>
                            <span v-else-if="p.status === 'fail'">❌</span>
                            <span v-else-if="p.status === 'active'">
                                <span class="progress-spinner"></span>
                            </span>
                            <span v-else>⏳</span>
                        </span>
                        <span class="progress-text">{{ p.label }}</span>
                    </div>
                </div>
                <div v-if="installError" class="error-msg">{{ installError }}</div>
                <div v-if="installError" style="text-align:center;margin-top:16px">
                    <button class="btn btn-outline" @click="step = 1; installError = ''">← 返回修改</button>
                </div>
            </div>

            <!-- ===== Step 4: 完成 ===== -->
            <div :class="['install-step', { active: step === 3 }]" ref="step3">
                <div class="success-icon">🎉</div>
                <div class="success-title">安装成功！</div>
                <div class="success-desc">
                    幸福小厨已经准备好了 🏠<br>
                    快进去为家人点一顿暖心美食吧！
                </div>
                <div class="success-actions">
                    <a href="index.php" class="btn btn-primary btn-lg">🏠 进入幸福小厨</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const { createApp, ref, reactive, computed, onMounted, nextTick } = Vue;

createApp({
    setup() {
        const steps = ['欢迎', '数据库', '安装', '完成'];
        const step = ref(0);
        const form = reactive({
            host: '127.0.0.1',
            port: '3306',
            name: 'happy_kitchen',
            user: 'root',
            pass: '',
        });
        const env = reactive(<?= json_encode($env) ?>);
        const canInstall = computed(() => env.php_ok && env.pdo_mysql && env.config_writable);

        const testing = ref(false);
        const testResult = ref(null);
        const connectionOk = ref(false);
        const installing = ref(false);
        const installError = ref('');
        const progressItems = ref([
            { label: '📝 写入配置文件', status: 'pending' },
            { label: '🔌 连接数据库', status: 'pending' },
            { label: '📦 创建数据表', status: 'pending' },
            { label: '🌱 导入初始数据', status: 'pending' },
        ]);

        /* ---- 步骤过渡动画 ---- */
        function goStep(target) {
            const old = step.value;
            step.value = target;
            nextTick(() => {
                const card = document.querySelector('.install-card');
                if (card) {
                    gsap.fromTo(card,
                        { scale: 0.97, opacity: 0.6 },
                        { scale: 1, opacity: 1, duration: 0.4, ease: 'power2.out' }
                    );
                }
                updateLine();
            });
        }

        function updateLine() {
            const pct = (step.value / (steps.length - 1)) * 100;
            const fill = document.querySelector('.step-line-fill');
            if (fill) {
                gsap.to(fill, { width: pct + '%', duration: 0.5, ease: 'power2.out' });
            }
        }

        /* ---- 测试连接 ---- */
        async function testConnection() {
            testing.value = true;
            testResult.value = null;
            connectionOk.value = false;
            try {
                const res = await fetch('install.php?action=test', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ...form }),
                });
                const data = await res.json();
                if (data.success) {
                    testResult.value = { type: 'success', msg: '✅ 连接成功！数据库可用' };
                    connectionOk.value = true;
                } else {
                    testResult.value = { type: 'error', msg: '❌ ' + (data.error || '连接失败') };
                }
            } catch (e) {
                testResult.value = { type: 'error', msg: '❌ 请求失败：' + e.message };
            } finally {
                testing.value = false;
            }
        }

        /* ---- 安装 ---- */
        async function startInstall() {
            installing.value = true;
            installError.value = '';
            step.value = 2;
            progressItems.value.forEach(p => p.status = 'pending');
            nextTick(() => updateLine());

            // 逐步骤推进
            const sleep = ms => new Promise(r => setTimeout(r, ms));

            // Step A: 写入配置
            progressItems.value[0].status = 'active';
            await sleep(400);
            progressItems.value[0].status = 'done';
            await sleep(200);

            // Step B-D: AJAX 安装
            progressItems.value[1].status = 'active';
            await sleep(200);

            try {
                const res = await fetch('install.php?action=install', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ...form }),
                });
                const data = await res.json();

                if (!data.success) {
                    // 失败
                    progressItems.value[1].status = 'fail';
                    installError.value = data.error || '安装失败';
                    return;
                }

                progressItems.value[1].status = 'done';
                progressItems.value[2].status = 'active';
                await sleep(300);
                progressItems.value[2].status = 'done';
                progressItems.value[3].status = 'active';
                await sleep(300);
                progressItems.value[3].status = 'done';
                await sleep(400);

                step.value = 3;
                nextTick(() => {
                    updateLine();
                    const card = document.querySelector('.install-card');
                    if (card) {
                        gsap.from(card, { scale: 0.95, opacity: 0, duration: 0.5, ease: 'back.out(1.5)' });
                    }
                });
            } catch (e) {
                progressItems.value[1].status = 'fail';
                installError.value = '网络错误：' + e.message;
            } finally {
                installing.value = false;
            }
        }

        onMounted(() => {
            step.value = 0;
            nextTick(() => {
                updateLine();
                const card = document.querySelector('.install-card');
                if (card) {
                    gsap.from(card, { y: 30, opacity: 0, duration: 0.6, ease: 'power2.out' });
                }
                const dots = document.querySelectorAll('.step-dot');
                if (dots.length) {
                    gsap.from(dots, {
                        scale: 0, duration: 0.4,
                        stagger: 0.1, ease: 'back.out(1.5)',
                        delay: 0.3,
                    });
                }
            });
        });

        return { steps, step, form, env, canInstall,
                 testing, testResult, connectionOk,
                 installing, installError, progressItems,
                 goStep, testConnection, startInstall };
    }
}).mount('#install-app');
</script>
</body>
</html>

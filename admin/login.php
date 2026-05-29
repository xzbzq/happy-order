<?php
/**
 * 幸福小厨 🏠 后台管理 - 登录页
 * 首次访问自动进入管理员创建流程
 */
require_once __DIR__ . '/../config/database.php';

session_start();
header('Content-Type: text/html; charset=utf-8');

// 已登录则直接跳转
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

// 检测是否为首次运行（无管理员密码文件）
$firstRun = !file_exists(__DIR__ . '/../config/admin.php');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚙️ 幸福小厨 · 后台管理</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js">
    </script>
    <style>
        /* ---- 后台管理专属样式 ---- */
        .admin-login-body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fff5f5 0%, #fff8e1 50%, #fff3e0 100%);
            padding: 20px;
            overflow: hidden;
            position: relative;
        }
        .admin-login-body::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,167,38,0.08), transparent);
            top: -80px;
            right: -80px;
            pointer-events: none;
        }
        .admin-login-body::after {
            content: '';
            position: absolute;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,107,107,0.06), transparent);
            bottom: -60px;
            left: -60px;
            pointer-events: none;
        }
        .login-card {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 48px 40px;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.08), 0 1px 3px rgba(0,0,0,0.04);
            border: 1px solid rgba(255,255,255,0.6);
            position: relative;
            z-index: 1;
        }
        .login-card .login-logo {
            text-align: center;
            margin-bottom: 8px;
        }
        .login-card .login-logo span {
            font-size: 48px;
            display: inline-block;
        }
        .login-card h1 {
            text-align: center;
            font-size: 22px;
            color: #5d4037;
            margin: 0 0 4px 0;
        }
        .login-card .login-sub {
            text-align: center;
            color: #999;
            font-size: 14px;
            margin-bottom: 32px;
        }
        .login-card .form-group {
            margin-bottom: 20px;
        }
        .login-card .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #6d4c41;
            margin-bottom: 6px;
        }
        .login-card .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #f0e6e0;
            border-radius: 12px;
            font-size: 15px;
            transition: border-color 0.3s, box-shadow 0.3s;
            background: rgba(255,255,255,0.7);
            box-sizing: border-box;
            outline: none;
        }
        .login-card .form-group input:focus {
            border-color: #ffa726;
            box-shadow: 0 0 0 4px rgba(255,167,38,0.12);
        }
        .login-card .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #ff8a65, #ffa726);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 8px;
        }
        .login-card .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(255,167,38,0.30);
        }
        .login-card .btn-login:active {
            transform: translateY(0);
        }
        .login-card .login-error {
            background: rgba(255,107,107,0.10);
            color: #e53935;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
            margin-bottom: 16px;
            display: none;
        }
        .login-card .login-error.visible {
            display: block;
        }
        .login-card .login-success {
            text-align: center;
            color: #43a047;
            font-size: 15px;
            padding: 10px;
            background: rgba(76,175,80,0.08);
            border-radius: 10px;
            display: none;
        }
        .login-card .login-success.visible {
            display: block;
        }
        .login-card .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #999;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s;
        }
        .login-card .back-link:hover {
            color: #ff8a65;
        }
        .login-card .hint-text {
            text-align: center;
            color: #bbb;
            font-size: 12px;
            margin-top: 16px;
        }
        .login-card .input-wrapper {
            position: relative;
        }
        .login-card .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #999;
            padding: 4px;
        }
        .login-card .toggle-pw:hover {
            color: #ff8a65;
        }
        /* 浮动装饰 */
        .login-deco {
            position: fixed;
            font-size: 20px;
            opacity: 0;
            pointer-events: none;
            z-index: 0;
        }
        @media (max-width: 480px) {
            .login-card { padding: 32px 24px; }
        }
    </style>
</head>
<body class="admin-login-body">
    <!-- 浮动装饰 -->
    <div id="loginDecos"></div>

    <div class="login-card" id="loginCard">
        <div class="login-logo">
            <span id="logoIcon">⚙️</span>
        </div>
        <h1 id="titleText"><?= $firstRun ? '🎉 欢迎使用幸福小厨' : '🔐 管理员登录' ?></h1>
        <p class="login-sub" id="subText">
            <?= $firstRun ? '首次使用，请设置管理员密码' : '请输入密码进入后台管理' ?>
        </p>

        <div class="login-error" id="loginError"></div>
        <div class="login-success" id="loginSuccess"></div>

        <form id="loginForm" onsubmit="return handleLogin(event)">
            <div class="form-group">
                <label for="password"><?= $firstRun ? '📝 设置管理员密码' : '🔑 管理员密码' ?></label>
                <div class="input-wrapper">
                    <input type="password" id="password" placeholder="<?= $firstRun ? '至少 4 位密码…' : '输入密码…' ?>"
                           minlength="4" required autocomplete="off" />
                    <button type="button" class="toggle-pw" onclick="togglePassword()" tabindex="-1">👁️</button>
                </div>
            </div>
            <?php if ($firstRun): ?>
            <div class="form-group">
                <label for="confirm">✅ 确认密码</label>
                <div class="input-wrapper">
                    <input type="password" id="confirm" placeholder="再次输入密码…" minlength="4" required autocomplete="off" />
                </div>
            </div>
            <?php endif; ?>
            <button type="submit" class="btn-login" id="submitBtn">
                <?= $firstRun ? '🎉 创建管理员' : '🚀 登录后台' ?>
            </button>
        </form>

        <a class="back-link" href="../index.php">🏠 返回幸福首页</a>
        <p class="hint-text">❤️ 幸福小厨 · 家庭点单系统</p>
    </div>

    <script>
        // ====== GSAP 入场动画 ======
        gsap.from('#loginCard', {
            opacity: 0,
            y: 40,
            scale: 0.96,
            duration: 0.7,
            ease: 'back.out(1.4)',
        });
        gsap.from('#loginCard .form-group, #loginCard .btn-login, #loginCard .back-link', {
            opacity: 0,
            y: 20,
            duration: 0.4,
            stagger: 0.08,
            ease: 'power2.out',
            delay: 0.3,
        });

        // 浮动装饰
        (function createLoginDecos() {
            const symbols = ['🍳', '🥟', '🍜', '❤️', '⭐', '🌙', '☀️', '🧁', '🍰', '🥘'];
            const container = document.getElementById('loginDecos');
            for (let i = 0; i < 8; i++) {
                const el = document.createElement('span');
                el.className = 'login-deco';
                el.textContent = symbols[i % symbols.length];
                el.style.left = (10 + Math.random() * 80) + '%';
                el.style.top = (10 + Math.random() * 80) + '%';
                container.appendChild(el);
                gsap.set(el, { opacity: 0, scale: 0.5 });
                gsap.to(el, {
                    opacity: 0.12,
                    scale: 1,
                    y: -20 + Math.random() * -30,
                    duration: 3 + Math.random() * 2,
                    repeat: -1,
                    yoyo: true,
                    ease: 'sine.inOut',
                    delay: i * 0.5,
                });
            }
        })();

        // ====== 切换密码可见 ======
        function togglePassword() {
            const pw = document.getElementById('password');
            pw.type = pw.type === 'password' ? 'text' : 'password';
        }

        // ====== 登录 / 创建管理员 ======
        const firstRun = <?= json_encode($firstRun) ?>;

        async function handleLogin(e) {
            e.preventDefault();
            const errEl = document.getElementById('loginError');
            const successEl = document.getElementById('loginSuccess');
            const submitBtn = document.getElementById('submitBtn');
            errEl.classList.remove('visible');
            successEl.classList.remove('visible');

            const password = document.getElementById('password').value;
            if (!password || password.length < 4) {
                showError('密码至少需要 4 位');
                return false;
            }

            if (firstRun) {
                const confirm = document.getElementById('confirm').value;
                if (password !== confirm) {
                    showError('两次输入的密码不一致');
                    return false;
                }
            }

            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ 请稍候…';

            try {
                const res = await fetch('../api/admin.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ password }),
                });
                const data = await res.json();

                if (!res.ok) {
                    showError(data.error || '登录失败');
                    submitBtn.disabled = false;
                    submitBtn.textContent = firstRun ? '🎉 创建管理员' : '🚀 登录后台';
                    return false;
                }

                // 成功
                successEl.textContent = data.message || '登录成功 🎉';
                successEl.classList.add('visible');
                submitBtn.textContent = '✅ 登录成功！';

                // 跳转
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 800);
            } catch (e) {
                showError('网络错误，请重试');
                submitBtn.disabled = false;
                submitBtn.textContent = firstRun ? '🎉 创建管理员' : '🚀 登录后台';
            }
            return false;
        }

        function showError(msg) {
            const el = document.getElementById('loginError');
            el.textContent = msg;
            el.classList.add('visible');
            gsap.from(el, { opacity: 0, x: -10, duration: 0.3, ease: 'power2.out' });
        }
    </script>
</body>
</html>

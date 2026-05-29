<?php
/**
 * 幸福小厨 🏠 授权激活页
 * 首次使用或授权失效时显示
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/License.php';

$license = new License();
$info = $license->info();

// 如果已授权，自动跳转
if ($info['licensed']) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔑 幸福小厨 · 授权激活</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js">
    </script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fff5f5 0%, #fff8e1 50%, #fff3e0 100%);
            padding: 20px;
            overflow: hidden;
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,107,107,0.06), transparent);
            top: -100px; right: -100px;
        }
        body::after {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(78,205,196,0.05), transparent);
            bottom: -80px; left: -80px;
        }

        .license-card {
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 44px 40px 36px;
            max-width: 440px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.06), 0 1px 3px rgba(0,0,0,0.03);
            border: 1px solid rgba(255,255,255,0.6);
            position: relative;
            z-index: 1;
            text-align: center;
        }
        .license-card .icon-top {
            font-size: 52px;
            display: block;
            margin-bottom: 8px;
        }
        .license-card h1 {
            font-size: 22px;
            color: #5d4037;
            margin-bottom: 4px;
        }
        .license-card .sub {
            color: #aaa;
            font-size: 14px;
            margin-bottom: 28px;
        }
        .license-card .domain-badge {
            background: linear-gradient(135deg, rgba(255,138,101,0.08), rgba(255,167,38,0.06));
            border: 1px solid rgba(255,167,38,0.12);
            padding: 10px 16px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            color: #ff8a65;
            margin-bottom: 24px;
            word-break: break-all;
        }
        .license-card .domain-badge small {
            display: block;
            font-weight: 400;
            font-size: 12px;
            color: #bbb;
            margin-bottom: 4px;
        }
        .license-card .form-group {
            margin-bottom: 18px;
            text-align: left;
        }
        .license-card .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #6d4c41;
            margin-bottom: 6px;
        }
        .license-card .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #f0e6e0;
            border-radius: 12px;
            font-size: 14px;
            background: rgba(255,255,255,0.7);
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
            box-sizing: border-box;
            text-align: center;
            letter-spacing: 2px;
            font-family: 'Courier New', monospace;
        }
        .license-card .form-group input:focus {
            border-color: #ffa726;
            box-shadow: 0 0 0 4px rgba(255,167,38,0.10);
        }
        .license-card .btn-activate {
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
        }
        .license-card .btn-activate:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(255,167,38,0.25);
        }
        .license-card .btn-activate:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .license-card .error-msg {
            color: #e53935;
            font-size: 14px;
            padding: 10px;
            background: rgba(255,107,107,0.08);
            border-radius: 10px;
            margin-bottom: 16px;
            display: none;
        }
        .license-card .success-msg {
            color: #2e7d32;
            font-size: 14px;
            padding: 10px;
            background: rgba(76,175,80,0.08);
            border-radius: 10px;
            margin-bottom: 16px;
            display: none;
        }
        .license-card .hint {
            color: #ccc;
            font-size: 12px;
            margin-top: 18px;
        }
        /* 浮动装饰 */
        .license-emoji {
            position: fixed;
            font-size: 20px;
            pointer-events: none;
            z-index: 0;
            opacity: 0;
        }
    </style>
</head>
<body>
    <!-- 浮动装饰 -->
    <div id="decos"></div>

    <div class="license-card" id="card">
        <span class="icon-top">🔑</span>
        <h1>授权激活</h1>
        <p class="sub">输入授权码，开启幸福小厨之旅</p>

        <div class="domain-badge">
            <small>🌐 当前域名</small>
            <?= htmlspecialchars($info['domain']) ?>
        </div>

        <div class="error-msg" id="errorMsg"></div>
        <div class="success-msg" id="successMsg"></div>

        <form id="licenseForm" onsubmit="return handleActivate(event)">
            <div class="form-group">
                <label>🔐 授权码</label>
                <input type="text" id="licenseKey" placeholder="XXXXX-XXXXX-XXXXX-XXXXX-XXXXX"
                       autocomplete="off" spellcheck="false" />
            </div>
            <button type="submit" class="btn-activate" id="activateBtn">🚀 激活授权</button>
        </form>

        <p class="hint">💡 联系管理员获取授权码</p>
    </div>

    <script>
        // 入场动画
        gsap.from('#card', { opacity: 0, y: 40, scale: 0.95, duration: 0.7, ease: 'back.out(1.4)' });
        gsap.from('#card .form-group, #card .btn-activate', {
            opacity: 0, y: 20, duration: 0.4, stagger: 0.08, ease: 'power2.out', delay: 0.3,
        });

        // 浮动装饰
        (function() {
            const emojis = ['🍳', '🥟', '🍜', '❤️', '⭐', '🧁', '🥘', '☀️'];
            const container = document.getElementById('decos');
            for (let i = 0; i < 8; i++) {
                const el = document.createElement('span');
                el.className = 'license-emoji';
                el.textContent = emojis[i];
                el.style.left = (5 + Math.random() * 90) + '%';
                el.style.top = (5 + Math.random() * 90) + '%';
                container.appendChild(el);
                gsap.set(el, { opacity: 0, scale: 0.5 });
                gsap.to(el, {
                    opacity: 0.12, scale: 1, y: -15 + Math.random() * -20,
                    duration: 3 + Math.random() * 2, repeat: -1, yoyo: true,
                    ease: 'sine.inOut', delay: i * 0.5,
                });
            }
        })();

        async function handleActivate(e) {
            e.preventDefault();
            const errEl = document.getElementById('errorMsg');
            const sucEl = document.getElementById('successMsg');
            const btn = document.getElementById('activateBtn');
            errEl.style.display = 'none';
            sucEl.style.display = 'none';

            const key = document.getElementById('licenseKey').value.trim();
            if (!key) { showError('请输入授权码'); return false; }

            btn.disabled = true;
            btn.textContent = '⏳ 激活中…';

            try {
                const res = await fetch('api/license.php?action=activate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ license_key: key }),
                });
                const data = await res.json();
                if (!res.ok) { showError(data.error || '激活失败'); return false; }

                sucEl.textContent = data.message || '🎉 激活成功！';
                sucEl.style.display = 'block';
                btn.textContent = '✅ 激活成功，跳转中…';

                setTimeout(() => { window.location.href = 'index.php'; }, 1000);
            } catch (e) {
                showError('网络错误，请重试');
            } finally {
                if (!sucEl.style.display || sucEl.style.display === 'none') {
                    btn.disabled = false;
                    btn.textContent = '🚀 激活授权';
                }
            }
            return false;
        }

        function showError(msg) {
            const el = document.getElementById('errorMsg');
            el.textContent = msg;
            el.style.display = 'block';
            gsap.from(el, { opacity: 0, x: -10, duration: 0.3 });
        }
    </script>
</body>
</html>

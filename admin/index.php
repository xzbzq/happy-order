<?php
/**
 * 幸福小厨 🏠 后台管理 - 仪表盘
 */
require_once __DIR__ . '/../config/database.php';

session_start();
header('Content-Type: text/html; charset=utf-8');

// 验证登录
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
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
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #fff5f5 0%, #fff8e1 100%);
            color: #5d4037;
            min-height: 100vh;
        }
        a { text-decoration: none; color: inherit; }

        /* ---- 顶部栏 ---- */
        .admin-header {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(0,0,0,0.04);
            padding: 0 28px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .admin-header .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .admin-header .header-left .logo-icon {
            font-size: 28px;
        }
        .admin-header .header-left h1 {
            font-size: 18px;
            font-weight: 700;
            background: linear-gradient(135deg, #ff8a65, #ffa726);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .admin-header .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .admin-header .header-right a {
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
        }
        .admin-header .header-right a:hover {
            background: rgba(255,167,38,0.10);
        }
        .admin-header .header-right .btn-logout {
            background: none;
            border: 1px solid #ffcdd2;
            color: #e53935;
            border-radius: 10px;
            padding: 8px 16px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
            font-weight: 500;
        }
        .admin-header .header-right .btn-logout:hover {
            background: #ffebee;
        }
        .admin-header .btn-home {
            background: linear-gradient(135deg, #ff8a65, #ffa726);
            color: #fff !important;
            border-radius: 10px;
            padding: 8px 18px;
            font-size: 14px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .admin-header .btn-home:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255,167,38,0.30);
        }

        /* ---- 主内容 ---- */
        .admin-main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 28px 24px 60px;
        }

        /* ---- 欢迎横幅 ---- */
        .admin-welcome {
            background: linear-gradient(135deg, rgba(255,138,101,0.08), rgba(255,167,38,0.06));
            border-radius: 20px;
            padding: 24px 28px;
            margin-bottom: 28px;
            border: 1px solid rgba(255,167,38,0.10);
        }
        .admin-welcome h2 {
            font-size: 20px;
            margin-bottom: 4px;
        }
        .admin-welcome p {
            color: #999;
            font-size: 14px;
        }

        /* ---- 统计卡片 ---- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: rgba(255,255,255,0.80);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 16px;
            padding: 22px 20px;
            border: 1px solid rgba(255,255,255,0.6);
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        }
        .stat-card .stat-icon {
            font-size: 28px;
            margin-bottom: 8px;
        }
        .stat-card .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: #3e2723;
            line-height: 1.2;
        }
        .stat-card .stat-label {
            font-size: 14px;
            color: #999;
            margin-top: 4px;
        }
        .stat-card .stat-unit {
            font-size: 14px;
            font-weight: 400;
            color: #999;
        }

        /* ---- 面板卡片 ---- */
        .panel-card {
            background: rgba(255,255,255,0.80);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid rgba(255,255,255,0.6);
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            margin-bottom: 24px;
        }
        .panel-card .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }
        .panel-card .panel-header h3 {
            font-size: 17px;
            font-weight: 700;
        }
        .panel-card .panel-header .badge {
            background: rgba(255,167,38,0.12);
            color: #ff8a65;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        /* ---- 趋势表 ---- */
        .trend-table {
            width: 100%;
            border-collapse: collapse;
        }
        .trend-table th, .trend-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #f5f0ec;
            font-size: 14px;
        }
        .trend-table th {
            font-weight: 600;
            color: #999;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .trend-table tr:last-child td { border-bottom: none; }
        .trend-bar {
            height: 6px;
            border-radius: 3px;
            background: linear-gradient(90deg, #ff8a65, #ffa726);
            min-width: 4px;
            transition: width 0.6s ease;
        }

        /* ---- 订单表格 ---- */
        .order-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 16px;
        }
        .order-filters input, .order-filters select {
            padding: 8px 14px;
            border: 2px solid #f0e6e0;
            border-radius: 10px;
            font-size: 14px;
            background: rgba(255,255,255,0.7);
            outline: none;
            transition: border-color 0.2s;
        }
        .order-filters input:focus, .order-filters select:focus {
            border-color: #ffa726;
        }
        .order-filters .btn-filter {
            padding: 8px 20px;
            background: linear-gradient(135deg, #ff8a65, #ffa726);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .order-filters .btn-filter:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255,167,38,0.25);
        }

        .admin-table-wrap {
            overflow-x: auto;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .admin-table th {
            background: #faf5f0;
            padding: 12px 14px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        .admin-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #f5f0ec;
            vertical-align: middle;
        }
        .admin-table tr:hover td {
            background: rgba(255,248,225,0.4);
        }
        .admin-table .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .admin-table .status-badge.pending { background: #fff3e0; color: #e65100; }
        .admin-table .status-badge.confirmed { background: #e8f5e9; color: #2e7d32; }
        .admin-table .status-badge.done { background: #e3f2fd; color: #1565c0; }
        .admin-table .order-items-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }
        .admin-table .order-items-preview span {
            background: #f5f0ec;
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 12px;
            white-space: nowrap;
        }
        .admin-table .action-btns {
            display: flex;
            gap: 6px;
        }
        .admin-table .action-btns button {
            padding: 4px 10px;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            cursor: pointer;
            transition: transform 0.15s;
        }
        .admin-table .action-btns button:hover { transform: scale(1.05); }
        .admin-table .action-btns .btn-confirm { background: #c8e6c9; color: #2e7d32; }
        .admin-table .action-btns .btn-done { background: #bbdefb; color: #1565c0; }
        .admin-table .action-btns .btn-delete { background: #ffcdd2; color: #c62828; }
        .admin-table .no-orders {
            text-align: center;
            padding: 40px;
            color: #bbb;
        }

        /* ---- 菜单概览 ---- */
        .menu-overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
        }
        .menu-overview-item {
            background: rgba(255,248,225,0.5);
            border-radius: 12px;
            padding: 14px;
            text-align: center;
            border: 1px solid rgba(255,167,38,0.08);
        }
        .menu-overview-item .cat-icon { font-size: 24px; }
        .menu-overview-item .cat-name { font-size: 14px; font-weight: 600; margin: 4px 0; }
        .menu-overview-item .cat-count { font-size: 12px; color: #999; }

        /* ---- 修改密码 ---- */
        .pw-form {
            display: flex;
            flex-direction: column;
            gap: 14px;
            max-width: 400px;
        }
        .pw-form .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #6d4c41;
            margin-bottom: 4px;
        }
        .pw-form .form-group input {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #f0e6e0;
            border-radius: 10px;
            font-size: 14px;
            background: rgba(255,255,255,0.7);
            outline: none;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        .pw-form .form-group input:focus {
            border-color: #ffa726;
        }
        .pw-form .btn-save {
            padding: 10px 24px;
            background: linear-gradient(135deg, #ff8a65, #ffa726);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            align-self: flex-start;
        }
        .pw-form .btn-save:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(255,167,38,0.25);
        }
        .pw-form .btn-save:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* ---- 邮件配置 ---- */
        #emailPanel .form-group {
            margin-bottom: 0;
        }
        #emailPanel .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #6d4c41;
            margin-bottom: 4px;
        }
        #emailPanel .form-group input {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #f0e6e0;
            border-radius: 10px;
            font-size: 14px;
            background: rgba(255,255,255,0.7);
            outline: none;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        #emailPanel .form-group input:focus {
            border-color: #ffa726;
        }
        #emailPanel .checkbox-label {
            font-size: 14px;
            color: #6d4c41;
        }
        #emailPanel .btn-outline {
            padding: 10px 20px;
            background: transparent;
            border: 2px solid #ffa726;
            border-radius: 10px;
            color: #ff8a65;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        #emailPanel .btn-outline:hover {
            background: linear-gradient(135deg, #ff8a65, #ffa726);
            color: #fff;
            border-color: transparent;
        }

        /* ---- Toast ---- */
        .admin-toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(12px);
            padding: 12px 24px;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.10);
            font-size: 15px;
            font-weight: 500;
            z-index: 999;
            border: 1px solid rgba(255,255,255,0.6);
            display: none;
        }
        .admin-toast.show { display: block; }
        .admin-toast.error { color: #e53935; }
        .admin-toast.success { color: #2e7d32; }

        /* ---- 加载状态 ---- */
        .loading-shimmer {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            color: #bbb;
            font-size: 16px;
        }
        .loading-shimmer::after {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f0e6e0;
            border-top-color: #ffa726;
            border-radius: 50%;
            margin-left: 10px;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 768px) {
            .admin-header { padding: 0 16px; }
            .admin-main { padding: 16px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .admin-header .header-right .btn-home span { display: none; }
            .admin-table { font-size: 13px; }
            .admin-table th, .admin-table td { padding: 8px 10px; }
        }
    </style>
</head>
<body>
    <!-- ====== 顶部栏 ====== -->
    <header class="admin-header" id="adminHeader">
        <div class="header-left">
            <span class="logo-icon">⚙️</span>
            <h1>幸福小厨 · 管理后台</h1>
        </div>
        <div class="header-right">
            <a href="../index.php" class="btn-home">🏠 <span>返回首页</span></a>
            <a href="logout.php" class="btn-logout">🚪 退出登录</a>
        </div>
    </header>

    <!-- ====== 主内容 ====== -->
    <main class="admin-main" id="adminMain">

        <!-- 欢迎 -->
        <div class="admin-welcome" id="welcomeBanner">
            <h2 id="welcomeText">👋 管理员你好！</h2>
            <p id="welcomeSub">今天也是幸福的一天，来看看餐厅的情况吧~</p>
        </div>

        <!-- 统计卡片 -->
        <div class="stats-grid" id="statsGrid"></div>

        <!-- 订单趋势 -->
        <div class="panel-card" id="trendPanel">
            <div class="panel-header">
                <h3>📈 近 7 天订单趋势</h3>
            </div>
            <div id="trendContent"><div class="loading-shimmer">加载中</div></div>
        </div>

        <!-- 订单管理 -->
        <div class="panel-card" id="orderPanel">
            <div class="panel-header">
                <h3>📋 订单管理</h3>
                <span class="badge">支持筛选</span>
            </div>
            <div class="order-filters">
                <input type="date" id="filterDate" />
                <select id="filterStatus">
                    <option value="">全部状态</option>
                    <option value="pending">待确认</option>
                    <option value="confirmed">已确认</option>
                    <option value="done">已完成</option>
                </select>
                <select id="filterMember">
                    <option value="">全部成员</option>
                    <option value="爸爸">👨 爸爸</option>
                    <option value="妈妈">👩 妈妈</option>
                    <option value="爷爷">👴 爷爷</option>
                    <option value="奶奶">👵 奶奶</option>
                    <option value="宝贝">🧒 宝贝</option>
                </select>
                <button class="btn-filter" onclick="loadOrders()">🔍 筛选</button>
            </div>
            <div class="admin-table-wrap" id="orderTableWrap">
                <div class="loading-shimmer">加载中</div>
            </div>
        </div>

        <!-- 菜单概览 + 修改密码 两列 -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
            <!-- 菜单概览 -->
            <div class="panel-card" id="menuPanel">
                <div class="panel-header">
                    <h3>🥘 菜单概览</h3>
                    <a href="../index.php#menu" class="badge" style="cursor:pointer;">去管理 →</a>
                </div>
                <div id="menuOverviewContent"><div class="loading-shimmer">加载中</div></div>
            </div>

            <!-- 修改密码 -->
            <div class="panel-card" id="passwordPanel">
                <div class="panel-header">
                    <h3>🔐 修改密码</h3>
                </div>
                <form class="pw-form" id="passwordForm" onsubmit="return handleChangePassword(event)">
                    <div class="form-group">
                        <label>原密码</label>
                        <input type="password" id="oldPassword" placeholder="输入原密码…" required />
                    </div>
                    <div class="form-group">
                        <label>新密码</label>
                        <input type="password" id="newPassword" placeholder="至少 4 位…" minlength="4" required />
                    </div>
                    <div class="form-group">
                        <label>确认新密码</label>
                        <input type="password" id="confirmPassword" placeholder="再次输入…" minlength="4" required />
                    </div>
                    <button type="submit" class="btn-save" id="pwSaveBtn">💾 更新密码</button>
                </form>
            </div>
        </div>

        <!-- 邮件配置 -->
        <div class="panel-card" id="emailPanel" style="margin-bottom:24px;">
            <div class="panel-header">
                <h3>📧 邮件通知配置</h3>
                <span id="emailStatus" class="badge">检测中...</span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group">
                    <label>SMTP 服务器</label>
                    <input type="text" id="emailHost" placeholder="smtp.qq.com" />
                </div>
                <div class="form-group">
                    <label>端口</label>
                    <input type="number" id="emailPort" placeholder="465" value="465" />
                </div>
                <div class="form-group">
                    <label>邮箱账号</label>
                    <input type="text" id="emailUser" placeholder="xxx@qq.com" />
                </div>
                <div class="form-group">
                    <label>邮箱密码/授权码</label>
                    <input type="password" id="emailPass" placeholder="留空则不修改" />
                </div>
                <div class="form-group">
                    <label>发件人名称</label>
                    <input type="text" id="emailFromName" placeholder="幸福小厨" />
                </div>
                <div class="form-group">
                    <label>通知接收邮箱</label>
                    <input type="email" id="emailNotifyTo" placeholder="family@example.com" />
                </div>
            </div>
            <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap;">
                <label class="checkbox-label" style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                    <input type="checkbox" id="emailEnabled" checked />
                    启用邮件通知
                </label>
                <button class="btn-save" id="emailSaveBtn" onclick="saveEmailConfig()">💾 保存配置</button>
                <button class="btn btn-sm btn-outline" id="emailTestBtn" onclick="testEmail()">📨 发送测试</button>
            </div>
        </div>

        <!-- 底部 -->
        <div style="text-align:center; padding:20px 0; color:#ccc; font-size:13px;">
            ❤️ 幸福小厨 · 家庭点单系统 &nbsp;|&nbsp; 用爱做每一顿饭
        </div>
    </main>

    <!-- Toast -->
    <div class="admin-toast" id="adminToast"></div>

    <script>
        // ====== GSAP 入场动画 ======
        gsap.from('#adminHeader', { opacity: 0, y: -20, duration: 0.4, ease: 'power2.out' });
        gsap.from('#welcomeBanner', { opacity: 0, y: 20, duration: 0.5, ease: 'power2.out', delay: 0.1 });
        gsap.from('.stat-card', { opacity: 0, y: 20, duration: 0.4, stagger: 0.06, ease: 'back.out(1.3)', delay: 0.2 });
        gsap.from('.panel-card', { opacity: 0, y: 20, duration: 0.4, stagger: 0.08, ease: 'power2.out', delay: 0.3 });

        // ====== Toast ======
        let toastTimer = null;
        function showToast(msg, type = 'success') {
            const el = document.getElementById('adminToast');
            el.textContent = msg;
            el.className = 'admin-toast ' + type + ' show';
            clearTimeout(toastTimer);
            gsap.killTweensOf(el);
            gsap.set(el, { opacity: 0, y: 10 });
            gsap.to(el, { opacity: 1, y: 0, duration: 0.3, ease: 'power2.out' });
            toastTimer = setTimeout(() => {
                gsap.to(el, { opacity: 0, y: 10, duration: 0.3, onComplete: () => { el.classList.remove('show'); } });
            }, 2500);
        }

        // ====== API 调用 ======
        async function adminApi(action, method = 'GET', body = null) {
            const opts = { method, headers: { 'Content-Type': 'application/json' } };
            if (body) opts.body = JSON.stringify(body);
            const res = await fetch('../api/admin.php?action=' + action, opts);
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || '请求失败');
            return data;
        }

        // ====== 加载统计数据 ======
        let statsData = null;
        async function loadStats() {
            try {
                statsData = await adminApi('stats');
                renderStats(statsData);
            } catch (e) {
                document.getElementById('statsGrid').innerHTML = `<div style="color:#e53935;">加载失败: ${e.message}</div>`;
            }
        }

        function renderStats(data) {
            const grid = document.getElementById('statsGrid');
            const cards = [
                { icon: '📋', value: data.today_orders, label: '今日订单', unit: '单' },
                { icon: '💰', value: '¥' + Number(data.today_revenue).toFixed(2), label: '今日收入', unit: '' },
                { icon: '📅', value: data.month_orders, label: '本月订单', unit: '单' },
                { icon: '🥘', value: data.total_items, label: '菜品总数', unit: '道' },
            ];
            grid.innerHTML = cards.map(c => `
                <div class="stat-card">
                    <div class="stat-icon">${c.icon}</div>
                    <div class="stat-value">${c.value} <span class="stat-unit">${c.unit}</span></div>
                    <div class="stat-label">${c.label}</div>
                </div>
            `).join('');
            // 动画
            gsap.from('.stat-card', { opacity: 0, y: 20, duration: 0.4, stagger: 0.06, ease: 'back.out(1.3)' });
            // 渲染趋势
            renderTrend(data.trend);
        }

        // ====== 渲染趋势 ======
        function renderTrend(trend) {
            const container = document.getElementById('trendContent');
            if (!trend || trend.length === 0) {
                container.innerHTML = '<div style="text-align:center;padding:30px;color:#bbb;">暂无数据</div>';
                return;
            }
            const maxCnt = Math.max(...trend.map(t => parseInt(t.cnt)), 1);
            const rows = trend.map(t => {
                const date = t.order_date;
                const cnt = parseInt(t.cnt);
                const rev = Number(t.revenue).toFixed(2);
                const pct = (cnt / maxCnt * 100).toFixed(1);
                const weekday = new Date(date + 'T00:00:00').toLocaleDateString('zh-CN', { weekday: 'short' });
                return `<tr>
                    <td>${date} (${weekday})</td>
                    <td><div style="display:flex;align-items:center;gap:10px;">
                        <div class="trend-bar" style="width:${pct}%"></div>
                        <span style="font-weight:600;white-space:nowrap;">${cnt} 单</span>
                    </div></td>
                    <td style="font-weight:600;">¥${rev}</td>
                </tr>`;
            }).join('');
            container.innerHTML = `<table class="trend-table">
                <thead><tr><th>日期</th><th>订单数</th><th>收入</th></tr></thead>
                <tbody>${rows}</tbody>
            </table>`;
            // 动画条
            gsap.from('.trend-bar', { width: '0%', duration: 0.8, ease: 'power2.out', stagger: 0.05 });
        }

        // ====== 加载订单 ======
        async function loadOrders() {
            const wrap = document.getElementById('orderTableWrap');
            wrap.innerHTML = '<div class="loading-shimmer">加载中</div>';

            try {
                const date = document.getElementById('filterDate').value;
                const status = document.getElementById('filterStatus').value;
                const member = document.getElementById('filterMember').value;
                let url = 'orders';
                const params = [];
                if (date) params.push('date=' + date);
                if (status) params.push('status=' + status);
                if (member) params.push('member=' + encodeURIComponent(member));
                if (params.length) url += '&' + params.join('&');

                const orders = await adminApi(url);
                renderOrders(orders);
            } catch (e) {
                wrap.innerHTML = `<div class="no-orders">❌ ${e.message}</div>`;
            }
        }

        function renderOrders(orders) {
            const wrap = document.getElementById('orderTableWrap');
            if (!orders || orders.length === 0) {
                wrap.innerHTML = '<div class="no-orders">🍃 没有找到符合条件的订单</div>';
                return;
            }
            const statusMap = { pending: '待确认', confirmed: '已确认', done: '已完成' };
            const rows = orders.map(o => {
                const itemsHtml = (o.items || []).map(oi =>
                    `<span>${oi.item_name} ×${oi.quantity}</span>`
                ).join('');
                const canConfirm = o.status === 'pending';
                const canDone = o.status === 'confirmed';
                return `<tr>
                    <td>${o.order_date}<br><small style="color:#bbb;">#${o.id}</small></td>
                    <td>${o.member_avatar || '👤'} ${o.member_name}</td>
                    <td>${o.meal_time || '—'}</td>
                    <td><div class="order-items-preview">${itemsHtml || '—'}</div></td>
                    <td>¥${Number(o.total_amount).toFixed(2)}</td>
                    <td><span class="status-badge ${o.status}">${statusMap[o.status] || o.status}</span></td>
                    <td>
                        <div class="action-btns">
                            ${canConfirm ? `<button class="btn-confirm" onclick="updateOrder(${o.id},'confirmed')">✅ 确认</button>` : ''}
                            ${canDone ? `<button class="btn-done" onclick="updateOrder(${o.id},'done')">🎉 完成</button>` : ''}
                            <button class="btn-delete" onclick="deleteOrder(${o.id})">🗑️</button>
                        </div>
                    </td>
                </tr>`;
            }).join('');
            wrap.innerHTML = `<table class="admin-table">
                <thead><tr>
                    <th>日期 / ID</th><th>点餐人</th><th>餐别</th><th>菜品</th><th>金额</th><th>状态</th><th>操作</th>
                </tr></thead>
                <tbody>${rows}</tbody>
            </table>`;
            gsap.from('.admin-table tbody tr', {
                opacity: 0, x: -10, duration: 0.3, stagger: 0.03, ease: 'power2.out',
            });
        }

        async function updateOrder(id, status) {
            try {
                await adminApi('update_order', 'PUT', { id, status });
                const msgs = { confirmed: '✅ 已确认', done: '🎉 已完成' };
                showToast(msgs[status] || '已更新');
                await loadOrders();
                await loadStats();
            } catch (e) {
                showToast(e.message, 'error');
            }
        }

        async function deleteOrder(id) {
            if (!confirm('确定要删除这个订单吗？')) return;
            try {
                await adminApi('delete_order&id=' + id, 'GET');
                showToast('🗑️ 已删除');
                await loadOrders();
                await loadStats();
            } catch (e) {
                showToast(e.message, 'error');
            }
        }

        // ====== 加载菜单概览 ======
        async function loadMenuOverview() {
            try {
                const cats = await adminApi('categories_with_count');
                const container = document.getElementById('menuOverviewContent');
                if (!cats || cats.length === 0) {
                    container.innerHTML = '<div style="text-align:center;padding:20px;color:#bbb;">暂无分类</div>';
                    return;
                }
                const html = `<div class="menu-overview-grid">${cats.map(c => `
                    <div class="menu-overview-item">
                        <div class="cat-icon">${c.icon || '🍽️'}</div>
                        <div class="cat-name">${c.name}</div>
                        <div class="cat-count">${c.item_count || 0} 道菜</div>
                    </div>
                `).join('')}</div>`;
                container.innerHTML = html;
                gsap.from('.menu-overview-item', {
                    opacity: 0, scale: 0.8, duration: 0.3, stagger: 0.04, ease: 'back.out(1.5)',
                });
            } catch (e) {
                document.getElementById('menuOverviewContent').innerHTML =
                    `<div style="color:#e53935;">加载失败: ${e.message}</div>`;
            }
        }

        // ====== 修改密码 ======
        async function handleChangePassword(e) {
            e.preventDefault();
            const oldPw = document.getElementById('oldPassword').value;
            const newPw = document.getElementById('newPassword').value;
            const confirmPw = document.getElementById('confirmPassword').value;

            if (newPw !== confirmPw) {
                showToast('两次输入的新密码不一致', 'error');
                return false;
            }
            if (newPw.length < 4) {
                showToast('新密码至少 4 位', 'error');
                return false;
            }

            const btn = document.getElementById('pwSaveBtn');
            btn.disabled = true;
            btn.textContent = '⏳ 更新中…';

            try {
                await adminApi('change_password', 'PUT', { old_password: oldPw, new_password: newPw });
                showToast('✅ 密码已更新！请牢记新密码');
                document.getElementById('passwordForm').reset();
            } catch (e) {
                showToast(e.message, 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = '💾 更新密码';
            }
            return false;
        }

        // ====== 邮件配置 ======
        async function loadEmailConfig() {
            const statusEl = document.getElementById('emailStatus');
            try {
                const cfg = await adminApi('email_config');
                document.getElementById('emailHost').value = cfg.host || '';
                document.getElementById('emailPort').value = cfg.port || 465;
                document.getElementById('emailUser').value = cfg.user || '';
                document.getElementById('emailFromName').value = cfg.from_name || '幸福小厨';
                document.getElementById('emailNotifyTo').value = cfg.notify_to || '';
                document.getElementById('emailEnabled').checked = cfg.enabled;
                statusEl.textContent = cfg.enabled ? '✅ 已启用' : '⏸️ 未启用';
                statusEl.className = 'badge ' + (cfg.enabled ? 'badge-success' : '');
            } catch (e) {
                statusEl.textContent = '❌ 加载失败';
            }
        }

        async function saveEmailConfig() {
            const btn = document.getElementById('emailSaveBtn');
            btn.disabled = true;
            btn.textContent = '⏳ 保存中…';

            try {
                const data = {
                    host: document.getElementById('emailHost').value,
                    port: parseInt(document.getElementById('emailPort').value) || 465,
                    user: document.getElementById('emailUser').value,
                    pass: document.getElementById('emailPass').value,
                    from_name: document.getElementById('emailFromName').value,
                    notify_to: document.getElementById('emailNotifyTo').value,
                    enabled: document.getElementById('emailEnabled').checked,
                };
                await adminApi('save_email_config', 'PUT', data);
                showToast('✅ 邮件配置已保存');
                document.getElementById('emailPass').value = '';
                await loadEmailConfig();
            } catch (e) {
                showToast(e.message, 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = '💾 保存配置';
            }
        }

        async function testEmail() {
            const btn = document.getElementById('emailTestBtn');
            btn.disabled = true;
            btn.textContent = '⏳ 保存并测试…';

            try {
                // 先保存当前配置
                const data = {
                    host: document.getElementById('emailHost').value,
                    port: parseInt(document.getElementById('emailPort').value) || 465,
                    user: document.getElementById('emailUser').value,
                    pass: document.getElementById('emailPass').value,
                    from_name: document.getElementById('emailFromName').value,
                    notify_to: document.getElementById('emailNotifyTo').value,
                    enabled: document.getElementById('emailEnabled').checked,
                };
                btn.textContent = '⏳ 保存配置…';
                await adminApi('save_email_config', 'PUT', data);
                document.getElementById('emailPass').value = '';
                await loadEmailConfig();

                // 发送测试邮件
                btn.textContent = '⏳ 发送测试邮件…';
                const res = await adminApi('test_email', 'POST');
                showToast(res.message || '✅ 测试邮件发送成功');
            } catch (e) {
                showToast(e.message, 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = '📨 发送测试';
            }
        }

        // ====== 设置默认日期 ======
        document.getElementById('filterDate').value = new Date().toISOString().slice(0, 10);

        // ====== 初始化 ======
        loadStats();
        loadOrders();
        loadMenuOverview();
        loadEmailConfig();
    </script>
</body>
</html>

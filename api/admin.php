<?php
/**
 * 幸福小厨 🏠 后台管理 API
 * 登录验证 + 数据管理
 */
require_once __DIR__ . '/../config/database.php';

session_start();
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {

        // ============================================================
        // 登录验证
        // ============================================================
        case 'login':
            $input = json_decode(file_get_contents('php://input'), true);
            $pass  = $input['password'] ?? '';

            $adminFile = __DIR__ . '/../config/admin.php';
            $firstRun  = !file_exists($adminFile);

            // 首次使用：创建管理员密码
            if ($firstRun) {
                if (strlen($pass) < 4) {
                    jsonExit(['error' => '密码至少 4 位'], 400);
                }
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $code = "<?php\n/** 管理员密码（自动生成） */\ndefine('ADMIN_PASS_HASH', " . var_export($hash, true) . ");\n";
                file_put_contents($adminFile, $code);
                $_SESSION['admin_logged_in'] = true;
                jsonExit(['success' => true, 'first_run' => true, 'message' => '🎉 管理员创建成功！']);
            }

            // 验证登录
            require $adminFile;
            if (password_verify($pass, ADMIN_PASS_HASH)) {
                $_SESSION['admin_logged_in'] = true;
                jsonExit(['success' => true, 'message' => '登录成功 🎉']);
            } else {
                jsonExit(['error' => '密码错误，请重试 🙏'], 401);
            }
            break;

        // ============================================================
        // 退出登录
        // ============================================================
        case 'logout':
            $_SESSION['admin_logged_in'] = false;
            session_destroy();
            jsonExit(['success' => true]);
            break;

        // ============================================================
        // 检查登录状态
        // ============================================================
        case 'status':
            $adminFile = __DIR__ . '/../config/admin.php';
            $hasAdmin = file_exists($adminFile);
            jsonExit([
                'logged_in' => !empty($_SESSION['admin_logged_in']),
                'has_admin' => $hasAdmin,
            ]);
            break;

        // ============================================================
        // 修改密码
        // ============================================================
        case 'change_password':
            requireAdmin();
            $input = json_decode(file_get_contents('php://input'), true);
            $oldPass = $input['old_password'] ?? '';
            $newPass = $input['new_password'] ?? '';

            $adminFile = __DIR__ . '/../config/admin.php';
            require $adminFile;

            if (!password_verify($oldPass, ADMIN_PASS_HASH)) {
                jsonExit(['error' => '原密码错误'], 401);
            }
            if (strlen($newPass) < 4) {
                jsonExit(['error' => '新密码至少 4 位'], 400);
            }

            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $code = "<?php\n/** 管理员密码（自动生成） */\ndefine('ADMIN_PASS_HASH', " . var_export($hash, true) . ");\n";
            file_put_contents($adminFile, $code);
            jsonExit(['success' => true, 'message' => '密码已更新 ✅']);
            break;

        // ============================================================
        // 统计数据
        // ============================================================
        case 'stats':
            requireAdmin();

            // 今日订单数
            $today = date('Y-m-d');
            $stmt = $db->query("SELECT COUNT(*) FROM orders WHERE order_date = '$today'");
            $todayOrders = (int)$stmt->fetchColumn();

            // 本月订单数
            $month = date('Y-m');
            $stmt = $db->query("SELECT COUNT(*) FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = '$month'");
            $monthOrders = (int)$stmt->fetchColumn();

            // 菜品总数
            $stmt = $db->query('SELECT COUNT(*) FROM menu_items');
            $totalItems = (int)$stmt->fetchColumn();

            // 分类总数
            $stmt = $db->query('SELECT COUNT(*) FROM categories');
            $totalCategories = (int)$stmt->fetchColumn();

            // 今日收入
            $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE order_date = '$today'");
            $todayRevenue = (float)$stmt->fetchColumn();

            // 本周订单趋势（近7天）
            $stmt = $db->query("
                SELECT order_date, COUNT(*) as cnt, COALESCE(SUM(total_amount), 0) as revenue
                FROM orders
                WHERE order_date >= DATE_SUB('$today', INTERVAL 6 DAY)
                GROUP BY order_date
                ORDER BY order_date ASC
            ");
            $trend = $stmt->fetchAll();

            jsonExit([
                'today_orders'     => $todayOrders,
                'month_orders'     => $monthOrders,
                'total_items'      => $totalItems,
                'total_categories' => $totalCategories,
                'today_revenue'    => $todayRevenue,
                'trend'            => $trend,
            ]);
            break;

        // ============================================================
        // 所有订单（支持筛选）
        // ============================================================
        case 'orders':
            requireAdmin();

            $date   = $_GET['date'] ?? '';
            $status = $_GET['status'] ?? '';
            $member = $_GET['member'] ?? '';

            $sql  = 'SELECT * FROM orders WHERE 1=1';
            $bind = [];

            if ($date) {
                $sql .= ' AND order_date = :date';
                $bind['date'] = $date;
            }
            if ($status) {
                $sql .= ' AND status = :status';
                $bind['status'] = $status;
            }
            if ($member) {
                $sql .= ' AND member_name = :member';
                $bind['member'] = $member;
            }

            $sql .= ' ORDER BY order_date DESC, created_at DESC LIMIT 200';
            $stmt = $db->prepare($sql);
            $stmt->execute($bind);
            $orders = $stmt->fetchAll();

            // 补充订单明细
            if ($orders) {
                $ids = array_column($orders, 'id');
                $ph  = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $db->prepare("SELECT * FROM order_items WHERE order_id IN ($ph)");
                $stmt->execute($ids);
                $allItems = $stmt->fetchAll();
                $grouped = [];
                foreach ($allItems as $oi) {
                    $grouped[$oi['order_id']][] = $oi;
                }
                foreach ($orders as &$o) {
                    $o['items'] = $grouped[$o['id']] ?? [];
                }
            }

            jsonExit($orders);
            break;

        // ============================================================
        // 更新订单状态
        // ============================================================
        case 'update_order':
            requireAdmin();
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['id']) || empty($input['status'])) {
                jsonExit(['error' => '参数错误'], 400);
            }
            $stmt = $db->prepare('UPDATE orders SET status = :status WHERE id = :id');
            $stmt->execute(['id' => $input['id'], 'status' => $input['status']]);
            jsonExit(['success' => true, 'message' => '已更新 ✅']);
            break;

        // ============================================================
        // 删除订单
        // ============================================================
        case 'delete_order':
            requireAdmin();
            $id = $_GET['id'] ?? 0;
            $stmt = $db->prepare('DELETE FROM orders WHERE id = :id');
            $stmt->execute(['id' => $id]);
            jsonExit(['success' => true, 'message' => '已删除']);
            break;

        // ============================================================
        // 获取分类列表（管理用，含菜品数量）
        // ============================================================
        case 'categories_with_count':
            requireAdmin();
            $stmt = $db->query('
                SELECT c.*, COUNT(m.id) as item_count
                FROM categories c
                LEFT JOIN menu_items m ON m.category_id = c.id
                GROUP BY c.id
                ORDER BY c.sort_order ASC
            ');
            jsonExit($stmt->fetchAll());
            break;

        // ============================================================
        // 获取邮件配置
        // ============================================================
        case 'email_config':
            requireAdmin();
            $emailFile = __DIR__ . '/../config/email.php';
            if (!file_exists($emailFile)) {
                jsonExit([
                    'enabled'    => false,
                    'host'       => '',
                    'port'       => 465,
                    'user'       => '',
                    'pass'       => '',
                    'from'       => '',
                    'from_name'  => '幸福小厨',
                    'notify_to'  => '',
                ]);
            }
            require $emailFile;
            jsonExit([
                'enabled'    => defined('SMTP_ENABLED') ? SMTP_ENABLED : false,
                'host'       => SMTP_HOST,
                'port'       => SMTP_PORT,
                'user'       => SMTP_USER,
                'pass'       => '', // 不回传密码
                'from'       => SMTP_FROM,
                'from_name'  => SMTP_FROM_NAME,
                'notify_to'  => NOTIFY_EMAIL,
            ]);
            break;

        // ============================================================
        // 保存邮件配置
        // ============================================================
        case 'save_email_config':
            requireAdmin();
            $input = json_decode(file_get_contents('php://input'), true);
            $host      = $input['host'] ?? '';
            $port      = (int)($input['port'] ?? 465);
            $user      = $input['user'] ?? '';
            $pass      = $input['pass'] ?? '';
            $from      = $input['from'] ?? $user;
            $fromName  = $input['from_name'] ?? '幸福小厨';
            $notifyTo  = $input['notify_to'] ?? '';
            $enabled   = !empty($input['enabled']);

            if (!$host || !$user || !$notifyTo) {
                jsonExit(['error' => '请填写 SMTP 服务器、账号和通知邮箱'], 400);
            }

            // 保留旧密码（如果新密码为空）
            $emailFile = __DIR__ . '/../config/email.php';
            if (!$pass && file_exists($emailFile)) {
                require $emailFile;
                $pass = SMTP_PASS;
            }

            $code = "<?php\n/** 邮件配置（自动生成） */\n"
                  . "define('SMTP_ENABLED', " . ($enabled ? 'true' : 'false') . ");\n"
                  . "define('SMTP_HOST', " . var_export($host, true) . ");\n"
                  . "define('SMTP_PORT', $port);\n"
                  . "define('SMTP_USER', " . var_export($user, true) . ");\n"
                  . "define('SMTP_PASS', " . var_export($pass, true) . ");\n"
                  . "define('SMTP_FROM', " . var_export($from, true) . ");\n"
                  . "define('SMTP_FROM_NAME', " . var_export($fromName, true) . ");\n"
                  . "define('NOTIFY_EMAIL', " . var_export($notifyTo, true) . ");\n";
            file_put_contents($emailFile, $code);
            jsonExit(['success' => true, 'message' => '邮件配置已保存 ✅']);
            break;

        // ============================================================
        // 测试邮件发送
        // ============================================================
        case 'test_email':
            requireAdmin();
            $emailFile = __DIR__ . '/../config/email.php';
            if (!file_exists($emailFile)) {
                jsonExit(['error' => '请先保存邮件配置'], 400);
            }
            require $emailFile;
            if (!SMTP_ENABLED) {
                jsonExit(['error' => '邮件功能未启用'], 400);
            }
            require_once __DIR__ . '/../lib/Mailer.php';
            try {
                $mailer = new Mailer([
                    'host'      => SMTP_HOST,
                    'port'      => SMTP_PORT,
                    'user'      => SMTP_USER,
                    'pass'      => SMTP_PASS,
                    'from'      => SMTP_FROM,
                    'from_name' => SMTP_FROM_NAME,
                ]);
                $mailer->send(NOTIFY_EMAIL, '🏠 幸福小厨 · 测试邮件', '
                    <div style="font-family:sans-serif;max-width:480px;margin:0 auto;padding:24px;">
                        <div style="text-align:center;font-size:40px;margin-bottom:12px;">🏠</div>
                        <h2 style="text-align:center;color:#ff8a65;">邮件配置正确 ✅</h2>
                        <p style="color:#666;text-align:center;">恭喜！幸福小厨的邮件通知已配置成功！</p>
                        <hr style="border:none;border-top:1px solid #f0e6e0;margin:20px 0;">
                        <p style="color:#999;font-size:13px;text-align:center;">❤️ 幸福小厨 · 家庭点单系统</p>
                    </div>
                ');
                jsonExit(['success' => true, 'message' => '测试邮件发送成功 ✅ 请检查收件箱']);
            } catch (Exception $e) {
                jsonExit(['error' => '发送失败: ' . $e->getMessage()], 500);
            }
            break;

        default:
            jsonExit(['error' => '未知操作'], 400);
    }
} catch (Exception $e) {
    jsonExit(['error' => $e->getMessage()], 500);
}

/** 验证管理员登录 */
function requireAdmin(): void
{
    if (empty($_SESSION['admin_logged_in'])) {
        http_response_code(401);
        echo json_encode(['error' => '请先登录'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

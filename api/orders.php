<?php
/**
 * 订单 API
 */

// 安装检测
$configFile = __DIR__ . '/../config/database.php';
if (!file_exists($configFile) || filesize($configFile) < 100) {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => '系统未安装，请先运行安装程序'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $db     = getDB();

    switch ($method) {
        // ---- 获取订单列表 / 详情 ----
        case 'GET':
            $id = $_GET['id'] ?? null;

            // 单个订单详情（含明细）
            if ($id) {
                $order = $db->prepare('SELECT * FROM orders WHERE id = :id');
                $order->execute(['id' => $id]);
                $order = $order->fetch();
                if (!$order) {
                    jsonExit(['error' => '订单不存在'], 404);
                }
                $items = $db->prepare(
                    'SELECT * FROM order_items WHERE order_id = :order_id'
                );
                $items->execute(['order_id' => $id]);
                $order['items'] = $items->fetchAll();
                jsonExit($order);
            }

            // 列表
            $date    = $_GET['date'] ?? date('Y-m-d');
            $member  = $_GET['member'] ?? null;
            $status  = $_GET['status'] ?? null;

            $sql  = 'SELECT * FROM orders WHERE order_date = :date';
            $bind = ['date' => $date];

            if ($member) {
                $sql .= ' AND member_name = :member';
                $bind['member'] = $member;
            }
            if ($status) {
                $sql .= ' AND status = :status';
                $bind['status'] = $status;
            }

            $sql .= ' ORDER BY meal_time ASC, created_at ASC';
            $stmt = $db->prepare($sql);
            $stmt->execute($bind);
            $orders = $stmt->fetchAll();

            // 补充每个订单的明细
            if ($orders) {
                $ids  = array_column($orders, 'id');
                $ph   = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $db->prepare(
                    "SELECT * FROM order_items WHERE order_id IN ($ph) ORDER BY id ASC"
                );
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

        // ---- 创建订单 ----
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['member_name']) || empty($input['items'])) {
                jsonExit(['error' => '请填写点餐人和菜品'], 400);
            }

            $db->beginTransaction();
            try {
                // 计算总价
                $total = 0;
                foreach ($input['items'] as $item) {
                    $total += ($item['price'] * ($item['quantity'] ?? 1));
                }

                // 插入订单
                $stmt = $db->prepare(
                    'INSERT INTO orders (member_name, member_avatar, notes, total_amount, meal_time)
                     VALUES (:member_name, :member_avatar, :notes, :total_amount, :meal_time)'
                );
                $stmt->execute([
                    'member_name'   => $input['member_name'],
                    'member_avatar' => $input['member_avatar'] ?? '👤',
                    'notes'         => $input['notes'] ?? '',
                    'total_amount'  => $total,
                    'meal_time'     => $input['meal_time'] ?? '',
                ]);
                $orderId = $db->lastInsertId();

                // 插入订单明细
                $stmt = $db->prepare(
                    'INSERT INTO order_items (order_id, item_id, item_name, quantity, unit_price)
                     VALUES (:order_id, :item_id, :item_name, :quantity, :unit_price)'
                );
                foreach ($input['items'] as $item) {
                    $stmt->execute([
                        'order_id'  => $orderId,
                        'item_id'   => $item['item_id'],
                        'item_name' => $item['item_name'],
                        'quantity'  => $item['quantity'] ?? 1,
                        'unit_price'=> $item['price'],
                    ]);
                }

                $db->commit();

                // ---- 发送邮件通知 ----
                $emailFile = __DIR__ . '/../config/email.php';
                if (file_exists($emailFile)) {
                    require $emailFile;
                    if (defined('SMTP_ENABLED') && SMTP_ENABLED) {
                        try {
                            require_once __DIR__ . '/../lib/Mailer.php';
                            $mailer = new Mailer([
                                'host'      => SMTP_HOST,
                                'port'      => SMTP_PORT,
                                'user'      => SMTP_USER,
                                'pass'      => SMTP_PASS,
                                'from'      => SMTP_FROM,
                                'from_name' => SMTP_FROM_NAME,
                            ]);
                            $itemsHtml = '';
                            foreach ($input['items'] as $item) {
                                $itemsHtml .= '<tr><td style="padding:6px 10px;border-bottom:1px solid #f0e6e0;">'
                                    . htmlspecialchars($item['item_name']) . '</td>'
                                    . '<td style="padding:6px 10px;border-bottom:1px solid #f0e6e0;text-align:center;">×' . (int)($item['quantity'] ?? 1) . '</td>'
                                    . '<td style="padding:6px 10px;border-bottom:1px solid #f0e6e0;text-align:right;">¥' . number_format($item['price'] * ($item['quantity'] ?? 1), 2) . '</td></tr>';
                            }
                            $mealLabel = $input['meal_time'] ?? '随便吃吃';
                            $notes = $input['notes'] ?? '';
                            $notesHtml = $notes ? "<p style='color:#999;font-style:italic;'>📝 备注：$notes</p>" : '';
                            $mailer->send(NOTIFY_EMAIL, '🍳 新订单 · ' . $input['member_name'] . ' 点餐啦', '
                                <div style="font-family:sans-serif;max-width:520px;margin:0 auto;padding:24px;background:#fff;border-radius:16px;">
                                    <div style="text-align:center;font-size:36px;margin-bottom:8px;">🏠</div>
                                    <h2 style="text-align:center;color:#ff8a65;margin:0 0 4px 0;">新订单通知</h2>
                                    <p style="text-align:center;color:#999;margin:0 0 20px 0;">' . htmlspecialchars($input['member_name']) . ' 刚刚点单啦 🎉</p>
                                    <div style="background:#fefaf5;border-radius:12px;padding:16px;margin-bottom:16px;">
                                        <p style="margin:0 0 8px 0;"><strong>👤 点餐人：</strong>' . htmlspecialchars($input['member_name']) . ' ' . ($input['member_avatar'] ?? '') . '</p>
                                        <p style="margin:0 0 8px 0;"><strong>🍽️ 餐别：</strong>' . $mealLabel . '</p>
                                        <p style="margin:0;"><strong>📋 订单号：</strong>#' . $orderId . '</p>
                                    </div>
                                    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
                                        <thead><tr style="background:#fff5f0;">
                                            <th style="padding:8px 10px;text-align:left;">菜品</th>
                                            <th style="padding:8px 10px;text-align:center;">数量</th>
                                            <th style="padding:8px 10px;text-align:right;">金额</th>
                                        </tr></thead>
                                        <tbody>' . $itemsHtml . '</tbody>
                                        <tfoot><tr>
                                            <td colspan="2" style="padding:10px;text-align:right;font-weight:700;">合计</td>
                                            <td style="padding:10px;text-align:right;font-weight:700;color:#ff8a65;font-size:18px;">¥' . number_format($total, 2) . '</td>
                                        </tr></tfoot>
                                    </table>
                                    ' . $notesHtml . '
                                    <hr style="border:none;border-top:1px solid #f0e6e0;margin:20px 0;">
                                    <p style="color:#999;font-size:13px;text-align:center;">❤️ 幸福小厨 · 家庭点单系统</p>
                                </div>
                            ');
                        } catch (Exception $e) {
                            // 邮件发送失败不影响订单
                            error_log('邮件通知失败: ' . $e->getMessage());
                        }
                    }
                }

                jsonExit(['id' => $orderId, 'message' => '下单成功！幸福开饭 🥰'], 201);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        // ---- 更新订单状态 / 信息 ----
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['id'])) {
                jsonExit(['error' => '缺少订单ID'], 400);
            }

            // 只更新状态
            if (isset($input['status'])) {
                $stmt = $db->prepare('UPDATE orders SET status = :status WHERE id = :id');
                $stmt->execute(['id' => $input['id'], 'status' => $input['status']]);
                $msg = $input['status'] === 'confirmed' ? '已确认订单 ✅' : ($input['status'] === 'done' ? '已完成 🎉' : '已更新');
                jsonExit(['message' => $msg]);
            }

            jsonExit(['error' => '请提供要更新的字段'], 400);
            break;

        // ---- 删除订单 ----
        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                jsonExit(['error' => '缺少订单ID'], 400);
            }
            $stmt = $db->prepare('DELETE FROM orders WHERE id = :id');
            $stmt->execute(['id' => $id]);
            jsonExit(['message' => '订单已取消']);
            break;

        default:
            jsonExit(['error' => '不支持的请求方法'], 405);
    }
} catch (Exception $e) {
    jsonExit(['error' => $e->getMessage()], 500);
}

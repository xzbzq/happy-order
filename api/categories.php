<?php
/**
 * 分类 API
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

// 写入操作需要管理员登录
session_start();
function requireWriteAuth(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') return;
    if (empty($_SESSION['admin_logged_in'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => '请先登录后台解锁管理'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
requireWriteAuth();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $db     = getDB();

    switch ($method) {
        // ---- 获取全部分类 ----
        case 'GET':
            $stmt = $db->query('SELECT * FROM categories ORDER BY sort_order ASC, id ASC');
            $cats = $stmt->fetchAll();
            // 服务端 emoji 兼容（替换显示异常的字符）
            $emojiFix = ['🧋' => '🥤', '🥟' => '🍤', '🧁' => '🍪', '🥘' => '🍝', '🥗' => '🥒'];
            foreach ($cats as &$c) {
                $c['icon'] = str_replace(array_keys($emojiFix), array_values($emojiFix), $c['icon'] ?? '🍽️');
                if (empty($c['icon'])) $c['icon'] = '🍽️';
            }
            jsonExit($cats);
            break;

        // ---- 新增分类 ----
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['name'])) {
                jsonExit(['error' => '分类名称不能为空'], 400);
            }
            $stmt = $db->prepare(
                'INSERT INTO categories (name, icon, sort_order) VALUES (:name, :icon, :sort_order)'
            );
            $stmt->execute([
                'name'       => $input['name'],
                'icon'       => $input['icon'] ?? '🍽️',
                'sort_order' => $input['sort_order'] ?? 0,
            ]);
            jsonExit(['id' => $db->lastInsertId(), 'message' => '新增成功 💕'], 201);
            break;

        // ---- 更新分类 ----
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['id'])) {
                jsonExit(['error' => '缺少分类ID'], 400);
            }
            $stmt = $db->prepare(
                'UPDATE categories SET name = :name, icon = :icon, sort_order = :sort_order WHERE id = :id'
            );
            $stmt->execute([
                'id'         => $input['id'],
                'name'       => $input['name'],
                'icon'       => $input['icon'] ?? '🍽️',
                'sort_order' => $input['sort_order'] ?? 0,
            ]);
            jsonExit(['message' => '更新成功 💕']);
            break;

        // ---- 删除分类 ----
        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                jsonExit(['error' => '缺少分类ID'], 400);
            }
            $stmt = $db->prepare('DELETE FROM categories WHERE id = :id');
            $stmt->execute(['id' => $id]);
            jsonExit(['message' => '删除成功']);
            break;

        default:
            jsonExit(['error' => '不支持的请求方法'], 405);
    }
} catch (Exception $e) {
    jsonExit(['error' => $e->getMessage()], 500);
}

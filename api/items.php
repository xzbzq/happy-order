<?php
/**
 * 菜品 API
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
        // ---- 获取菜品列表（可按分类筛选） ----
        case 'GET':
            $categoryId = $_GET['category_id'] ?? null;
            $available  = $_GET['available'] ?? null;

            $sql  = 'SELECT m.*, c.name AS category_name, c.icon AS category_icon
                     FROM menu_items m
                     LEFT JOIN categories c ON c.id = m.category_id';
            $where = [];
            $bind  = [];

            if ($categoryId) {
                $where[]         = 'm.category_id = :category_id';
                $bind['category_id'] = $categoryId;
            }
            if ($available !== null) {
                $where[]           = 'm.is_available = :available';
                $bind['available'] = $available ? 1 : 0;
            }

            if ($where) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }
            $sql .= ' ORDER BY m.is_recommend DESC, m.id ASC';

            $stmt = $db->prepare($sql);
            $stmt->execute($bind);
            $items = $stmt->fetchAll();
            // 服务端 emoji 兼容
            $emojiFix = ['🧋' => '🥤', '🥟' => '🍤', '🧁' => '🍪', '🥘' => '🍝', '🥗' => '🥒'];
            foreach ($items as &$item) {
                $item['category_icon'] = str_replace(array_keys($emojiFix), array_values($emojiFix), $item['category_icon'] ?? '🍽️');
                if (empty($item['category_icon'])) $item['category_icon'] = '🍽️';
            }
            jsonExit($items);
            break;

        // ---- 新增菜品 ----
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['name']) || empty($input['category_id'])) {
                jsonExit(['error' => '菜名和分类不能为空'], 400);
            }
            $stmt = $db->prepare(
                'INSERT INTO menu_items (category_id, name, price, description, is_recommend, is_available)
                 VALUES (:category_id, :name, :price, :description, :is_recommend, :is_available)'
            );
            $stmt->execute([
                'category_id'  => $input['category_id'],
                'name'         => $input['name'],
                'price'        => $input['price'] ?? 0,
                'description'  => $input['description'] ?? '',
                'is_recommend' => $input['is_recommend'] ?? 0,
                'is_available' => $input['is_available'] ?? 1,
            ]);
            jsonExit(['id' => $db->lastInsertId(), 'message' => '菜品已上架 🎉'], 201);
            break;

        // ---- 更新菜品 ----
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['id'])) {
                jsonExit(['error' => '缺少菜品ID'], 400);
            }
            $stmt = $db->prepare(
                'UPDATE menu_items SET
                    category_id  = :category_id,
                    name         = :name,
                    price        = :price,
                    description  = :description,
                    is_recommend = :is_recommend,
                    is_available = :is_available
                 WHERE id = :id'
            );
            $stmt->execute([
                'id'           => $input['id'],
                'category_id'  => $input['category_id'],
                'name'         => $input['name'],
                'price'        => $input['price'] ?? 0,
                'description'  => $input['description'] ?? '',
                'is_recommend' => $input['is_recommend'] ?? 0,
                'is_available' => $input['is_available'] ?? 1,
            ]);
            jsonExit(['message' => '菜品已更新 ✅']);
            break;

        // ---- 删除菜品 ----
        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                jsonExit(['error' => '缺少菜品ID'], 400);
            }
            $stmt = $db->prepare('DELETE FROM menu_items WHERE id = :id');
            $stmt->execute(['id' => $id]);
            jsonExit(['message' => '已删除']);
            break;

        default:
            jsonExit(['error' => '不支持的请求方法'], 405);
    }
} catch (Exception $e) {
    jsonExit(['error' => $e->getMessage()], 500);
}

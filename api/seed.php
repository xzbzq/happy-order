<?php
/**
 * 幸福小厨 🏠 示例菜单数据导入
 * 访问: POST api/seed.php  (需要管理员登录)
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

session_start();
if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => '请先登录'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = getDB();

    // 已有数据则跳过
    $stmt = $db->query('SELECT COUNT(*) FROM categories');
    $catCount = (int)$stmt->fetchColumn();
    $stmt = $db->query('SELECT COUNT(*) FROM menu_items');
    $itemCount = (int)$stmt->fetchColumn();

    if ($catCount > 0 || $itemCount > 0) {
        // 追加模式：不清除已有数据，只补充
    }

    // 分类 — 使用通用性好的 emoji
    $categories = [
        ['name' => '早餐',   'icon' => '🌅', 'sort_order' => 1],
        ['name' => '午餐',   'icon' => '☀️', 'sort_order' => 2],
        ['name' => '晚餐',   'icon' => '🌙', 'sort_order' => 3],
        ['name' => '加餐',   'icon' => '🍪', 'sort_order' => 4],
        ['name' => '主食',   'icon' => '🍚', 'sort_order' => 5],
        ['name' => '汤羹',   'icon' => '🥣', 'sort_order' => 6],
        ['name' => '饮品',   'icon' => '🥤', 'sort_order' => 7],
        ['name' => '凉菜',   'icon' => '🥒', 'sort_order' => 8],
    ];

    $catIdMap = [];
    $insertCat = $db->prepare('INSERT IGNORE INTO categories (name, icon, sort_order) VALUES (:name, :icon, :sort_order)');

    foreach ($categories as $c) {
        // 检查是否已存在同名分类
        $check = $db->prepare('SELECT id FROM categories WHERE name = :name LIMIT 1');
        $check->execute(['name' => $c['name']]);
        $existing = $check->fetch();

        if ($existing) {
            $catIdMap[$c['name']] = $existing['id'];
        } else {
            $insertCat->execute($c);
            $catIdMap[$c['name']] = $db->lastInsertId();
        }
    }

    // 菜品 — 按分类
    $items = [
        '早餐' => [
            ['name' => '小米粥',         'price' => 3,   'description' => '暖心暖胃', 'is_recommend' => true, 'is_available' => true],
            ['name' => '豆浆油条',       'price' => 5,   'description' => '经典搭配', 'is_recommend' => true, 'is_available' => true],
            ['name' => '煮鸡蛋',         'price' => 2,   'description' => '营养早餐必备', 'is_available' => true],
            ['name' => '蒸红薯',         'price' => 4,   'description' => '香甜软糯', 'is_available' => true],
            ['name' => '小笼包',         'price' => 8,   'description' => '鲜肉小笼', 'is_recommend' => true, 'is_available' => true],
            ['name' => '煎饺',           'price' => 6,   'description' => '底部金黄', 'is_available' => true],
            ['name' => '皮蛋瘦肉粥',     'price' => 5,   'description' => '咸香适口', 'is_available' => true],
            ['name' => '牛奶燕麦',       'price' => 4,   'description' => '健康美味', 'is_available' => true],
        ],
        '午餐' => [
            ['name' => '番茄炒蛋',       'price' => 12,  'description' => '国民家常菜', 'is_recommend' => true, 'is_available' => true],
            ['name' => '青椒肉丝',       'price' => 15,  'description' => '下饭神器', 'is_available' => true],
            ['name' => '宫保鸡丁',       'price' => 18,  'description' => '微辣酸甜', 'is_recommend' => true, 'is_available' => true],
            ['name' => '红烧排骨',       'price' => 28,  'description' => '软烂入味', 'is_recommend' => true, 'is_available' => true],
            ['name' => '清蒸鲈鱼',       'price' => 32,  'description' => '鲜嫩无比', 'is_available' => true],
            ['name' => '蒜蓉西兰花',     'price' => 10,  'description' => '清爽可口', 'is_available' => true],
            ['name' => '可乐鸡翅',       'price' => 20,  'description' => '小朋友最爱', 'is_recommend' => true, 'is_available' => true],
            ['name' => '鱼香肉丝',       'price' => 16,  'description' => '经典川菜', 'is_available' => true],
        ],
        '晚餐' => [
            ['name' => '酸菜鱼',         'price' => 35,  'description' => '酸爽开胃', 'is_recommend' => true, 'is_available' => true],
            ['name' => '水煮牛肉',       'price' => 38,  'description' => '麻辣鲜香', 'is_available' => true],
            ['name' => '糖醋里脊',       'price' => 22,  'description' => '外酥里嫩', 'is_recommend' => true, 'is_available' => true],
            ['name' => '麻婆豆腐',       'price' => 10,  'description' => '麻辣下饭', 'is_available' => true],
            ['name' => '东坡肉',         'price' => 30,  'description' => '肥而不腻', 'is_available' => true],
            ['name' => '地三鲜',         'price' => 12,  'description' => '茄子土豆青椒', 'is_available' => true],
            ['name' => '干煸四季豆',     'price' => 14,  'description' => '焦香可口', 'is_available' => true],
            ['name' => '白灼虾',         'price' => 36,  'description' => '原汁原味', 'is_recommend' => true, 'is_available' => true],
        ],
        '加餐' => [
            ['name' => '水果拼盘',       'price' => 8,   'description' => '当季鲜果', 'is_recommend' => true, 'is_available' => true],
            ['name' => '酸奶',           'price' => 4,   'description' => '清爽助消化', 'is_available' => true],
            ['name' => '坚果混合',       'price' => 6,   'description' => '每日坚果', 'is_available' => true],
            ['name' => '小蛋糕',         'price' => 5,   'description' => '松软香甜', 'is_available' => true],
            ['name' => '绿豆汤',         'price' => 3,   'description' => '消暑解热', 'is_available' => true],
        ],
        '主食' => [
            ['name' => '白米饭',         'price' => 2,   'description' => '粒粒饱满', 'is_available' => true],
            ['name' => '蛋炒饭',         'price' => 8,   'description' => '金包银', 'is_recommend' => true, 'is_available' => true],
            ['name' => '手工面条',       'price' => 6,   'description' => '筋道爽滑', 'is_available' => true],
            ['name' => '馒头',           'price' => 1,   'description' => '松软白面', 'is_available' => true],
            ['name' => '葱油饼',         'price' => 5,   'description' => '层层酥脆', 'is_available' => true],
        ],
        '汤羹' => [
            ['name' => '紫菜蛋花汤',     'price' => 4,   'description' => '清淡鲜美', 'is_available' => true],
            ['name' => '玉米排骨汤',     'price' => 18,  'description' => '浓郁滋补', 'is_recommend' => true, 'is_available' => true],
            ['name' => '西红柿蛋汤',     'price' => 4,   'description' => '酸甜开胃', 'is_available' => true],
            ['name' => '冬瓜丸子汤',     'price' => 12,  'description' => '清爽不腻', 'is_available' => true],
            ['name' => '酸辣汤',         'price' => 6,   'description' => '酸辣过瘾', 'is_available' => true],
        ],
        '饮品' => [
            ['name' => '柠檬水',         'price' => 3,   'description' => '鲜柠切片', 'is_available' => true],
            ['name' => '酸梅汤',         'price' => 4,   'description' => '冰镇更爽', 'is_available' => true],
            ['name' => '豆浆',           'price' => 3,   'description' => '现磨香浓', 'is_available' => true],
            ['name' => '蜂蜜柚子茶',     'price' => 6,   'description' => '清甜润喉', 'is_available' => true],
        ],
        '凉菜' => [
            ['name' => '凉拌黄瓜',       'price' => 6,   'description' => '爽脆可口', 'is_available' => true],
            ['name' => '皮蛋豆腐',       'price' => 8,   'description' => '经典凉菜', 'is_recommend' => true, 'is_available' => true],
            ['name' => '口水鸡',         'price' => 16,  'description' => '麻辣鲜嫩', 'is_available' => true],
            ['name' => '凉拌木耳',       'price' => 8,   'description' => '清爽脆嫩', 'is_available' => true],
            ['name' => '五香牛肉',       'price' => 22,  'description' => '酱香浓郁', 'is_recommend' => true, 'is_available' => true],
        ],
    ];

    $insertItem = $db->prepare(
        'INSERT IGNORE INTO menu_items (category_id, name, price, description, is_recommend, is_available)
         VALUES (:category_id, :name, :price, :description, :is_recommend, :is_available)'
    );

    $added = 0;
    foreach ($items as $catName => $dishes) {
        $catId = $catIdMap[$catName] ?? null;
        if (!$catId) continue;

        foreach ($dishes as $d) {
            // 检查是否已存在同名菜品
            $check = $db->prepare('SELECT id FROM menu_items WHERE name = :name AND category_id = :cid LIMIT 1');
            $check->execute(['name' => $d['name'], 'cid' => $catId]);
            if ($check->fetch()) continue;

            $insertItem->execute([
                'category_id'  => $catId,
                'name'         => $d['name'],
                'price'        => $d['price'],
                'description'  => $d['description'],
                'is_recommend' => $d['is_recommend'] ? 1 : 0,
                'is_available' => $d['is_available'] ? 1 : 0,
            ]);
            $added++;
        }
    }

    jsonExit([
        'success' => true,
        'message' => "🎉 导入完成！新增 {$added} 道菜品",
        'added'   => $added,
    ]);

} catch (Exception $e) {
    jsonExit(['error' => $e->getMessage()], 500);
}

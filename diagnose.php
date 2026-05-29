<?php
/**
 * 🏠 幸福小厨 简易诊断
 * 访问: http://c.xzbzq.com/diagnose.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🏠 幸福小厨 诊断</h2>";

echo "<h3>1. PHP 版本</h3>";
echo PHP_VERSION . "<br>";

echo "<h3>2. 关键文件检查</h3>";
$files = [
    '/config/database.php',
    '/config/admin.php',
    '/api/admin.php',
    '/admin/index.php',
    '/lib/Mailer.php',
];
foreach ($files as $f) {
    $path = __DIR__ . $f;
    $exists = file_exists($path);
    $size = $exists ? filesize($path) . ' bytes' : '—';
    $color = $exists ? 'green' : 'red';
    echo "<div style='color:$color'>" . ($exists ? '✅' : '❌') . " $f ($size)</div>";
}

echo "<h3>3. 数据库连接</h3>";
try {
    require __DIR__ . '/config/database.php';
    $db = getDB();
    echo "✅ 数据库连接成功<br>";
    $stmt = $db->query('SELECT COUNT(*) FROM orders');
    echo "✅ 订单条数: " . $stmt->fetchColumn() . "<br>";
} catch (Exception $e) {
    echo "❌ " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<h3>4. Session 测试</h3>";
try {
    session_start();
    $_SESSION['_diag'] = time();
    echo "✅ Session 写入正常<br>";
} catch (Exception $e) {
    echo "❌ " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<h3>5. 请在浏览器打开这个地址，看看返回什么</h3>";
echo "<a href='/api/admin.php?action=status' target='_blank'>/api/admin.php?action=status</a>";

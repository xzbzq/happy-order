<?php
/**
 * 幸福小厨 🏠 授权 API
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/License.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

try {
    $license = new License();

    switch ($action) {

        // 当前授权状态
        case 'status':
            jsonExit($license->info());
            break;

        // 激活授权
        case 'activate':
            $input = json_decode(file_get_contents('php://input'), true);
            $key = trim($input['license_key'] ?? '');
            $domain = trim($input['domain'] ?? '');

            if (!$key) {
                jsonExit(['error' => '请输入授权码'], 400);
            }
            if (!$domain) {
                $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
            }

            if ($license->save($key, $domain)) {
                jsonExit([
                    'success' => true,
                    'message' => '🎉 授权激活成功！',
                    'info'    => $license->info(),
                ]);
            } else {
                jsonExit(['error' => '授权码无效，请检查是否与域名匹配'], 400);
            }
            break;

        default:
            jsonExit(['error' => '未知操作'], 400);
    }
} catch (Exception $e) {
    jsonExit(['error' => $e->getMessage()], 500);
}

<?php
/**
 * 幸福小厨 🏠 后台管理 - 退出登录
 */
session_start();
$_SESSION['admin_logged_in'] = false;
session_destroy();
header('Location: login.php');
exit;

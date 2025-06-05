<?php

session_start();
require_once __DIR__ . '/../../config/helpers.php';

// Xóa cookie "remember me" nếu có
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/'); // Đặt thời gian hết hạn về quá khứ

    // Xóa token từ database nếu cần
    if (isset($_SESSION['user_id'])) {
        require_once __DIR__ . '/../../config/database.php';
        require_once __DIR__ . '/../../model/UserModel.php';

        $database = new Database();
        $db = $database->connect();
        $userModel = new UserModel($db);

        // Xóa tất cả token "remember me" của người dùng
    }
}

// Xóa tất cả dữ liệu phiên
$_SESSION = array();

// Hủy phiên
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

// Chuyển hướng về trang chủ
header('Location: ' . baseUrl());
exit();
?>
<?php
// Include helper functions
require_once __DIR__ . '/../../config/helpers.php';

// Đảm bảo session được khởi động
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra biến $categories
if (!isset($categories) || empty($categories)) {
    if (!class_exists('BookModel')) {
        require_once __DIR__ . '/../../model/BookModel.php';
    }
    if (!class_exists('Database')) {
        require_once __DIR__ . '/../../config/Database.php';
    }

    try {
        $database = new Database();
        $db = $database->connect(); // Đảm bảo sử dụng phương thức connect()

        if (!$db) {
            throw new Exception("Failed to connect to database");
        }

        $bookModel = new BookModel($db);
        $categories = $bookModel->getAllCategories();
    } catch (Exception $e) {
        error_log('Error in navigation.php: ' . $e->getMessage());
        $categories = []; // Đặt giá trị mặc định để tránh lỗi
    }
}

// Kiểm tra người dùng đã đăng nhập chưa
$isLoggedIn = isset($_SESSION['user_id']);

// Lấy thông tin người dùng nếu đã đăng nhập
$user = null;
if ($isLoggedIn && !isset($user)) {
    if (!class_exists('UserModel')) {
        require_once __DIR__ . '/../../model/UserModel.php';
    }

    try {
        if (!isset($database) || !isset($db)) {
            $database = new Database();
            $db = $database->connect();
        }

        $userModel = new UserModel($db);
        $user = $userModel->getUserById($_SESSION['user_id']);
    } catch (Exception $e) {
        error_log('Error getting user data: ' . $e->getMessage());
    }
}
?>
<!-- Header Navigation -->
<link rel="stylesheet" href="<?php echo viewUrl('navigation/navigation.css'); ?>">

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?php echo baseUrl(); ?>">TheBookStore</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo isCurrentUrl('') ? 'active' : ''; ?>"
                        href="<?php echo viewUrl('index.php'); ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo viewUrl('shop.php'); ?>">Shop</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo viewUrl('about.php'); ?>">About us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo viewUrl('contact.php'); ?>">Contact us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo viewUrl('blog.php'); ?>">Blog</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        Categories
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item <?php echo !isset($_GET['category']) ? 'active' : ''; ?>"
                                style="color: white;" href="<?php echo baseUrl(); ?>">All Categories</a></li>

                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <li>
                                    <a class="dropdown-item <?php echo (isset($_GET['category']) && $_GET['category'] === $category['name']) ? 'active' : ''; ?>"
                                        style="color: white;"
                                        href="<?php echo baseUrl('?category=' . urlencode($category['name'])); ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </li>
                <!-- Thêm form search vào ngay sau Categories -->
                <li class="nav-item">
                    <form class="d-flex search-form ms-2" method="GET"
                        action="<?php echo viewUrl('search_page.php'); ?>">
                        <div class="input-group">
                            <input class="form-control" type="search" name="search" placeholder="Find your book here..."
                                value="">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </li>
            </ul>

            <!-- Phần đăng nhập/đăng xuất -->
            <div class="d-flex">
                <?php if ($isLoggedIn && $user): ?>
                    <!-- Hiển thị thông tin người dùng đã đăng nhập và nút đăng xuất -->
                    <div class="nav-item dropdown user-dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown"
                            role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?php echo viewUrl('images/avatar/default-avatar.png'); ?>" alt="User Avatar"
                                class="user-avatar">
                            <span
                                class="d-none d-md-inline-block text-white"><?php echo htmlspecialchars($user['name']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li class="dropdown-header">
                                <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                <small><?php echo htmlspecialchars($user['email']); ?></small>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="<?php echo viewUrl('account/profile.php'); ?>">
                                    <i class="bi bi-person"></i> My Profile
                                </a></li>
                            <li><a class="dropdown-item" href="<?php echo viewUrl('cart.php'); ?>">
                                    <i class="bi bi-bag"></i> My Orders
                                </a></li>
                            <li><a class="dropdown-item" href="<?php echo viewUrl('account/wishlist.php'); ?>">
                                    <i class="bi bi-heart"></i> Wishlist
                                </a></li>
                            <?php if ($user['permission'] === 'admin'): ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="<?php echo viewUrl('admin/dashboard.php'); ?>">
                                        <i class="bi bi-speedometer2"></i> Admin Dashboard
                                    </a></li>
                            <?php endif; ?>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="<?php echo viewUrl('login/logout.php'); ?>">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- Hiển thị nút đăng nhập/đăng ký cho người dùng chưa đăng nhập -->
                    <a href="<?php echo viewUrl('login/login.php'); ?>" class="btn btn-outline-light me-2">Sign In</a>
                    <a href="<?php echo viewUrl('login/register.php'); ?>" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
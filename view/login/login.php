<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../model/UserModel.php';
require_once __DIR__ . '/../../config/helpers.php';

// Nếu người dùng đã đăng nhập, chuyển hướng đến trang chủ
if (isset($_SESSION['user_id'])) {
    header('Location: ' . baseUrl());
    exit;
}

$database = new Database();
$db = $database->connect();
$userModel = new UserModel($db);

$errors = [];
$username = '';

// Xử lý form đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;

    // Validate username
    if (empty($username)) {
        $errors['username'] = 'Username or email is required';
    }

    // Validate password
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }

    // Nếu không có lỗi, tiến hành đăng nhập
    if (empty($errors)) {
        $user = $userModel->login($username, $password);
        var_dump($user); // Debugging line to check user data

        if ($user) {
            // Đăng nhập thành công
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_permission'] = $user['permission'];

            // Lưu cookie nếu "Remember me" được chọn
            if ($remember) {
                $token = bin2hex(random_bytes(32)); // Tạo token ngẫu nhiên

                // Lưu token vào cookie, hết hạn sau 30 ngày
                setcookie('remember_token', $token, time() + 30 * 24 * 60 * 60, '/');

                // Lưu token vào database
                $userModel->saveRememberToken($user['id'], $token);
            }

            // Chuyển hướng đến trang chủ hoặc trang trước đó
            $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : baseUrl();
            unset($_SESSION['redirect_after_login']);

            header('Location: ' . $redirect);
            exit;
        } else {
            $errors['login'] = 'Invalid username or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TheBookStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo viewUrl('css/index.css'); ?>">
    <style>

    </style>
</head>

<body>
    <?php include __DIR__ . '/../navigation/navigation.php'; ?>

    <div class="container">
        <div class="login-container">
            <h2 class="text-center mb-4">Login to Your Account</h2>

            <?php if (isset($errors['login'])): ?>
                <div class="alert alert-danger">
                    <?php echo $errors['login']; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label">Username or Email</label>
                    <input type="text"
                        class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" id="username"
                        name="username" value="<?php echo htmlspecialchars($username); ?>">
                    <?php if (isset($errors['username'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['username']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-container">
                        <input type="password"
                            class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                            id="password" name="password">
                        <span class="password-toggle" onclick="togglePassword('password')">
                            <i class="bi bi-eye"></i>
                        </span>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>

                <div class="mb-3 text-end">
                    <a href="forgot_password.php">Forgot password?</a>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-login">Login</button>
                </div>

                <div class="text-center register-link">
                    Don't have an account? <a href="register.php">Register now</a>
                </div>

                <div class="divider">
                    <hr>
                    <span class="divider-text">OR</span>
                    <hr>
                </div>

                <div class="social-login">
                    <a href="#" class="social-btn btn-facebook">
                        <i class="bi bi-facebook"></i>
                    </a>
                    <a href="#" class="social-btn btn-google">
                        <i class="bi bi-google"></i>
                    </a>
                    <a href="#" class="social-btn btn-twitter">
                        <i class="bi bi-twitter"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../footer/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const icon = passwordInput.nextElementSibling.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    </script>
</body>

</html>
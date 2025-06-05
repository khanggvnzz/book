<?php

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../model/UserModel.php';
require_once __DIR__ . '/../../config/helpers.php';

$database = new Database();
$db = $database->connect();
$userModel = new UserModel($db);

$errors = [];
$success = false;

// Kiểm tra nếu form đã được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy và validate dữ liệu từ form
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate name
    if (empty($name)) {
        $errors['name'] = 'Name is required';
    } elseif (strlen($name) > 100) {
        $errors['name'] = 'Name cannot exceed 100 characters';
    }

    // Validate phone
    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required';
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $errors['phone'] = 'Enter a valid phone number (10-15 digits)';
    }

    // Validate address
    if (empty($address)) {
        $errors['address'] = 'Address is required';
    }

    // Validate email
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address';
    } elseif ($userModel->emailExists($email)) {
        $errors['email'] = 'This email is already registered';
    }

    // Validate username
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($username) < 4 || strlen($username) > 20) {
        $errors['username'] = 'Username must be between 4 and 20 characters';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = 'Username can only contain letters, numbers and underscores';
    } elseif ($userModel->usernameExists($username)) {
        $errors['username'] = 'This username is already taken';
    }

    // Validate password
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }

    // Validate confirm password
    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    // Nếu không có lỗi, tiến hành đăng ký
    if (empty($errors)) {
        // Mã hóa mật khẩu sử dụng SHA-256 với salt là username
        $salt = $username;
        $hashedPassword = hash('sha256', $password . $salt);

        // Thêm người dùng mới với quyền mặc định là 'user'
        $userData = [
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'permission' => 'user', // Mặc định là user thường
            'email' => $email,
            'username' => $username,
            'password' => $hashedPassword
        ];
        var_dump($userData); // Debugging line to check user data

        if ($userModel->register($userData)) {
            $success = true;
            // Xóa dữ liệu form sau khi đăng ký thành công
            $name = $phone = $address = $email = $username = '';
        } else {
            $errors['general'] = 'Registration failed. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - TheBook.PK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="register.css">
</head>

<body>
    <?php include __DIR__ . '/../navigation/navigation.php'; ?>

    <div class="container">
        <div class="register-container">
            <h2 class="text-center mb-4">Create an Account</h2>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <p>Registration successful! You can now <a href="login.php">log in</a> to your account.</p>
                </div>
            <?php endif; ?>

            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger">
                    <p><?php echo $errors['general']; ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text"
                            class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" id="name"
                            name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>">
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel"
                            class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" id="phone"
                            name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                        <?php if (isset($errors['phone'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control <?php echo isset($errors['address']) ? 'is-invalid' : ''; ?>"
                        id="address" name="address" rows="2"><?php echo htmlspecialchars($address ?? ''); ?></textarea>
                    <?php if (isset($errors['address'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['address']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                        id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text"
                        class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" id="username"
                        name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>">
                    <?php if (isset($errors['username'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['username']; ?></div>
                    <?php endif; ?>
                    <div class="form-text">Choose a username between 4-20 characters. Only letters, numbers and
                        underscores.</div>
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
                    <div class="form-text">Password must be at least 8 characters long.</div>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="password-container">
                        <input type="password"
                            class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                            id="confirm_password" name="confirm_password">
                        <span class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="bi bi-eye"></i>
                        </span>
                    </div>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['confirm_password']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-register">Create Account</button>
                </div>

                <div class="text-center login-link">
                    Already have an account? <a href="login.php">Log in</a>
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
<?php

class UserModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Đăng ký người dùng mới
     * 
     * @param array $userData Dữ liệu người dùng (name, phone, address, email, permission, username, password)
     * @return bool Trả về true nếu đăng ký thành công, false nếu thất bại
     */
    public function register($userData)
    {
        try {
            $query = "INSERT INTO users (name, phone, address, permission, email, username, password) 
                     VALUES (:name, :phone, :address, :permission, :email, :username, :password)";

            $stmt = $this->db->prepare($query);

            // Bind các tham số
            $stmt->bindParam(':name', $userData['name']);
            $stmt->bindParam(':phone', $userData['phone']);
            $stmt->bindParam(':address', $userData['address']);
            $stmt->bindParam(':permission', $userData['permission']);
            $stmt->bindParam(':email', $userData['email']);
            $stmt->bindParam(':username', $userData['username']);
            $stmt->bindParam(':password', $userData['password']);

            // Thực thi câu lệnh
            return $stmt->execute();
        } catch (PDOException $e) {
            var_dump($e->getMessage()); // Debugging line to check error
            error_log("Register error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kiểm tra xem email đã tồn tại chưa
     * 
     * @param string $email Email cần kiểm tra
     * @return bool Trả về true nếu email đã tồn tại, false nếu chưa
     */
    public function emailExists($email)
    {
        try {
            $query = "SELECT COUNT(*) FROM users WHERE email = :email";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Email check error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kiểm tra xem username đã tồn tại chưa
     * 
     * @param string $username Username cần kiểm tra
     * @return bool Trả về true nếu username đã tồn tại, false nếu chưa
     */
    public function usernameExists($username)
    {
        try {
            $query = "SELECT COUNT(*) FROM users WHERE username = :username";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Username check error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Đăng nhập
     * 
     * @param string $username Username hoặc email
     * @param string $password Mật khẩu chưa mã hóa
     * @return array|false Trả về thông tin người dùng nếu đăng nhập thành công, false nếu thất bại
     */
    public function login($username, $password)
    {
        try {
            // Kiểm tra xem $username là email hay username
            $query = "SELECT * FROM users WHERE username = :username OR email = :email LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $username);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Mã hóa password nhập vào với salt là username
                $salt = $user['username'];
                $hashedPassword = hash('sha256', $password . $salt);

                var_dump($hashedPassword); // Debugging line to check hashed password
                // So sánh với password trong database
                if ($hashedPassword === $user['password']) {
                    // Xóa password trước khi trả về thông tin user
                    unset($user['password']);
                    return $user;
                }
            }

            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy thông tin người dùng theo ID
     * 
     * @param int $userId ID của người dùng
     * @return array|false Trả về thông tin người dùng nếu tìm thấy, false nếu không
     */
    public function getUserById($userId)
    {
        try {
            $query = "SELECT id, name, phone, address, permission, email, username FROM users WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cập nhật thông tin người dùng
     * 
     * @param int $userId ID của người dùng
     * @param array $userData Dữ liệu cần cập nhật
     * @return bool Trả về true nếu cập nhật thành công, false nếu thất bại
     */
    public function updateUser($userId, $userData)
    {
        try {
            $updateFields = [];
            $params = [':id' => $userId];

            // Xây dựng câu lệnh SQL động dựa trên dữ liệu cần cập nhật
            foreach ($userData as $key => $value) {
                if ($key !== 'id') {
                    $updateFields[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
            }

            if (empty($updateFields)) {
                return false; // Không có dữ liệu cần cập nhật
            }

            $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($query);

            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Update user error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Thay đổi mật khẩu người dùng
     * 
     * @param int $userId ID của người dùng
     * @param string $newPassword Mật khẩu mới chưa mã hóa
     * @return bool Trả về true nếu thay đổi thành công, false nếu thất bại
     */
    public function changePassword($userId, $newPassword)
    {
        try {
            // Lấy username để tạo salt
            $query = "SELECT username FROM users WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return false;
            }

            // Mã hóa mật khẩu mới
            $salt = $user['username'];
            $hashedPassword = hash('sha256', $newPassword . $salt);

            // Cập nhật mật khẩu
            $query = "UPDATE users SET password = :password WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':id', $userId);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Change password error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lưu token "Remember Me" cho người dùng
     * 
     * @param int $userId ID của người dùng
     * @param string $token Token ngẫu nhiên
     * @return bool Trả về true nếu lưu thành công, false nếu thất bại
     */
    public function saveRememberToken($userId, $token)
    {
        try {
            // Xóa token cũ nếu có
            $query = "DELETE FROM user_tokens WHERE user_id = :user_id AND token_type = 'remember'";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();

            // Thêm token mới
            $query = "INSERT INTO user_tokens (user_id, token, token_type, expires_at) 
                     VALUES (:user_id, :token, 'remember', DATE_ADD(NOW(), INTERVAL 30 DAY))";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':token', $token);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Save remember token error: " . $e->getMessage());
            return false;
        }
    }
}
?>
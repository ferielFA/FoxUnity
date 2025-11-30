<?php
require_once __DIR__ . '/config.php';

class User {
    private ?int $id = null;
    private ?string $username = null;
    private ?string $email = null;
    private ?string $dob = null;
    private ?string $password = null;
    private ?string $gender = null;
    private ?string $role = null;
    private ?string $status = null;
    private ?string $image = null;

    // Constructor
    public function __construct(?int $id = null, ?string $username = null, ?string $email = null, 
                                ?string $dob = null, ?string $password = null, ?string $gender = null,
                                ?string $role = 'Gamer', ?string $status = 'active', ?string $image = null) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->dob = $dob;
        $this->password = $password;
        $this->gender = $gender;
        $this->role = $role;
        $this->status = $status;
        $this->image = $image;
    }

    // Getters
    public function getId(): ?int {
        return $this->id;
    }

    public function getUsername(): ?string {
        return $this->username;
    }

    public function getEmail(): ?string {
        return $this->email;
    }

    public function getDob(): ?string {
        return $this->dob;
    }

    public function getPassword(): ?string {
        return $this->password;
    }

    public function getGender(): ?string {
        return $this->gender;
    }

    public function getRole(): ?string {
        return $this->role;
    }

    public function getStatus(): ?string {
        return $this->status;
    }

    public function getImage(): ?string {
        return $this->image;
    }

    // Setters
    public function setId(?int $id): void {
        $this->id = $id;
    }

    public function setUsername(?string $username): void {
        $this->username = $username;
    }

    public function setEmail(?string $email): void {
        $this->email = $email;
    }

    public function setDob(?string $dob): void {
        $this->dob = $dob;
    }

    public function setPassword(?string $password): void {
        $this->password = $password;
    }

    public function setGender(?string $gender): void {
        $this->gender = $gender;
    }

    public function setRole(?string $role): void {
        $this->role = $role;
    }

    public function setStatus(?string $status): void {
        $this->status = $status;
    }

    public function setImage(?string $image): void {
        $this->image = $image;
    }

    // Create user in database
    public function create(): bool {
        try {
            $pdo = getDB();
            $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (username, email, dob, password, gender, role, status, image) 
                    VALUES (:username, :email, :dob, :password, :gender, :role, :status, :image)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':username' => $this->username,
                ':email' => $this->email,
                ':dob' => $this->dob,
                ':password' => $hashedPassword,
                ':gender' => $this->gender,
                ':role' => $this->role,
                ':status' => $this->status,
                ':image' => $this->image
            ]);
            
            $this->id = $pdo->lastInsertId();
            return true;
        } catch (Exception $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }

    // Read user by ID
    public static function getById(int $id): ?User {
        try {
            $pdo = getDB();
            $sql = "SELECT * FROM users WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $userData = $stmt->fetch();
            
            if ($userData) {
                return new User(
                    $userData['id'],
                    $userData['username'],
                    $userData['email'],
                    $userData['dob'],
                    $userData['password'],
                    $userData['gender'],
                    $userData['role'],
                    $userData['status'],
                    $userData['image']
                );
            }
            return null;
        } catch (Exception $e) {
            error_log("Error fetching user by ID: " . $e->getMessage());
            return null;
        }
    }

    // Read user by email
    public static function getByEmail(string $email): ?User {
        try {
            $pdo = getDB();
            $sql = "SELECT * FROM users WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email]);
            
            $userData = $stmt->fetch();
            
            if ($userData) {
                return new User(
                    $userData['id'],
                    $userData['username'],
                    $userData['email'],
                    $userData['dob'],
                    $userData['password'],
                    $userData['gender'],
                    $userData['role'],
                    $userData['status'],
                    $userData['image']
                );
            }
            return null;
        } catch (Exception $e) {
            error_log("Error fetching user by email: " . $e->getMessage());
            return null;
        }
    }

    // Get all users
    public static function getAll(): array {
        try {
            $pdo = getDB();
            $sql = "SELECT * FROM users ORDER BY id DESC";
            $stmt = $pdo->query($sql);
            
            $users = [];
            while ($userData = $stmt->fetch()) {
                $users[] = new User(
                    $userData['id'],
                    $userData['username'],
                    $userData['email'],
                    $userData['dob'],
                    $userData['password'],
                    $userData['gender'],
                    $userData['role'],
                    $userData['status'],
                    $userData['image']
                );
            }
            return $users;
        } catch (Exception $e) {
            error_log("Error fetching all users: " . $e->getMessage());
            return [];
        }
    }

    // Update user
    public function update(): bool {
        try {
            $pdo = getDB();
            
            $sql = "UPDATE users 
                    SET username = :username, 
                        email = :email, 
                        dob = :dob, 
                        gender = :gender,
                        role = :role, 
                        status = :status, 
                        image = :image 
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                ':id' => $this->id,
                ':username' => $this->username,
                ':email' => $this->email,
                ':dob' => $this->dob,
                ':gender' => $this->gender,
                ':role' => $this->role,
                ':status' => $this->status,
                ':image' => $this->image
            ]);
        } catch (Exception $e) {
            error_log("Error updating user: " . $e->getMessage());
            return false;
        }
    }

    // Update password
    public function updatePassword(string $newPassword): bool {
        try {
            $pdo = getDB();
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $sql = "UPDATE users SET password = :password WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                ':password' => $hashedPassword,
                ':id' => $this->id
            ]);
        } catch (Exception $e) {
            error_log("Error updating password: " . $e->getMessage());
            return false;
        }
    }

    // Delete user
    public function delete(): bool {
        try {
            $pdo = getDB();
            $sql = "DELETE FROM users WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([':id' => $this->id]);
        } catch (Exception $e) {
            error_log("Error deleting user: " . $e->getMessage());
            return false;
        }
    }

    // Verify password
    public function verifyPassword(string $password): bool {
        return password_verify($password, $this->password);
    }

    // Check if email exists
    public static function emailExists(string $email): bool {
        try {
            $pdo = getDB();
            $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error checking email existence: " . $e->getMessage());
            return false;
        }
    }

    // Check if username exists
    public static function usernameExists(string $username): bool {
        try {
            $pdo = getDB();
            $sql = "SELECT COUNT(*) FROM users WHERE username = :username";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':username' => $username]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error checking username existence: " . $e->getMessage());
            return false;
        }
    }
    
    // ✅ EMAIL VERIFICATION - Save verification token
    public static function saveVerificationToken(int $userId, string $token, string $expires): bool {
        try {
            $pdo = getDB();
            
            // Check if table exists, if not create it
            $createTable = "CREATE TABLE IF NOT EXISTS email_verifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(64) NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            $pdo->exec($createTable);
            
            // Delete any existing tokens for this user
            $deleteSql = "DELETE FROM email_verifications WHERE user_id = :user_id";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute([':user_id' => $userId]);
            
            // Insert new token
            $sql = "INSERT INTO email_verifications (user_id, token, expires_at) 
                    VALUES (:user_id, :token, :expires)";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                ':user_id' => $userId,
                ':token' => $token,
                ':expires' => $expires
            ]);
        } catch (Exception $e) {
            error_log("Error saving verification token: " . $e->getMessage());
            return false;
        }
    }
    
    // ✅ EMAIL VERIFICATION - Verify token and activate account
    public static function verifyEmailToken(string $token): bool {
        try {
            $pdo = getDB();
            
            // Find token
            $sql = "SELECT user_id, expires_at FROM email_verifications 
                    WHERE token = :token";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':token' => $token]);
            $verification = $stmt->fetch();
            
            if (!$verification) {
                return false; // Token not found
            }
            
            // Check if token expired
            $expiresAt = new DateTime($verification['expires_at']);
            $now = new DateTime();
            
            if ($now > $expiresAt) {
                return false; // Token expired
            }
            
            // Activate user account
            $updateSql = "UPDATE users SET status = 'active' WHERE id = :id";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([':id' => $verification['user_id']]);
            
            // Delete used token
            $deleteSql = "DELETE FROM email_verifications WHERE token = :token";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute([':token' => $token]);
            
            return true;
        } catch (Exception $e) {
            error_log("Error verifying email token: " . $e->getMessage());
            return false;
        }
    }
    
    // ✅ FORGOT PASSWORD - Save password reset token
    public static function savePasswordResetToken(int $userId, string $token, string $expires): bool {
        try {
            $pdo = getDB();
            
            // Check if table exists, if not create it
            $createTable = "CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(64) NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            $pdo->exec($createTable);
            
            // Delete any existing tokens for this user
            $deleteSql = "DELETE FROM password_resets WHERE user_id = :user_id";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute([':user_id' => $userId]);
            
            // Insert new token
            $sql = "INSERT INTO password_resets (user_id, token, expires_at) 
                    VALUES (:user_id, :token, :expires)";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                ':user_id' => $userId,
                ':token' => $token,
                ':expires' => $expires
            ]);
        } catch (Exception $e) {
            error_log("Error saving password reset token: " . $e->getMessage());
            return false;
        }
    }
    
    // ✅ FORGOT PASSWORD - Get password reset token data
    public static function getPasswordResetToken(string $token): ?array {
        try {
            $pdo = getDB();
            
            $sql = "SELECT user_id, expires_at FROM password_resets 
                    WHERE token = :token";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':token' => $token]);
            $result = $stmt->fetch();
            
            if (!$result) {
                return null;
            }
            
            // Check if token expired
            $expiresAt = new DateTime($result['expires_at']);
            $now = new DateTime();
            
            if ($now > $expiresAt) {
                return null; // Token expired
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error getting password reset token: " . $e->getMessage());
            return null;
        }
    }
    
    // ✅ FORGOT PASSWORD - Reset password with token
    public static function resetPasswordWithToken(string $token, string $newPassword): bool {
        try {
            $pdo = getDB();
            
            // Get token data
            $tokenData = self::getPasswordResetToken($token);
            
            if (!$tokenData) {
                return false; // Token invalid or expired
            }
            
            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $updateSql = "UPDATE users SET password = :password WHERE id = :id";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                ':password' => $hashedPassword,
                ':id' => $tokenData['user_id']
            ]);
            
            // Delete used token
            $deleteSql = "DELETE FROM password_resets WHERE token = :token";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute([':token' => $token]);
            
            return true;
        } catch (Exception $e) {
            error_log("Error resetting password: " . $e->getMessage());
            return false;
        }
    }
    
    // ✅ GOOGLE LOGIN - Get user by Google ID
    public static function getByGoogleId($googleId) {
        try {
            $pdo = getDB();
            
            $sql = "SELECT * FROM users WHERE google_id = :google_id LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':google_id' => $googleId]);
            $userData = $stmt->fetch();
            
            if (!$userData) {
                return null;
            }
            
            $user = new User(
                $userData['id'],
                $userData['username'],
                $userData['email'],
                $userData['dob'],
                $userData['password'],
                $userData['gender'],
                $userData['role'],
                $userData['status'],
                $userData['image']
            );
            
            return $user;
        } catch (Exception $e) {
            error_log("Error getting user by Google ID: " . $e->getMessage());
            return null;
        }
    }
    
    // ✅ GOOGLE LOGIN - Link Google account to existing user
    public static function linkGoogleAccount($userId, $googleId, $imageUrl = null) {
        try {
            $pdo = getDB();
            
            // Only update google_id, not the image
            $sql = "UPDATE users 
                    SET google_id = :google_id 
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                ':google_id' => $googleId,
                ':id' => $userId
            ]);
        } catch (Exception $e) {
            error_log("Error linking Google account: " . $e->getMessage());
            return false;
        }
    }
    
    // ✅ GOOGLE LOGIN - Create user with Google
    public static function createGoogleUser($googleId, $email, $username, $imageUrl = null) {
        try {
            $pdo = getDB();
            
            // Generate random password (user won't use it)
            $randomPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
            
            // Don't set image - let user upload their own
            $sql = "INSERT INTO users 
                    (username, email, password, google_id, status, role) 
                    VALUES 
                    (:username, :email, :password, :google_id, 'active', 'Gamer')";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $randomPassword,
                ':google_id' => $googleId
            ]);
            
            return (int) $pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("Error creating Google user: " . $e->getMessage());
            return null;
        }
    }
    
    // ✅ GOOGLE LOGIN - Get user by username
    public static function getByUsername($username) {
        try {
            $pdo = getDB();
            
            $sql = "SELECT * FROM users WHERE username = :username LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':username' => $username]);
            $userData = $stmt->fetch();
            
            if (!$userData) {
                return null;
            }
            
            $user = new User(
                $userData['id'],
                $userData['username'],
                $userData['email'],
                $userData['dob'],
                $userData['password'],
                $userData['gender'],
                $userData['role'],
                $userData['status'],
                $userData['image']
            );
            
            return $user;
        } catch (Exception $e) {
            error_log("Error getting user by username: " . $e->getMessage());
            return null;
        }
    }
}
?>
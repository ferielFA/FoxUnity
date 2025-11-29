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
                    SET username = :username, email = :email, dob = :dob, 
                        role = :role, status = :status, image = :image 
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                ':id' => $this->id,
                ':username' => $this->username,
                ':email' => $this->email,
                ':dob' => $this->dob,
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
}
?>
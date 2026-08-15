<?php
/**
 * User.php
 * Base class for both Administrator and Client (Type field in DB
 * distinguishes them). Handles: register, login, logout, and
 * basic CRUD/lookup shared by both user types.
 */
class User
{
    protected PDO $db;

    public ?int $userId = null;
    public ?string $name = null;
    public ?string $phone = null;
    public ?string $email = null;
    public ?string $userType = null;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Register a new user (admin or client).
     * Returns ['success'=>bool, 'errors'=>array]
     */
    public function register(string $name, string $phone, string $email, string $password, string $userType): array
    {
        $errors = [];

        if (!Validator::required($name) || !Validator::minLength($name, 2)) {
            $errors[] = "Please enter a valid name (at least 2 characters).";
        }
        if (!Validator::isPhone($phone)) {
            $errors[] = "Please enter a valid phone number.";
        }
        if (!Validator::isEmail($email)) {
            $errors[] = "Please enter a valid email address.";
        }
        if (!Validator::minLength($password, 6)) {
            $errors[] = "Password must be at least 6 characters long.";
        }
        if (!in_array($userType, ['admin', 'client'], true)) {
            $errors[] = "Invalid user type.";
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Check for duplicate email
        $stmt = $this->db->prepare("SELECT user_id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'errors' => ["An account with this email already exists."]];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            "INSERT INTO users (name, phone, email, password_hash, user_type)
             VALUES (:name, :phone, :email, :hash, :type)"
        );
        $stmt->execute([
            ':name'  => $name,
            ':phone' => $phone,
            ':email' => $email,
            ':hash'  => $hash,
            ':type'  => $userType,
        ]);

        return ['success' => true, 'errors' => [], 'user_id' => (int)$this->db->lastInsertId()];
    }

    /**
     * Attempt login. Returns user array on success, or null on failure.
     */
    public function login(string $email, string $password): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();

        if ($row && password_verify($password, $row['password_hash'])) {
            return $row;
        }
        return null;
    }

    /** Static helper: is someone currently logged in? */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function isAdmin(): bool
    {
        return self::isLoggedIn() && $_SESSION['user_type'] === 'admin';
    }

    public static function isClient(): bool
    {
        return self::isLoggedIn() && $_SESSION['user_type'] === 'client';
    }

    /** Redirect helper used on pages that require login/role */
    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header("Location: " . BASE_URL . "auth/login.php");
            exit;
        }
    }

    public static function requireRole(string $role): void
    {
        self::requireLogin();
        if ($_SESSION['user_type'] !== $role) {
            header("Location: " . BASE_URL . "index.php");
            exit;
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
    }

    /** Get a single user by ID */
    public function getById(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = :id");
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}

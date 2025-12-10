<?php

if (!defined('DB_CONFIG')) {
    define('DB_CONFIG', true);
}


define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'integration');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');


define('BASE_PATH', dirname(__DIR__) . '/');
define('MODEL_PATH', BASE_PATH . 'model/');
define('VIEW_PATH', BASE_PATH . 'view/');
define('CONTROLLER_PATH', BASE_PATH . 'controller/');
define('FRONT_PATH', VIEW_PATH . 'front/');
define('BACK_PATH', VIEW_PATH . 'back/');
define('IMAGES_PATH', BASE_PATH . 'images/');


define('BASE_URL', 'http://localhost/foxunity/');
define('IMAGES_URL', BASE_URL . 'images/');


// Only declare Database class if it doesn't already exist
if (!class_exists('Database')) {
    class Database {
        private static $instance = null;
        private $connection;
        

        private function __construct() {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
                ];
                
                $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
                
            } catch (PDOException $e) {

                error_log("Database Connection Error: " . $e->getMessage());
                die("Database connection failed. Please check your configuration.");
            }
        }
        
        /**
         * Get singleton instance of Database
         * 
         * @return Database
         */
        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        /**
         * Get PDO connection
         * 
         * @return PDO
         */
        public function getConnection() {
            return $this->connection;
        }
        
        /**
         * Prevent cloning of the instance
         */
        private function __clone() {}
        
        /**
         * Prevent unserialization of the instance
         */
        public function __wakeup() {
            throw new Exception("Cannot unserialize singleton");
        }
    }
} // End of class_exists check

/**
 *
 * 
 * @return PDO
 */
function getDB() {
    return Database::getInstance()->getConnection();
}

/**
 * 
 * 
 * @param mixed $data
 * @param bool $die
 */
function debug($data, $die = false) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    if ($die) die();
}

/**
 * 
 * 
 * @param string $data
 * @return string
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * 
 * 
 * @param string $path
 */
function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit();
}

/**
 * Check if user is logged in
 * 
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * 
 * 
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
}

/**
 * 
 * 
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current logged-in username from session
 * 
 * @return string|null
 */
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

/**
 * Get current logged-in user data from database
 * 
 * @return array|null Returns user data array or null if not found
 */
function getCurrentUserFromDB() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $userId = getCurrentUserId();
    if ($userId === null) {
        return null;
    }
    
    try {
        require_once __DIR__ . '/UserModel.php';
        $userModel = new UserModel();
        return $userModel->getUserById($userId);
    } catch (Exception $e) {
        error_log("getCurrentUserFromDB error: " . $e->getMessage());
        return null;
    }
}

/**
 * Verify current session user is linked to database
 * 
 * @return bool
 */
function verifyCurrentUserLinked() {
    if (!isLoggedIn()) {
        return false;
    }
    
    try {
        require_once __DIR__ . '/UserModel.php';
        $userModel = new UserModel();
        return $userModel->verifySessionUserLinked();
    } catch (Exception $e) {
        error_log("verifyCurrentUserLinked error: " . $e->getMessage());
        return false;
    }
}

/**
 * Set flash message
 * 
 * @param string $type (success, error, info, warning)
 * @param string $message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * 
 * 
 * @return array|null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


date_default_timezone_set('Africa/Tunis');


error_reporting(E_ALL);
ini_set('display_errors', 1);



?>
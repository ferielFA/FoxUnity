<?php
// Database connection for FoxUnity project

$dsn = 'mysql:host=127.0.0.1;dbname=foxunity0;charset=utf8mb4';
$user = 'root'; // change if you use another user
$pass = '';     // change if you have a password

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
?>
<?php
// Database connection for FoxUnity project

$dsn = 'mysql:host=127.0.0.1;dbname=foxunity0;charset=utf8mb4';
$user = 'root'; // change if you use another user
$pass = '';     // change if you have a password

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
?>

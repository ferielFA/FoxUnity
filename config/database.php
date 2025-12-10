<?php

class Database {
    private static $host = 'localhost';
    private static $dbname = 'integration';
    private static $username = 'root';
    private static $password = '';
    private static $connection = null;
    private static $instance = null;

    public static function getConnection(): PDO {
        if (self::$connection === null) {
            try {
                $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=utf8mb4";
                self::$connection = new PDO($dsn, self::$username, self::$password);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die("Erreur de connexion : " . $e->getMessage());
            }
        }
        return self::$connection;
    }

    // Add getInstance() for compatibility with model/config.php
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Make constructor private for singleton pattern
    private function __construct() {
        // Initialize connection
        self::getConnection();
    }
}
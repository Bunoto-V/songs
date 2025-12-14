<?php
// config/database.php

define('DB_HOST', 'localhost');
define('DB_USER', 'adm_tkc');
define('DB_PASS', 'a1367$1661va');
define('DB_NAME', 'social_management');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // בדיקת חיבור
    $pdo->query("SELECT 1");
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
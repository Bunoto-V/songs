<?php
// create_admin.php - הנח קובץ זה בתיקיית הפרויקט הראשית
require_once '../config/database.php';

try {
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetch()) {
        die('משתמש מנהל כבר קיים במערכת');
    }

    // Create roles table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role_name VARCHAR(50) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Create users table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            role_id INT DEFAULT 1,
            is_active BOOLEAN DEFAULT TRUE,
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Insert admin role if not exists
    $pdo->exec("INSERT IGNORE INTO roles (id, role_name, description) VALUES (1, 'admin', 'מנהל מערכת - גישה מלאה')");

    // Create admin user
    $username = 'admin';
    $password = '123456'; // סיסמה פשוטה לבדיקה
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $email = 'admin@example.com';

    $stmt = $pdo->prepare("
        INSERT INTO users (username, password, email, role_id, is_active)
        VALUES (?, ?, ?, 1, 1)
    ");

    $stmt->execute([$username, $hashedPassword, $email]);

    echo "משתמש מנהל נוצר בהצלחה!<br>";
    echo "שם משתמש: admin<br>";
    echo "סיסמה: 123456<br>";
    echo "אימייל: admin@example.com<br>";

} catch (PDOException $e) {
    die("שגיאה: " . $e->getMessage());
}
?>
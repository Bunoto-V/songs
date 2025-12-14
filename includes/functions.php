<?php
// includes/functions.php

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}


function requireLogin() {
    if (!isLoggedIn()) {
        // Get current URL
        $currentUrl = $_SERVER['REQUEST_URI'];
        // Redirect to login with return URL
        redirectTo('../public/login.php?return_url=' . urlencode($currentUrl));
    }
}

// הגדרת מערך הרשאות גלובלי
$GLOBALS['userPermissions'] = [
    'admin' => ['manage_users', 'manage_clients', 'manage_categories', 'manage_songs'],
    'manager' => ['manage_clients', 'manage_categories', 'manage_songs'],
    'user' => ['view_songs']
];

function hasPermission($permission) {
    global $pdo;

    if (!isLoggedIn()) {
        return false;
    }

    // Get user's role and permissions
    $stmt = $pdo->prepare("
        SELECT r.role_name, rp.permission_id
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        LEFT JOIN role_permissions rp ON r.id = rp.role_id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $rolePermissions = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);

    // Check for direct user permissions
    $stmt = $pdo->prepare("
        SELECT up.permission_id
        FROM user_permissions up
        WHERE up.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $userPermissions = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    // Merge permissions and check
    $allPermissions = array_merge($rolePermissions, $userPermissions);
    return in_array($permission, $allPermissions);
}

function requirePermission($permission) {
    // אם אין פונקציה קיימת, כאן תוכל לבדוק הרשאות ידנית או להדפיס הודעת שגיאה מותאמת
    if (!isset($_SESSION['user_permissions']) || !in_array($permission, $_SESSION['user_permissions'])) {
        die('אין לך הרשאה לגשת לדף זה.');
    }
}


function redirectTo($path) {
    header("Location: $path");
    exit;
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function uploadFile($file, $targetDir, $allowedTypes = ['jpg', 'jpeg', 'png'], $maxSize = 100485760) {
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = basename($file['name']);
    $targetPath = $targetDir . generateUniqueFileName($fileName);
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Check if file type is allowed
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('סוג הקובץ אינו נתמך. קבצים מותרים: ' . implode(', ', $allowedTypes));
    }

    // Check file size
    if ($file['size'] > $maxSize) {
        $maxSizeMB = $maxSize / (1024 * 1024);
        throw new Exception('הקובץ גדול מדי. גודל מקסימלי מותר: ' . $maxSizeMB . 'MB');
    }

    // Upload file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // החזרת נתיב יחסי בפורמט הנכון
        return str_replace($_SERVER['DOCUMENT_ROOT'], '', $targetPath);
    }
    
    throw new Exception('אירעה שגיאה בהעלאת הקובץ');
}

function generateUniqueFileName($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $extension;
}

function getCurrentUser() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            r.role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

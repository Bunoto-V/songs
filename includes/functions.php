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

// הגדרת מערך הרשאות גלובלי לפי תפקיד
$GLOBALS['rolePermissions'] = [
    'admin' => ['manage_users', 'manage_clients', 'manage_categories', 'manage_songs', 'manage_logos', 'manage_plugins', 'manage_stories'],
    'editor' => ['manage_clients', 'manage_categories', 'manage_songs', 'manage_logos', 'manage_plugins', 'manage_stories'],
    'viewer' => ['view_songs', 'view_logos', 'view_plugins']
];

function hasPermission($permission) {
    global $pdo;

    if (!isLoggedIn()) {
        return false;
    }

    try {
        // Get user's role
        $stmt = $pdo->prepare("
            SELECT r.role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        
        if (!$result || !$result['role_name']) {
            return false;
        }
        
        $roleName = $result['role_name'];
        
        // Check if role has permission
        if (isset($GLOBALS['rolePermissions'][$roleName])) {
            return in_array($permission, $GLOBALS['rolePermissions'][$roleName]);
        }
        
        // Admin has all permissions
        if ($roleName === 'admin') {
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Permission check error: " . $e->getMessage());
        return false;
    }
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

/**
 * יצירת קובץ ZIP של תמונות מילים לשיר
 * @param int $song_id מזהה השיר
 * @return string|false נתיב לקובץ ZIP שנוצר או false במקרה של כישלון
 */
function createSongImagesZip($song_id) {
    global $pdo;
    
    try {
        // Get song details
        $stmt = $pdo->prepare("SELECT title, title_he, title_en FROM songs WHERE id = ?");
        $stmt->execute([$song_id]);
        $song = $stmt->fetch();
        
        if (!$song) {
            return false;
        }
        
        // Get all images for this song
        $stmt = $pdo->prepare("
            SELECT image_path, page_number 
            FROM song_images 
            WHERE song_id = ? AND image_type = 'lyrics_page'
            ORDER BY page_number ASC, display_order ASC
        ");
        $stmt->execute([$song_id]);
        $images = $stmt->fetchAll();
        
        if (empty($images)) {
            return false;
        }
        
        // Create ZIP file
        $zip = new ZipArchive();
        $song_title = $song['title_he'] ?? $song['title_en'] ?? $song['title'];
        $zip_filename = 'song_lyrics_' . $song_id . '_' . time() . '.zip';
        $zip_path = __DIR__ . '/../public/uploads/temp/' . $zip_filename;
        
        // Ensure temp directory exists
        if (!is_dir(dirname($zip_path))) {
            mkdir(dirname($zip_path), 0755, true);
        }
        
        if ($zip->open($zip_path, ZipArchive::CREATE) !== TRUE) {
            return false;
        }
        
        // Add images to ZIP
        foreach ($images as $index => $image) {
            $image_path = __DIR__ . '/../public' . $image['image_path'];
            if (file_exists($image_path)) {
                $page_num = $image['page_number'] ?? ($index + 1);
                $extension = pathinfo($image_path, PATHINFO_EXTENSION);
                $zip_image_name = sprintf("%s_page_%02d.%s", $song_title, $page_num, $extension);
                $zip->addFile($image_path, $zip_image_name);
            }
        }
        
        $zip->close();
        
        return '/uploads/temp/' . $zip_filename;
        
    } catch (Exception $e) {
        error_log('Error creating ZIP: ' . $e->getMessage());
        return false;
    }
}

/**
 * ניקוי קבצי ZIP ישנים (מעל 24 שעות)
 */
function cleanOldZipFiles() {
    $temp_dir = __DIR__ . '/../public/uploads/temp/';
    if (!is_dir($temp_dir)) {
        return;
    }
    
    $files = glob($temp_dir . '*.zip');
    $now = time();
    
    foreach ($files as $file) {
        if (is_file($file)) {
            if ($now - filemtime($file) >= 24 * 3600) { // 24 hours
                unlink($file);
            }
        }
    }
}

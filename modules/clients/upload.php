<?php
// modules/clients/upload.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

try {
    if (!isset($_FILES['logo'])) {
        throw new Exception('לא נשלח קובץ');
    }

    $file = $_FILES['logo'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('שגיאה בהעלאת הקובץ: ' . $file['error']);
    }

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('סוג קובץ לא נתמך. יש להעלות רק קבצי PNG או JPEG');
    }

    // Validate file size (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('הקובץ גדול מדי. גודל מקסימלי הוא 5MB');
    }

    // Create upload directory if it doesn't exist
    $uploadDir = '../../public/uploads/logos/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('שגיאה בשמירת הקובץ');
    }

    // Return success with relative path
    echo json_encode([
        'success' => true,
        'filePath' => '/uploads/logos/' . $filename
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
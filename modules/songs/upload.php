<?php

// modules/songs/upload.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

try {
    if (!isset($_FILES['song'])) {
        throw new Exception('לא נשלח קובץ');
    }

    $file = $_FILES['song'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('שגיאה בהעלאת הקובץ: ' . $file['error']);
    }

    // Validate file type
    $allowedTypes = ['audio/mpeg', 'audio/wav', 'audio/x-wav'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('סוג קובץ לא נתמך. יש להעלות רק קבצי MP3 או WAV');
    }

    // Validate file size (50MB)
    if ($file['size'] > 50 * 1024 * 1024) {
        throw new Exception('הקובץ גדול מדי. גודל מקסימלי הוא 50MB');
    }

    // Create upload directory if it doesn't exist
    $uploadDir = '../../public/uploads/songs/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('שגיאה בשמירת הקובץ');
    }

    // Return success with relative path
    echo json_encode([
        'success' => true,
        'filePath' => '/uploads/songs/' . $filename,
        'originalName' => $file['name']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

?>

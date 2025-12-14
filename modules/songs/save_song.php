<?php
// modules/songs/save_song.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

try {
    // Validate input
    if (empty($_POST['title'])) {
        throw new Exception('שם השיר הוא שדה חובה');
    }
    if (empty($_POST['category_id'])) {
        throw new Exception('יש לבחור קטגוריה');
    }
    if (empty($_POST['file_path'])) {
        throw new Exception('נתיב הקובץ חסר');
    }

    $title = sanitizeInput($_POST['title']);
    $categoryId = filter_var($_POST['category_id'], FILTER_VALIDATE_INT);
    $filePath = $_POST['file_path'];

    // Validate that the song file exists
    if (!file_exists('../../public/' . $filePath)) {
        throw new Exception('קובץ השיר לא נמצא');
    }

    // Check if category exists
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    if (!$stmt->fetch()) {
        throw new Exception('הקטגוריה שנבחרה אינה קיימת');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Insert new song
    $stmt = $pdo->prepare("
        INSERT INTO songs (
            title,
            category_id,
            file_path,
            created_at
        ) VALUES (?, ?, ?, NOW())
    ");
    
    if (!$stmt->execute([$title, $categoryId, $filePath])) {
        throw new Exception('שגיאה בשמירת השיר בבסיס הנתונים');
    }

    $songId = $pdo->lastInsertId();

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'השיר נשמר בהצלחה',
        'songId' => $songId
    ]);

} catch (Exception $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // If there was an error, try to delete the uploaded file
    if (isset($filePath) && file_exists('../../public/' . $filePath)) {
        @unlink('../../public/' . $filePath);
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage() // מחזיר את הודעת השגיאה
    ]);
}
?>

<?php
// modules/songs/delete.php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    // קבלת נתוני JSON מהבקשה
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        throw new Exception('שגיאה: מזהה השיר חסר');
    }

    $songId = filter_var($data['id'], FILTER_VALIDATE_INT);
    
    if ($songId === false) {
        throw new Exception('שגיאה: מזהה השיר לא חוקי');
    }

    // קבלת הנתיב של הקובץ מהמסד נתונים
    $stmt = $pdo->prepare("SELECT file_path FROM songs WHERE id = ?");
    $stmt->execute([$songId]);
    $song = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$song) {
        throw new Exception('שגיאה: לא נמצא שיר למחיקה');
    }

    // מחיקת השיר מהמסד נתונים
    $stmt = $pdo->prepare("DELETE FROM songs WHERE id = ?");
    $stmt->execute([$songId]);

    // מחיקת הקובץ מהשרת
    $filePath = '../../public' . $song['file_path']; // ודא שהנתיב נכון
    if (file_exists($filePath)) {
        unlink($filePath); // מחיקת הקובץ
    }

    echo json_encode(['success' => true, 'message' => 'השיר נמחק בהצלחה']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

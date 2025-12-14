<?php
// modules/users/delete.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$userId) {
        throw new Exception('מזהה משתמש לא תקין');
    }

    // Prevent self-deletion
    if ($userId == $_SESSION['user_id']) {
        throw new Exception('לא ניתן למחוק את המשתמש הנוכחי');
    }

    // Check if user has any activity
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_log WHERE user_id = ?");
    $stmt->execute([$userId]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('לא ניתן למחוק משתמש שביצע פעולות במערכת');
    }

    // Delete user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('משתמש לא נמצא');
    }

    echo json_encode([
        'success' => true,
        'message' => 'המשתמש נמחק בהצלחה'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
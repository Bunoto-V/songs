<?php
// modules/users/toggle_status.php
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

    // Prevent self-deactivation
    if ($userId == $_SESSION['user_id']) {
        throw new Exception('לא ניתן לשנות את הסטטוס של המשתמש הנוכחי');
    }

    // Get current status
    $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('משתמש לא נמצא');
    }

    // Toggle status
    $stmt = $pdo->prepare("
        UPDATE users 
        SET is_active = ?, updated_at = NOW()
        WHERE id = ?
    ");

    $newStatus = !$user['is_active'];
    $stmt->execute([$newStatus, $userId]);

    echo json_encode([
        'success' => true,
        'newStatus' => $newStatus
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
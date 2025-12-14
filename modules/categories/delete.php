<?php
// modules/categories/delete.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Get and validate the category ID
    $data = json_decode(file_get_contents('php://input'), true);
    $categoryId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    
    if (!$categoryId) {
        throw new Exception('Invalid category ID');
    }

    // Check if category has songs
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM songs WHERE category_id = ?");
    $stmt->execute([$categoryId]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('לא ניתן למחוק קטגוריה שמכילה שירים');
    }

    // Delete category
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('הקטגוריה לא נמצאה');
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
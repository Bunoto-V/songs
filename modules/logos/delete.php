<?php
// modules/logos/delete.php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    
    if (!$id) {
        throw new Exception('ID לא תקין');
    }
    
    // Get logo file path before deleting
    $stmt = $pdo->prepare("SELECT logo_path FROM logos WHERE id = ?");
    $stmt->execute([$id]);
    $logo = $stmt->fetch();
    
    if (!$logo) {
        throw new Exception('לוגו לא נמצא');
    }
    
    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM logos WHERE id = ?");
    $stmt->execute([$id]);
    
    // Delete file
    $file_path = '../../public' . $logo['logo_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

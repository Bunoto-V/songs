<?php
// modules/plugins/delete.php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    
    if (!$id) {
        throw new Exception('ID לא תקין');
    }
    
    // Get plugin file path before deleting
    $stmt = $pdo->prepare("SELECT file_path FROM plugins WHERE id = ?");
    $stmt->execute([$id]);
    $plugin = $stmt->fetch();
    
    if (!$plugin) {
        throw new Exception('פלאגין לא נמצא');
    }
    
    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM plugins WHERE id = ?");
    $stmt->execute([$id]);
    
    // Delete file
    $file_path = '../../public' . $plugin['file_path'];
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

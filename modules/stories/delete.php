<?php
// modules/stories/delete.php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    
    if (!$id) {
        throw new Exception('ID לא תקין');
    }
    
    // Get story files before deleting
    $stmt = $pdo->prepare("SELECT music_file_path, background_image FROM story_content WHERE id = ?");
    $stmt->execute([$id]);
    $story = $stmt->fetch();
    
    if (!$story) {
        throw new Exception('תוכן לא נמצא');
    }
    
    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM story_content WHERE id = ?");
    $stmt->execute([$id]);
    
    // Delete files
    if ($story['music_file_path']) {
        $file_path = '../../public' . $story['music_file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    if ($story['background_image']) {
        $file_path = '../../public' . $story['background_image'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

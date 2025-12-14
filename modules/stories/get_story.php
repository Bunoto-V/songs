<?php
// modules/stories/get_story.php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$id || $id <= 0) {
        throw new Exception('ID לא תקין');
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            sc.*,
            l.producer_name_heb as logo_name,
            l.logo_path
        FROM story_content sc
        LEFT JOIN logos l ON sc.logo_id = l.id
        WHERE sc.id = ?
    ");
    $stmt->execute([$id]);
    $story = $stmt->fetch();
    
    if (!$story) {
        throw new Exception('תוכן לא נמצא');
    }
    
    echo json_encode([
        'success' => true,
        'story' => $story
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

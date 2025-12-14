<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['id'])) {
        throw new Exception('נתונים חסרים');
    }

    $sql = "UPDATE songs SET 
            title_he = ?, 
            title_en = ?, 
            youtube_link = ?,
            lyrics = ?,
            xml_content = ?
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['title_he'],
        $data['title_en'],
        $data['youtube_link'],
        $data['lyrics'],
        $data['xml_content'],
        $data['id']
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
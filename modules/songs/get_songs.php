<?php
// modules/songs/get_songs.php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT 
            s.*,
            c.category_name
        FROM songs s
        LEFT JOIN categories c ON s.category_id = c.id
        ORDER BY s.title
    ");
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($songs);
} catch (PDOException $e) {
    echo json_encode([]); // מחזיר מערך ריק במקרה של שגיאה
}
?>

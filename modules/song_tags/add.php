<?php
// מוסיף תגית לשיר עם משקל

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $songId = filter_input(INPUT_POST, 'song_id', FILTER_VALIDATE_INT);
        $tagId = filter_input(INPUT_POST, 'tag_id', FILTER_VALIDATE_INT);
        $weight = filter_input(INPUT_POST, 'weight', FILTER_VALIDATE_INT);

        if (!$songId || !$tagId || !$weight) {
            throw new Exception('נתונים חסרים');
        }

        $stmt = $pdo->prepare("
            INSERT INTO song_tags (song_id, tag_id, weight)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE weight = VALUES(weight)
        ");
        $stmt->execute([$songId, $tagId, $weight]);

        echo 'התגית נוספה בהצלחה';
    } catch (Exception $e) {
        echo 'שגיאה: ' . $e->getMessage();
    }
}
?>

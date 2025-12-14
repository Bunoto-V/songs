<?php
/****************************************************************
 * קובץ functions.php – פונקציות עזר לטיפול בשירים ותמונות
 ****************************************************************/

function addSong($pdo, $titleHe, $titleEn, $youtubeLink, $lyrics, $xmlContent) {
    $sql = "INSERT INTO songs (title_he, title_en, youtube_link, lyrics, xml_content) VALUES (:he, :en, :yt, :ly, :xml)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":he", $titleHe);
    $stmt->bindParam(":en", $titleEn);
    $stmt->bindParam(":yt", $youtubeLink);
    $stmt->bindParam(":ly", $lyrics);
    $stmt->bindParam(":xml", $xmlContent);
    $stmt->execute();
    return $pdo->lastInsertId();
}

function addImage($pdo, $songId, $imagePath) {
    $sql = "INSERT INTO images (song_id, image_path) VALUES (:sid, :ipath)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":sid", $songId);
    $stmt->bindParam(":ipath", $imagePath);
    $stmt->execute();
}

function getImagesForSong($pdo, $songId) {
    $sql = "SELECT * FROM images WHERE song_id = :songid";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":songid", $songId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

<?php
// modules/songs/download_zip.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Clean old ZIP files
cleanOldZipFiles();

$song_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$song_id) {
    die('Invalid song ID');
}

// Create ZIP
$zip_path = createSongImagesZip($song_id);

if (!$zip_path) {
    die('לא ניתן ליצור קובץ ZIP. ייתכן שאין תמונות מילים לשיר זה.');
}

// Get song title for filename
$stmt = $pdo->prepare("SELECT title, title_he FROM songs WHERE id = ?");
$stmt->execute([$song_id]);
$song = $stmt->fetch();

$download_name = ($song['title_he'] ?? $song['title'] ?? 'song_lyrics') . '.zip';

// Serve the file
$full_path = __DIR__ . '/../../public' . $zip_path;

if (file_exists($full_path)) {
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $download_name . '"');
    header('Content-Length: ' . filesize($full_path));
    header('Cache-Control: no-cache, must-revalidate');
    
    readfile($full_path);
    
    // Optionally delete the file after download
    // unlink($full_path);
    
    exit;
} else {
    die('קובץ ZIP לא נמצא');
}
?>

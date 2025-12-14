<?php
require_once 'config/database.php';

function slugify($text) {
    // Remove non-ASCII characters
    $text = transliterator_transliterate('Any-Latin; Latin-ASCII;', $text);
    // Convert to lowercase
    $text = strtolower($text);
    // Replace spaces with underscore
    $text = str_replace(' ', '_', $text);
    // Remove any remaining non-alphanumeric characters
    $text = preg_replace('/[^a-z0-9_]/', '', $text);
    return $text;
}

try {
    // Fetch all logos
    $stmt = $pdo->query("SELECT * FROM clients");
    $logos = $stmt->fetchAll();

    foreach ($logos as $logo) {
        // Get current file path and info
        $currentPath = "public" . $logo['logo_path'];
        $pathInfo = pathinfo($currentPath);
        
        // Generate new filename using producer name
        $newFilename = slugify($logo['producer_name_eng']) . "." . $pathInfo['extension'];
        $newPath = $pathInfo['dirname'] . "/" . $newFilename;
        
        // Rename file if it exists
        if (file_exists($currentPath)) {
            if (rename($currentPath, $newPath)) {
                // Update database with new path
                $newDbPath = str_replace("public", "", $newPath);
                $updateStmt = $pdo->prepare("UPDATE clients SET logo_path = ? WHERE id = ?");
                $updateStmt->execute([$newDbPath, $logo['id']]);
                
                echo "Updated: {$logo['producer_name_eng']} - {$currentPath} -> {$newPath}<br>";
            } else {
                echo "Error renaming: {$logo['producer_name_eng']}<br>";
            }
        } else {
            echo "File not found: {$currentPath}<br>";
        }
    }
    
    echo "<br>Process completed successfully!";
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<?php
// modules/plugins/upload.php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    if (!isset($_FILES['plugins'])) {
        throw new Exception('לא נבחרו קבצים');
    }
    
    $upload_dir = '../../public/uploads/plugins/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $uploaded = 0;
    $errors = [];
    
    foreach ($_FILES['plugins']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['plugins']['error'][$key] === UPLOAD_ERR_OK) {
            $original_name = $_FILES['plugins']['name'][$key];
            $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $file_size = $_FILES['plugins']['size'][$key];
            
            $new_filename = time() . '_' . uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($tmp_name, $target_path)) {
                $file_path = '/uploads/plugins/' . $new_filename;
                
                // Extract name from filename
                $plugin_name = pathinfo($original_name, PATHINFO_FILENAME);
                
                // Guess plugin type from extension
                $plugin_type = 'other';
                $audio_exts = ['vst', 'vst3', 'au', 'aax'];
                $video_exts = ['prproj', 'aep', 'mogrt'];
                $graphics_exts = ['psd', 'ai', 'indd'];
                
                if (in_array($file_extension, $audio_exts)) {
                    $plugin_type = 'audio';
                } elseif (in_array($file_extension, $video_exts)) {
                    $plugin_type = 'video';
                } elseif (in_array($file_extension, $graphics_exts)) {
                    $plugin_type = 'graphics';
                }
                
                // Insert into database
                $stmt = $pdo->prepare("
                    INSERT INTO plugins 
                    (plugin_name, file_path, file_size, plugin_type, is_active)
                    VALUES (?, ?, ?, ?, 1)
                ");
                
                $stmt->execute([
                    $plugin_name,
                    $file_path,
                    $file_size,
                    $plugin_type
                ]);
                
                $uploaded++;
            } else {
                $errors[] = "$original_name - שגיאה בהעלאה";
            }
        }
    }
    
    if ($uploaded > 0) {
        echo json_encode([
            'success' => true,
            'uploaded' => $uploaded,
            'errors' => $errors
        ]);
    } else {
        throw new Exception('לא הועלה אף קובץ. שגיאות: ' . implode(', ', $errors));
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

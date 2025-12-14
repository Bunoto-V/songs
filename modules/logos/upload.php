<?php
// modules/logos/upload.php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    if (!isset($_FILES['logos'])) {
        throw new Exception('לא נבחרו קבצים');
    }
    
    $upload_dir = '../../public/uploads/logos/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $uploaded = 0;
    $errors = [];
    
    foreach ($_FILES['logos']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['logos']['error'][$key] === UPLOAD_ERR_OK) {
            $original_name = $_FILES['logos']['name'][$key];
            $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            
            // Validate file type
            $allowed_extensions = ['png', 'svg', 'jpg', 'jpeg'];
            if (!in_array($file_extension, $allowed_extensions)) {
                $errors[] = "$original_name - פורמט לא נתמך";
                continue;
            }
            
            $new_filename = time() . '_' . uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($tmp_name, $target_path)) {
                $logo_path = '/uploads/logos/' . $new_filename;
                
                // Extract name from filename (without extension)
                $producer_name = pathinfo($original_name, PATHINFO_FILENAME);
                
                // Insert into database
                $stmt = $pdo->prepare("
                    INSERT INTO logos 
                    (producer_name_heb, logo_path, logo_type, file_format, is_active)
                    VALUES (?, ?, 'producer', ?, 1)
                ");
                
                $stmt->execute([
                    $producer_name,
                    $logo_path,
                    $file_extension
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

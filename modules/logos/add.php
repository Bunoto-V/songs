<?php
// modules/logos/add.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$pageTitle = 'הוסף לוגו חדש';
$pageHeader = 'הוסף לוגו חדש';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $producer_name_heb = $_POST['producer_name_heb'] ?? '';
        $producer_name_eng = $_POST['producer_name_eng'] ?? '';
        $logo_type = $_POST['logo_type'] ?? 'producer';
        
        if (empty($producer_name_heb)) {
            throw new Exception('נא להזין שם בעברית');
        }
        
        // Handle file upload
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../public/uploads/logos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
            $new_filename = time() . '_' . uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $target_path)) {
                $logo_path = '/uploads/logos/' . $new_filename;
                
                // Insert into database
                $stmt = $pdo->prepare("
                    INSERT INTO logos 
                    (producer_name_heb, producer_name_eng, logo_path, logo_type, file_format, is_active)
                    VALUES (?, ?, ?, ?, ?, 1)
                ");
                
                $stmt->execute([
                    $producer_name_heb,
                    $producer_name_eng,
                    $logo_path,
                    $logo_type,
                    $file_extension
                ]);
                
                $_SESSION['message'] = 'הלוגו נוסף בהצלחה!';
                $_SESSION['message_type'] = 'success';
                header('Location: list.php');
                exit;
            } else {
                throw new Exception('שגיאה בהעלאת הקובץ');
            }
        } else {
            throw new Exception('נא לבחור קובץ לוגו');
        }
        
    } catch (Exception $e) {
        $_SESSION['message'] = $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
}

require_once '../../includes/templates/header.php';
?>

<?php if (!empty($_SESSION['message'])): ?>
    <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
        <?= $_SESSION['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php 
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
    ?>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="producer_name_heb" class="form-label">שם בעברית *</label>
                    <input type="text" 
                           class="form-control" 
                           id="producer_name_heb" 
                           name="producer_name_heb" 
                           required>
                </div>
                
                <div class="col-md-6">
                    <label for="producer_name_eng" class="form-label">שם באנגלית</label>
                    <input type="text" 
                           class="form-control" 
                           id="producer_name_eng" 
                           name="producer_name_eng">
                </div>
                
                <div class="col-md-6">
                    <label for="logo_type" class="form-label">סוג לוגו</label>
                    <select class="form-select" id="logo_type" name="logo_type">
                        <option value="producer">מפיק</option>
                        <option value="brand">מותג</option>
                        <option value="event">אירוע</option>
                        <option value="other">אחר</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="logo_file" class="form-label">קובץ לוגו *</label>
                    <input type="file" 
                           class="form-control" 
                           id="logo_file" 
                           name="logo_file" 
                           accept="image/png,image/svg+xml,image/jpeg"
                           required>
                    <small class="form-text text-muted">פורמטים נתמכים: PNG, SVG, JPG</small>
                </div>
                
                <div class="col-12">
                    <div id="preview" class="mt-3" style="display: none;">
                        <label class="form-label">תצוגה מקדימה:</label>
                        <div class="border rounded p-3 bg-light" style="max-width: 300px;">
                            <img id="previewImage" src="" alt="Preview" class="img-fluid">
                        </div>
                    </div>
                </div>
                
                <div class="col-12">
                    <hr>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> שמור לוגו
                    </button>
                    <a href="list.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> ביטול
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
$extraJs = '
document.getElementById("logo_file").addEventListener("change", function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById("previewImage").src = e.target.result;
            document.getElementById("preview").style.display = "block";
        };
        reader.readAsDataURL(file);
    }
});
';

require_once '../../includes/templates/footer.php';
?>

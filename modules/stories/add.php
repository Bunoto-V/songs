<?php
// modules/stories/add.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$pageTitle = 'צור תוכן חדש לסטורי';
$pageHeader = 'צור תוכן חדש לסטורי';

try {
    $logos = $pdo->query("SELECT id, producer_name_heb FROM logos WHERE is_active = 1 ORDER BY producer_name_heb")->fetchAll();
} catch (PDOException $e) {
    $logos = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = sanitizeInput($_POST['title'] ?? '');
        $content_type = $_POST['content_type'] ?? 'mixed';
        $logo_id = filter_input(INPUT_POST, 'logo_id', FILTER_VALIDATE_INT);
        $duration = filter_input(INPUT_POST, 'duration', FILTER_VALIDATE_INT);
        $template_name = sanitizeInput($_POST['template_name'] ?? '');
        $description = $_POST['description'] ?? '';
        $tags = $_POST['tags'] ?? '';
        
        if (empty($title)) {
            throw new Exception('נא להזין כותרת');
        }
        
        // Handle music file upload
        $music_file_path = null;
        if (isset($_FILES['music_file']) && $_FILES['music_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../public/uploads/story_content/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['music_file']['name'], PATHINFO_EXTENSION));
            $new_filename = 'story_music_' . time() . '_' . uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['music_file']['tmp_name'], $target_path)) {
                $music_file_path = '/uploads/story_content/' . $new_filename;
            }
        }
        
        // Handle background image upload
        $background_image = null;
        if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../public/uploads/story_content/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['background_image']['name'], PATHINFO_EXTENSION));
            $new_filename = 'story_bg_' . time() . '_' . uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['background_image']['tmp_name'], $target_path)) {
                $background_image = '/uploads/story_content/' . $new_filename;
            }
        }
        
        // Insert into database
        $stmt = $pdo->prepare("
            INSERT INTO story_content 
            (title, content_type, music_file_path, logo_id, background_image, 
             duration, template_name, description, tags, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        $stmt->execute([
            $title,
            $content_type,
            $music_file_path,
            $logo_id ?: null,
            $background_image,
            $duration ?: null,
            $template_name,
            $description,
            $tags
        ]);
        
        $_SESSION['message'] = 'התוכן נוצר בהצלחה!';
        $_SESSION['message_type'] = 'success';
        header('Location: list.php');
        exit;
        
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
            
            <h5 class="mb-3 text-primary">פרטי התוכן</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="title" class="form-label">כותרת *</label>
                    <input type="text" 
                           class="form-control" 
                           id="title" 
                           name="title" 
                           required>
                </div>
                
                <div class="col-md-3">
                    <label for="content_type" class="form-label">סוג תוכן</label>
                    <select class="form-select" id="content_type" name="content_type">
                        <option value="mixed">מעורב</option>
                        <option value="music">מוזיקה</option>
                        <option value="logo">לוגו</option>
                        <option value="video">וידאו</option>
                        <option value="image">תמונה</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="duration" class="form-label">משך זמן (שניות)</label>
                    <input type="number" 
                           class="form-control" 
                           id="duration" 
                           name="duration"
                           min="1"
                           max="300"
                           placeholder="15">
                </div>
                
                <div class="col-md-6">
                    <label for="logo_id" class="form-label">לוגו</label>
                    <select class="form-select" id="logo_id" name="logo_id">
                        <option value="">ללא לוגו</option>
                        <?php foreach ($logos as $logo): ?>
                            <option value="<?= $logo['id'] ?>">
                                <?= htmlspecialchars($logo['producer_name_heb']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="template_name" class="form-label">תבנית</label>
                    <input type="text" 
                           class="form-control" 
                           id="template_name" 
                           name="template_name"
                           placeholder="שם התבנית">
                </div>
                
                <div class="col-md-12">
                    <label for="description" class="form-label">תיאור</label>
                    <textarea class="form-control" 
                              id="description" 
                              name="description" 
                              rows="3"></textarea>
                </div>
                
                <div class="col-md-12">
                    <label for="tags" class="form-label">תגיות</label>
                    <input type="text" 
                           class="form-control" 
                           id="tags" 
                           name="tags"
                           placeholder="הפרד בפסיקים: חתונה, שמחה, מצגת">
                </div>
            </div>
            
            <h5 class="mb-3 text-primary">קבצים</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="music_file" class="form-label">קובץ מוזיקה</label>
                    <input type="file" 
                           class="form-control" 
                           id="music_file" 
                           name="music_file"
                           accept="audio/mp3,audio/mpeg,audio/wav">
                    <div class="form-text">MP3, WAV</div>
                </div>
                
                <div class="col-md-6">
                    <label for="background_image" class="form-label">תמונת רקע</label>
                    <input type="file" 
                           class="form-control" 
                           id="background_image" 
                           name="background_image"
                           accept="image/png,image/jpeg">
                    <div class="form-text">PNG, JPG</div>
                </div>
                
                <div class="col-12" id="preview-container" style="display: none;">
                    <div class="row">
                        <div class="col-md-6" id="music-preview" style="display: none;">
                            <label class="form-label">תצוגה מקדימה - מוזיקה</label>
                            <audio controls class="w-100">
                                <source src="" type="audio/mpeg">
                            </audio>
                        </div>
                        <div class="col-md-6" id="image-preview" style="display: none;">
                            <label class="form-label">תצוגה מקדימה - תמונת רקע</label>
                            <img src="" class="img-fluid" style="max-height: 200px;">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="list.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> ביטול
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> שמור תוכן
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$extraJs = '
document.getElementById("music_file").addEventListener("change", function(e) {
    if (this.files && this.files[0]) {
        const file = this.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            const musicPreview = document.getElementById("music-preview");
            musicPreview.querySelector("audio source").src = e.target.result;
            musicPreview.querySelector("audio").load();
            musicPreview.style.display = "block";
            document.getElementById("preview-container").style.display = "block";
        };
        reader.readAsDataURL(file);
    }
});

document.getElementById("background_image").addEventListener("change", function(e) {
    if (this.files && this.files[0]) {
        const file = this.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            const imagePreview = document.getElementById("image-preview");
            imagePreview.querySelector("img").src = e.target.result;
            imagePreview.style.display = "block";
            document.getElementById("preview-container").style.display = "block";
        };
        reader.readAsDataURL(file);
    }
});
';

require_once '../../includes/templates/footer.php';
?>

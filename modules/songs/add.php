<?php
// modules/songs/add.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Page configuration
$pageTitle = 'הוספת שיר חדש';
$pageHeader = 'הוספת שיר';
$pageHeaderButtons = '<a href="list.php" class="btn btn-secondary">חזרה לרשימה</a>';

try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();
} catch (PDOException $e) {
    $_SESSION['message'] = 'שגיאה בטעינת קטגוריות';
    $_SESSION['message_type'] = 'danger';
    redirectTo('list.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (empty($_POST['title'])) {
            throw new Exception('שם השיר הוא שדה חובה');
        }
        if (empty($_POST['category_id'])) {
            throw new Exception('יש לבחור קטגוריה');
        }

        $title = sanitizeInput($_POST['title']);
        $title_he = sanitizeInput($_POST['title_he'] ?? '');
        $title_en = sanitizeInput($_POST['title_en'] ?? '');
        $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        $youtube_link = sanitizeInput($_POST['youtube_link'] ?? '');
        $google_drive_link = sanitizeInput($_POST['google_drive_link'] ?? '');
        $lyrics = $_POST['lyrics'] ?? '';
        $xml_content = $_POST['xml_content'] ?? '';
        $artist = sanitizeInput($_POST['artist'] ?? '');
        $album = sanitizeInput($_POST['album'] ?? '');
        
        // Handle file upload (optional if Google Drive link provided)
        $filePath = null;
        $file_size = null;
        
        if (isset($_FILES['song_file']) && $_FILES['song_file']['error'] === UPLOAD_ERR_OK) {
            $filePath = uploadFile(
                $_FILES['song_file'],
                '../../public/uploads/songs/',
                ['mp3', 'wav'],
                50 * 1024 * 1024
            );
            $file_size = $_FILES['song_file']['size'];
        } elseif (empty($google_drive_link)) {
            throw new Exception('יש להעלות קובץ שיר או להזין קישור Google Drive');
        }

        // Insert song
        $stmt = $pdo->prepare("
            INSERT INTO songs (
                title, title_he, title_en, category_id, 
                youtube_link, google_drive_link, file_path, file_size,
                lyrics, xml_content, artist, album,
                updated_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $title, $title_he, $title_en, $categoryId,
            $youtube_link, $google_drive_link, $filePath, $file_size,
            $lyrics, $xml_content, $artist, $album
        ]);
        
        $song_id = $pdo->lastInsertId();
        
        // Handle lyrics images upload
        if (isset($_FILES['lyrics_images']) && !empty($_FILES['lyrics_images']['name'][0])) {
            $upload_dir = '../../public/uploads/images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            foreach ($_FILES['lyrics_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['lyrics_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_extension = strtolower(pathinfo($_FILES['lyrics_images']['name'][$key], PATHINFO_EXTENSION));
                    $new_filename = 'song_' . $song_id . '_page_' . ($key + 1) . '_' . time() . '.' . $file_extension;
                    $target_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($tmp_name, $target_path)) {
                        $image_path = '/uploads/images/' . $new_filename;
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO song_images 
                            (song_id, image_path, image_type, page_number, display_order)
                            VALUES (?, ?, 'lyrics_page', ?, ?)
                        ");
                        $stmt->execute([$song_id, $image_path, $key + 1, $key]);
                    }
                }
            }
        }

        $_SESSION['message'] = 'השיר נוסף בהצלחה';
        $_SESSION['message_type'] = 'success';
        redirectTo('list.php');

    } catch (Exception $e) {
        $_SESSION['message'] = $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
}

require_once '../../includes/templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    
                    <!-- Basic Info -->
                    <h5 class="mb-3 text-primary">פרטי השיר</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="title" class="form-label">שם השיר*</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="title" 
                                   name="title" 
                                   value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                                   required>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="title_he" class="form-label">שם עברית</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="title_he" 
                                   name="title_he" 
                                   value="<?= htmlspecialchars($_POST['title_he'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="title_en" class="form-label">שם אנגלית</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="title_en" 
                                   name="title_en" 
                                   value="<?= htmlspecialchars($_POST['title_en'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="category_id" class="form-label">קטגוריה*</label>
                            <select class="form-select" 
                                    id="category_id" 
                                    name="category_id" 
                                    required>
                                <option value="">בחר קטגוריה</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"
                                            <?= (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="artist" class="form-label">אמן</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="artist" 
                                   name="artist" 
                                   value="<?= htmlspecialchars($_POST['artist'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="album" class="form-label">אלבום</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="album" 
                                   name="album" 
                                   value="<?= htmlspecialchars($_POST['album'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Files & Links -->
                    <h5 class="mb-3 text-primary">קבצים וקישורים</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="song_file" class="form-label">קובץ שיר</label>
                            <input type="file" 
                                   class="form-control" 
                                   id="song_file" 
                                   name="song_file"
                                   accept="audio/mp3,audio/mpeg,audio/wav">
                            <div class="form-text">MP3, WAV - עד 50MB</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="google_drive_link" class="form-label">קישור Google Drive</label>
                            <input type="url" 
                                   class="form-control" 
                                   id="google_drive_link" 
                                   name="google_drive_link" 
                                   value="<?= htmlspecialchars($_POST['google_drive_link'] ?? '') ?>"
                                   placeholder="https://drive.google.com/...">
                            <div class="form-text">אלטרנטיבה להעלאת קובץ</div>
                        </div>
                        
                        <div class="col-md-12">
                            <label for="youtube_link" class="form-label">קישור YouTube</label>
                            <input type="url" 
                                   class="form-control" 
                                   id="youtube_link" 
                                   name="youtube_link" 
                                   value="<?= htmlspecialchars($_POST['youtube_link'] ?? '') ?>"
                                   placeholder="https://www.youtube.com/watch?v=...">
                        </div>
                        
                        <div class="col-md-12">
                            <label for="lyrics_images" class="form-label">תמונות מילים (PNG)</label>
                            <input type="file" 
                                   class="form-control" 
                                   id="lyrics_images" 
                                   name="lyrics_images[]"
                                   accept="image/png,image/jpeg"
                                   multiple>
                            <div class="form-text">ניתן לבחור מספר תמונות - כל תמונה תהיה עמוד</div>
                        </div>
                    </div>

                    <!-- Content -->
                    <h5 class="mb-3 text-primary">תוכן</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="lyrics" class="form-label">מילים</label>
                            <textarea class="form-control" 
                                      id="lyrics" 
                                      name="lyrics" 
                                      rows="6"><?= htmlspecialchars($_POST['lyrics'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="xml_content" class="form-label">תוכן XML</label>
                            <textarea class="form-control" 
                                      id="xml_content" 
                                      name="xml_content" 
                                      rows="6"><?= htmlspecialchars($_POST['xml_content'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div id="audio-preview" class="mb-3 d-none">
                        <label class="form-label">תצוגה מקדימה</label>
                        <audio controls class="w-100">
                            <source src="" type="audio/mpeg">
                            הדפדפן שלך לא תומך בנגן השמע.
                        </audio>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="list.php" class="btn btn-secondary">ביטול</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> שמור שיר
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Page specific JavaScript
$extraJs = <<<JS
// Preview song file before upload
document.getElementById('song_file').addEventListener('change', function(e) {
    const previewContainer = document.getElementById('audio-preview');
    const audioElement = previewContainer.querySelector('audio');
    
    if (this.files && this.files[0]) {
        const file = this.files[0];
        
        // Check file size
        const maxSize = 50 * 1024 * 1024; // 50MB
        if (file.size > maxSize) {
            alert('הקובץ גדול מדי. גודל מקסימלי מותר הוא 50MB');
            this.value = '';
            previewContainer.classList.add('d-none');
            return;
        }
        
        // Check file type
        const allowedTypes = ['audio/mp3', 'audio/mpeg', 'audio/wav'];
        if (!allowedTypes.includes(file.type)) {
            alert('סוג הקובץ לא נתמך. יש להעלות קובץ MP3 או WAV בלבד');
            this.value = '';
            previewContainer.classList.add('d-none');
            return;
        }
        
        // Show preview
        const fileUrl = URL.createObjectURL(file);
        audioElement.src = fileUrl;
        previewContainer.classList.remove('d-none');
    } else {
        previewContainer.classList.add('d-none');
    }
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const title = document.getElementById('title').value.trim();
    const category = document.getElementById('category_id').value;
    const file = document.getElementById('song_file').files[0];
    const googleDrive = document.getElementById('google_drive_link').value.trim();
    
    if (!title || !category) {
        e.preventDefault();
        alert('יש למלא את שדות החובה (שם השיר וקטגוריה)');
        return;
    }
    
    if (!file && !googleDrive) {
        e.preventDefault();
        alert('יש להעלות קובץ שיר או להזין קישור Google Drive');
        return;
    }
});

// Preview lyrics images
document.getElementById('lyrics_images').addEventListener('change', function(e) {
    if (this.files.length > 0) {
        console.log(`נבחרו ${this.files.length} תמונות מילים`);
    }
});
JS;

require_once '../../includes/templates/footer.php';
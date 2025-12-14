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
        if (!isset($_FILES['song_file']) || $_FILES['song_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('יש להעלות קובץ שיר');
        }

        $title = sanitizeInput($_POST['title']);
        $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        $filePath = uploadFile(
            $_FILES['song_file'],
            '../../public/uploads/songs/',
            ['mp3', 'wav'],
            50 * 1024 * 1024
        );

        $stmt = $pdo->prepare("
            INSERT INTO songs (title, category_id, file_path, updated_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$title, $categoryId, $filePath]);

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
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="title" class="form-label">שם השיר*</label>
                        <input type="text" 
                               class="form-control" 
                               id="title" 
                               name="title" 
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                               required>
                    </div>

                    <div class="mb-3">
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

                    <div class="mb-3">
                        <label for="song_file" class="form-label">קובץ שיר*</label>
                        <input type="file" 
                               class="form-control" 
                               id="song_file" 
                               name="song_file"
                               accept="audio/mp3,audio/mpeg,audio/wav">

                        <div class="form-text">
                            <ul class="mb-0">
                                <li>קבצים מותרים: MP3, WAV</li>
                                <li>גודל מקסימלי: 50MB</li>
                            </ul>
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
                        <button type="submit" class="btn btn-primary">העלה שיר</button>
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
    
    if (!title || !category || !file) {
        e.preventDefault();
        alert('יש למלא את כל שדות החובה');
    }
});
JS;

require_once '../../includes/templates/footer.php';
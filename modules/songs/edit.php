<?php
// modules/songs/edit.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Page configuration
$pageTitle = 'עריכת שיר';
$pageHeader = 'עריכת שיר';
$pageHeaderButtons = '<a href="list.php" class="btn btn-secondary">חזרה לרשימה</a>';

$songId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$songId) {
    die('מזהה שיר לא תקין');
}

try {
    $stmt = $pdo->prepare("SELECT * FROM songs WHERE id = ?");
    $stmt->execute([$songId]);
    $song = $stmt->fetch();

    if (!$song) {
        die('שיר לא נמצא');
    }

    $categories = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();
} catch (PDOException $e) {
    die('שגיאה בטעינת נתוני השיר');
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
        $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        $filePath = $song['file_path'];

        if (isset($_FILES['song_file']) && $_FILES['song_file']['error'] === UPLOAD_ERR_OK) {
            $filePath = uploadFile(
                $_FILES['song_file'],
                '../../public/uploads/songs/',
                ['mp3', 'wav'],
                50 * 1024 * 1024
            );
        }

        $stmt = $pdo->prepare("
            UPDATE songs 
            SET title = ?, category_id = ?, file_path = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$title, $categoryId, $filePath, $songId]);

        $_SESSION['message'] = 'השיר עודכן בהצלחה';
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
                               value="<?= htmlspecialchars($song['title']) ?>"
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
                                        <?= $song['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="song_file" class="form-label">קובץ שיר (אופציונלי)</label>
                        <input type="file" 
                               class="form-control" 
                               id="song_file" 
                               name="song_file"
                               accept=".mp3,.wav">
                        <div class="form-text">
                            קבצים מותרים: MP3, WAV. השאר ריק כדי לשמור את הקובץ הקיים.
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="list.php" class="btn btn-secondary">ביטול</a>
                        <button type="submit" class="btn btn-primary">שמור שינויים</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../../includes/templates/footer.php';
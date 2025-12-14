<?php
// modules/social/schedule_post.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
requirePermission('manage_social');

// Get songs for selection
$songs = $pdo->query("
    SELECT 
        s.*,
        c.client_name
    FROM songs s
    LEFT JOIN clients c ON s.client_id = c.id
    ORDER BY s.title
")->fetchAll();

// Get available platforms
$platforms = [
    'facebook' => 'Facebook',
    'instagram' => 'Instagram',
    'tiktok' => 'TikTok'
];

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        if (empty($_POST['song_id'])) {
            throw new Exception('יש לבחור שיר');
        }
        if (empty($_POST['scheduled_time'])) {
            throw new Exception('יש לבחור מועד פרסום');
        }
        if (empty($_POST['platforms'])) {
            throw new Exception('יש לבחור לפחות פלטפורמה אחת');
        }

        // Insert post
        $stmt = $pdo->prepare("
            INSERT INTO social_posts (
                song_id, 
                content, 
                scheduled_time, 
                image_path
            ) VALUES (?, ?, ?, ?)
        ");

        // Upload image if exists
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = uploadFile(
                $_FILES['image'],
                __DIR__ . '/../../public/uploads/social/',
                ['jpg', 'jpeg', 'png']
            );
        }

        $stmt->execute([
            $_POST['song_id'],
            $_POST['content'],
            $_POST['scheduled_time'],
            $imagePath
        ]);

        $postId = $pdo->lastInsertId();

        // Insert platforms
        $platformStmt = $pdo->prepare("
            INSERT INTO post_platforms (post_id, platform_name)
            VALUES (?, ?)
        ");

        foreach ($_POST['platforms'] as $platform) {
            $platformStmt->execute([$postId, $platform]);
        }

        redirectTo('manager.php');

    } catch (Exception $e) {
        $message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>תזמון פוסט חדש</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">תזמון פוסט חדש</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-danger"><?= $message ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="song_id" class="form-label">בחר שיר*</label>
                                <select class="form-select" id="song_id" name="song_id" required>
                                    <option value="">בחר שיר</option>
                                    <?php foreach ($songs as $song): ?>
                                        <option value="<?= $song['id'] ?>">
                                            <?= htmlspecialchars($song['title']) ?> 
                                            (<?= htmlspecialchars($song['client_name']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="content" class="form-label">תוכן הפוסט</label>
                                <textarea class="form-control" 
                                          id="content" 
                                          name="content" 
                                          rows="4"></textarea>
                                <div class="form-text">השתמש ב- {title} להכנסת שם השיר ו- {client} להכנסת שם הלקוח</div>
                            </div>

                            <div class="mb-3">
                                <label for="scheduled_time" class="form-label">מועד פרסום*</label>
                                <input type="datetime-local" 
                                       class="form-control" 
                                       id="scheduled_time" 
                                       name="scheduled_time"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">פלטפורמות*</label>
                                <?php foreach ($platforms as $key => $name): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="platforms[]" 
                                               value="<?= $key ?>" 
                                               id="platform_<?= $key ?>">
                                        <label class="form-check-label" for="platform_<?= $key ?>">
                                            <?= $name ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="mb-3">
                                <label for="image" class="form-label">תמונה לפוסט</label>
                                <input type="file" 
                                       class="form-control" 
                                       id="image" 
                                       name="image"
                                       accept="image/jpeg,image/png">
                            </div>

                            <div class="text-end">
                                <a href="manager.php" class="btn btn-secondary">ביטול</a>
                                <button type="submit" class="btn btn-primary">תזמן פוסט</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/he.js"></script>
    <script>
        flatpickr("#scheduled_time", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            locale: "he",
            minDate: "today",
            time_24hr: true
        });
    </script>
</body>
</html>
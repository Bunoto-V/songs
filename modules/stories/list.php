<?php
// modules/stories/list.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$pageTitle = 'ניהול תוכן לסטוריז';
$pageHeader = 'ניהול תוכן לסטוריז';
$pageHeaderButtons = '<a href="add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> צור תוכן חדש</a>';

$extraStyles = [
    'https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css'
];

$extraScripts = [
    'https://code.jquery.com/jquery-3.6.0.min.js',
    'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js'
];

require_once '../../includes/templates/header.php';

try {
    $stmt = $pdo->query("
        SELECT 
            sc.*,
            l.producer_name_heb as logo_name,
            DATE_FORMAT(sc.created_at, '%d/%m/%Y') as created_date
        FROM story_content sc
        LEFT JOIN logos l ON sc.logo_id = l.id
        ORDER BY sc.created_at DESC
    ");
    $stories = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['message'] = 'שגיאה בטעינת רשימת הסטוריז';
    $_SESSION['message_type'] = 'danger';
    $stories = [];
}
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
        <table id="storiesTable" class="table table-striped">
            <thead>
                <tr>
                    <th>כותרת</th>
                    <th>סוג תוכן</th>
                    <th>לוגו</th>
                    <th>תבנית</th>
                    <th>משך זמן</th>
                    <th>שימושים</th>
                    <th>תאריך</th>
                    <th>פעולות</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stories as $story): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($story['title']) ?></strong>
                            <?php if ($story['description']): ?>
                                <br><small class="text-muted"><?= htmlspecialchars(substr($story['description'], 0, 50)) ?>...</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $types = [
                                'music' => '<span class="badge bg-primary">מוזיקה</span>',
                                'logo' => '<span class="badge bg-info">לוגו</span>',
                                'video' => '<span class="badge bg-danger">וידאו</span>',
                                'image' => '<span class="badge bg-success">תמונה</span>',
                                'mixed' => '<span class="badge bg-warning text-dark">מעורב</span>'
                            ];
                            echo $types[$story['content_type']] ?? $story['content_type'];
                            ?>
                        </td>
                        <td><?= htmlspecialchars($story['logo_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($story['template_name'] ?? '-') ?></td>
                        <td>
                            <?php 
                            if ($story['duration']) {
                                $minutes = floor($story['duration'] / 60);
                                $seconds = $story['duration'] % 60;
                                echo sprintf('%d:%02d', $minutes, $seconds);
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td><?= number_format($story['usage_count']) ?></td>
                        <td><?= $story['created_date'] ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-info" 
                                        onclick="previewStory(<?= $story['id'] ?>)"
                                        title="תצוגה מקדימה">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a href="edit.php?id=<?= $story['id'] ?>" 
                                   class="btn btn-sm btn-warning"
                                   title="ערוך">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button class="btn btn-sm btn-success" 
                                        onclick="downloadStory(<?= $story['id'] ?>)"
                                        title="הורד">
                                    <i class="bi bi-download"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" 
                                        onclick="deleteStory(<?= $story['id'] ?>)"
                                        title="מחק">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">תצוגה מקדימה</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Preview content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">סגור</button>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = '
$(document).ready(function() {
    $("#storiesTable").DataTable({
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/he.json"
        },
        order: [[6, "desc"]]
    });
});

function previewStory(id) {
    fetch("get_story.php?id=" + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const story = data.story;
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>פרטים:</h6>
                            <p><strong>כותרת:</strong> ${story.title}</p>
                            <p><strong>סוג תוכן:</strong> ${story.content_type}</p>
                            <p><strong>תיאור:</strong> ${story.description || "-"}</p>
                            <p><strong>תבנית:</strong> ${story.template_name || "-"}</p>
                        </div>
                        <div class="col-md-6">
                `;
                
                if (story.background_image) {
                    html += `<img src="../../public${story.background_image}" class="img-fluid mb-2">`;
                }
                
                if (story.music_file_path) {
                    html += `
                        <audio controls class="w-100">
                            <source src="../../public${story.music_file_path}" type="audio/mpeg">
                        </audio>
                    `;
                }
                
                html += `
                        </div>
                    </div>
                `;
                
                document.getElementById("previewContent").innerHTML = html;
                new bootstrap.Modal(document.getElementById("previewModal")).show();
            }
        });
}

function downloadStory(id) {
    window.location.href = "download.php?id=" + id;
}

function deleteStory(id) {
    if (!confirm("האם אתה בטוח שברצונך למחוק תוכן זה?")) {
        return;
    }
    
    fetch("delete.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert("שגיאה במחיקת התוכן: " + data.error);
        }
    });
}
';

require_once '../../includes/templates/footer.php';
?>

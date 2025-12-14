<?php
// modules/plugins/list.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$pageTitle = 'ניהול פלאגינים';
$pageHeader = 'ניהול פלאגינים';
$pageHeaderButtons = '<a href="add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> הוסף פלאגין חדש</a>';

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
            p.*,
            DATE_FORMAT(p.created_at, '%d/%m/%Y') as created_date
        FROM plugins p
        ORDER BY p.plugin_name
    ");
    $plugins = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['message'] = 'שגיאה בטעינת רשימת הפלאגינים';
    $_SESSION['message_type'] = 'danger';
    $plugins = [];
}
?>

<div class="mb-4">
    <h3>גרור ושחרר פלאגינים כאן להעלאה מהירה</h3>
    <div id="dropZone" class="drop-zone">
        גרור קבצים לכאן או לחץ לבחירה
        <input type="file" id="fileInput" multiple class="d-none">
    </div>
    <div id="uploadStatus"></div>
</div>

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
        <table id="pluginsTable" class="table table-striped">
            <thead>
                <tr>
                    <th>שם פלאגין</th>
                    <th>גרסה</th>
                    <th>סוג</th>
                    <th>גודל</th>
                    <th>תוכנות תואמות</th>
                    <th>הורדות</th>
                    <th>תאריך</th>
                    <th>פעולות</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plugins as $plugin): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($plugin['plugin_name']) ?></strong>
                            <?php if ($plugin['plugin_name_en']): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($plugin['plugin_name_en']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($plugin['version'] ?? '-') ?></td>
                        <td>
                            <?php
                            $types = [
                                'audio' => '<span class="badge bg-primary">אודיו</span>',
                                'video' => '<span class="badge bg-info">וידאו</span>',
                                'graphics' => '<span class="badge bg-success">גרפיקה</span>',
                                'other' => '<span class="badge bg-secondary">אחר</span>'
                            ];
                            echo $types[$plugin['plugin_type']] ?? $plugin['plugin_type'];
                            ?>
                        </td>
                        <td>
                            <?php 
                            if ($plugin['file_size']) {
                                if ($plugin['file_size'] < 1024*1024) {
                                    echo number_format($plugin['file_size'] / 1024, 1) . ' KB';
                                } else {
                                    echo number_format($plugin['file_size'] / (1024*1024), 1) . ' MB';
                                }
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            $software = $plugin['compatible_software'];
                            if ($software) {
                                echo '<small>' . nl2br(htmlspecialchars($software)) . '</small>';
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td><?= number_format($plugin['download_count']) ?></td>
                        <td><?= $plugin['created_date'] ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="../../public<?= htmlspecialchars($plugin['file_path']) ?>" 
                                   class="btn btn-sm btn-info" 
                                   download
                                   title="הורד">
                                    <i class="bi bi-download"></i>
                                </a>
                                <a href="edit.php?id=<?= $plugin['id'] ?>" 
                                   class="btn btn-sm btn-warning"
                                   title="ערוך">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button class="btn btn-sm btn-danger" 
                                        onclick="deletePlugin(<?= $plugin['id'] ?>)"
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

<?php
$extraJs = '
$(document).ready(function() {
    $("#pluginsTable").DataTable({
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/he.json"
        },
        order: [[0, "asc"]]
    });
    
    const dropZone = document.getElementById("dropZone");
    const fileInput = document.getElementById("fileInput");
    
    dropZone.addEventListener("click", () => fileInput.click());
    
    dropZone.addEventListener("dragover", (e) => {
        e.preventDefault();
        dropZone.classList.add("drag-over");
    });
    
    dropZone.addEventListener("dragleave", () => {
        dropZone.classList.remove("drag-over");
    });
    
    dropZone.addEventListener("drop", (e) => {
        e.preventDefault();
        dropZone.classList.remove("drag-over");
        handleFiles(e.dataTransfer.files);
    });
    
    fileInput.addEventListener("change", (e) => {
        handleFiles(e.target.files);
    });
    
    function handleFiles(files) {
        const formData = new FormData();
        
        for (let file of files) {
            formData.append("plugins[]", file);
        }
        
        const uploadStatus = document.getElementById("uploadStatus");
        uploadStatus.innerHTML = `<div class="upload-progress alert alert-info">מעלה ${files.length} קבצים...</div>`;
        
        fetch("upload.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                uploadStatus.innerHTML = `<div class="alert alert-success">הועלו ${data.uploaded} פלאגינים בהצלחה!</div>`;
                setTimeout(() => location.reload(), 1500);
            } else {
                uploadStatus.innerHTML = `<div class="alert alert-danger">שגיאה: ${data.error}</div>`;
            }
        })
        .catch(error => {
            uploadStatus.innerHTML = `<div class="alert alert-danger">שגיאה בהעלאה</div>`;
        });
    }
});

function deletePlugin(id) {
    if (!confirm("האם אתה בטוח שברצונך למחוק פלאגין זה?")) {
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
            alert("שגיאה במחיקת הפלאגין: " + data.error);
        }
    });
}
';

require_once '../../includes/templates/footer.php';
?>

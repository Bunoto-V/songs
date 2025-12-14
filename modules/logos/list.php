<?php
// modules/logos/list.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Page configuration
$pageTitle = 'ניהול לוגואים';
$pageHeader = 'ניהול לוגואים';
$pageHeaderButtons = '<a href="add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> הוסף לוגו חדש</a>';

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
    // Get all logos
    $stmt = $pdo->query("
        SELECT 
            l.*,
            DATE_FORMAT(l.created_at, '%d/%m/%Y') as created_date
        FROM logos l
        ORDER BY l.producer_name_heb
    ");
    $logos = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['message'] = 'שגיאה בטעינת רשימת הלוגואים';
    $_SESSION['message_type'] = 'danger';
    $logos = [];
}
?>

<div class="mb-4">
    <h3>גרור ושחרר לוגואים כאן להעלאה מהירה</h3>
    <div id="dropZone" class="drop-zone">
        גרור קבצים לכאן או לחץ לבחירה
        <input type="file" id="fileInput" multiple accept="image/png,image/svg+xml,image/jpeg" class="d-none">
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
        <table id="logosTable" class="table table-striped">
            <thead>
                <tr>
                    <th>תצוגה</th>
                    <th>שם עברית</th>
                    <th>שם אנגלית</th>
                    <th>סוג</th>
                    <th>פורמט</th>
                    <th>הורדות</th>
                    <th>תאריך הוספה</th>
                    <th>פעולות</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logos as $logo): ?>
                    <tr>
                        <td>
                            <img src="../../public<?= htmlspecialchars($logo['logo_path']) ?>" 
                                 alt="<?= htmlspecialchars($logo['producer_name_heb']) ?>"
                                 class="img-fluid"
                                 style="max-height: 60px; max-width: 100px; object-fit: contain;">
                        </td>
                        <td><?= htmlspecialchars($logo['producer_name_heb']) ?></td>
                        <td><?= htmlspecialchars($logo['producer_name_eng'] ?? '-') ?></td>
                        <td>
                            <?php
                            $types = [
                                'producer' => 'מפיק',
                                'brand' => 'מותג',
                                'event' => 'אירוע',
                                'other' => 'אחר'
                            ];
                            echo $types[$logo['logo_type']] ?? $logo['logo_type'];
                            ?>
                        </td>
                        <td><?= strtoupper($logo['file_format'] ?? '-') ?></td>
                        <td><?= number_format($logo['download_count']) ?></td>
                        <td><?= $logo['created_date'] ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="../../public<?= htmlspecialchars($logo['logo_path']) ?>" 
                                   class="btn btn-sm btn-info" 
                                   download
                                   title="הורד">
                                    <i class="bi bi-download"></i>
                                </a>
                                <a href="edit.php?id=<?= $logo['id'] ?>" 
                                   class="btn btn-sm btn-warning"
                                   title="ערוך">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button class="btn btn-sm btn-danger" 
                                        onclick="deleteLogo(<?= $logo['id'] ?>)"
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
    $("#logosTable").DataTable({
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/he.json"
        },
        order: [[1, "asc"]]
    });
    
    // Drag & Drop functionality
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
            formData.append("logos[]", file);
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
                uploadStatus.innerHTML = `<div class="alert alert-success">הועלו ${data.uploaded} לוגואים בהצלחה!</div>`;
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

function deleteLogo(id) {
    if (!confirm("האם אתה בטוח שברצונך למחוק לוגו זה?")) {
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
            alert("שגיאה במחיקת הלוגו: " + data.error);
        }
    });
}
';

require_once '../../includes/templates/footer.php';
?>

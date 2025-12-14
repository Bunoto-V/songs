<?php
// modules/clients/list.php
//session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Page configuration
$pageTitle = 'ניהול לוגואים';
$pageHeader = 'ניהול לוגואים';
$pageHeaderButtons = '<a href="add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> הוסף לוגו חדש</a>';

// Extra styles
$extraStyles = [
    'https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css',
    "<style>
        .drop-zone {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        .drop-zone.drag-over {
            background: #e9ecef;
            border-color: #4a90e2;
        }
        .client-logo {
            max-height: 50px;
            object-fit: contain;
        }
        .upload-progress {
            margin: 0.5rem 0;
            padding: 0.5rem;
            border-radius: 4px;
        }
    </style>"
];

// Extra scripts
$extraScripts = [
    'https://code.jquery.com/jquery-3.6.0.min.js',
    'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js'
];

require_once '../../includes/templates/header.php';

try {
    $stmt = $pdo->query("
        SELECT 
            id,
            producer_name_heb,
            producer_name_eng,
            logo_path,
            created_at
        FROM clients
        ORDER BY producer_name_heb
    ");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['message'] = 'שגיאה בטעינת רשימת הלוגואים';
    $_SESSION['message_type'] = 'danger';
    $clients = [];
}
?>

<div class="mb-4">
    <h3>גרור ושחרר לוגואים כאן להעלאה מהירה</h3>
    <div id="dropZone" class="drop-zone">
        גרור קבצים לכאן או לחץ לבחירה
        <input type="file" id="fileInput" multiple accept="image/jpeg,image/png" class="d-none">
    </div>
    <div id="uploadStatus"></div>
</div>

<div class="card">
    <div class="card-body">
        <table id="clientsTable" class="table table-striped">
            <thead>
                <tr>
                    <th width="80">לוגו</th>
                    <th>שם בעברית</th>
                    <th>שם באנגלית</th>
                    <th>תאריך הוספה</th>
                    <th width="150">פעולות</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $client): ?>
                <tr>
                    <td>
                        <?php if ($client['logo_path']): ?>
                            <img src="../../public<?= htmlspecialchars($client['logo_path']) ?>" 
                                 alt="<?= htmlspecialchars($client['producer_name_heb']) ?>" 
                                 class="client-logo">
                        <?php else: ?>
                            <span class="text-muted">אין לוגו</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($client['producer_name_heb']) ?></td>
                    <td><?= htmlspecialchars($client['producer_name_eng']) ?></td>
                    <td><?= date('d/m/Y', strtotime($client['created_at'])) ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="edit.php?id=<?= $client['id'] ?>" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" 
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="deleteClient(<?= $client['id'] ?>)">
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
// Page specific JavaScript
$extraJs = <<<JS
// Initialize DataTable
$(document).ready(function() {
    $('#clientsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/he.json'
        },
        order: [[1, 'asc']],
        pageLength: 100,           // מספר השורות שיוצגו כברירת מחדל
        lengthMenu: [             // אפשרויות להצגת מספר שורות
            [100, 200, 300, 400, 500],
            [100, 200, 300, 400, 500]
        ]
    });
});

// Drag and Drop implementation
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('dropZone');
    const uploadStatus = document.getElementById('uploadStatus');
    let uploadedFiles = {};

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.add('drag-over'));
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.remove('drag-over'));
    });

    dropZone.addEventListener('drop', e => handleFiles(e.dataTransfer.files));
    dropZone.addEventListener('click', () => {
        const input = document.createElement('input');
        input.type = 'file';
        input.multiple = true;
        input.accept = 'image/jpeg,image/png';
        input.onchange = e => handleFiles(e.target.files);
        input.click();
    });

    function handleFiles(files) {
        uploadStatus.innerHTML = '';
        uploadedFiles = {};
        [...files].forEach(file => uploadFile(file));
    }

    function uploadFile(file) {
        if (!file.type.match('image/(jpeg|png)')) {
            uploadStatus.innerHTML += `
                <div class="alert alert-danger">
                    \${file.name} - סוג קובץ לא נתמך. יש להעלות רק קבצי PNG או JPEG
                </div>`;
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            uploadStatus.innerHTML += `
                <div class="alert alert-danger">
                    \${file.name} - הקובץ גדול מדי. גודל מקסימלי הוא 5MB
                </div>`;
            return;
        }

        const formData = new FormData();
        formData.append('logo', file);

        const progressDiv = document.createElement('div');
        progressDiv.className = 'alert alert-info';
        progressDiv.innerHTML = `\${file.name} - מעלה...`;
        uploadStatus.appendChild(progressDiv);

        fetch('upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                progressDiv.className = 'alert alert-success';
                progressDiv.innerHTML = `\${file.name} - הועלה בהצלחה`;
                uploadedFiles[data.filePath] = file.name;
                
                // Check if all files are uploaded
                const uploadedCount = Object.keys(uploadedFiles).length;
                const totalCount = document.querySelectorAll('#uploadStatus .alert-success').length;
                
                if (uploadedCount === totalCount) {
                    showClientSelectionDialog(uploadedFiles);
                }
            } else {
                progressDiv.className = 'alert alert-danger';
                progressDiv.innerHTML = `\${file.name} - \${data.error || 'שגיאה בהעלאה'}`;
            }
        })
        .catch(error => {
            progressDiv.className = 'alert alert-danger';
            progressDiv.innerHTML = `\${file.name} - שגיאה בהעלאה`;
            console.error('Upload error:', error);
        });
    }

    function showClientSelectionDialog(files) {
        const modalHtml = `
            <div class="modal fade" id="clientModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">הזנת פרטי מפיקים</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="clientForm">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>שם קובץ</th>
                                            <th>שם מפיק בעברית*</th>
                                            <th>שם מפיק באנגלית*</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        \${Object.entries(files).map(([path, name]) => `
                                            <tr>
                                                <td>\${name}</td>
                                                <td>
                                                    <input type="text" 
                                                           class="form-control" 
                                                           name="producers[\${path}][producer_name_heb]" 
                                                           required>
                                                    <input type="hidden" 
                                                           name="producers[\${path}][logo_path]" 
                                                           value="\${path}">
                                                </td>
                                                <td>
                                                    <input type="text" 
                                                           class="form-control" 
                                                           name="producers[\${path}][producer_name_eng]"
                                                           required>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
                            <button type="button" class="btn btn-primary" onclick="saveClients()">שמור</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        if (document.getElementById('clientModal')) {
            document.getElementById('clientModal').remove();
        }
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        const modal = new bootstrap.Modal(document.getElementById('clientModal'));
        modal.show();
    }
});

function saveClients() {
    const form = document.getElementById('clientForm');
    const formData = new FormData(form);

    fetch('save_client.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'שגיאה בשמירת המפיקים');
        }
    })
    .catch(error => {
        console.error('Save error:', error);
        alert('שגיאה בשמירת המפיקים');
    });
}

function deleteClient(id) {
    if (confirm('האם אתה בטוח שברצונך למחוק? פעולה זו בלתי הפיכה!')) {
        fetch('delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'אירעה שגיאה במחיקה');
            }
        });
    }
}
JS;

require_once '../../includes/templates/footer.php';
?>
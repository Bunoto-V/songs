<?php
// modules/songs/list.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Page configuration
$pageTitle = 'ניהול שירים';
$pageHeader = 'ניהול שירים';
$pageHeaderButtons = '<a href="add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> הוסף שיר חדש</a>';

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
    // Get all categories for the dropdown
    $categories = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();

    // Get all songs with their categories and image count
    $stmt = $pdo->query("
        SELECT 
            s.*,
            c.category_name,
            COUNT(DISTINCT si.id) as lyrics_images_count
        FROM songs s
        LEFT JOIN categories c ON s.category_id = c.id
        LEFT JOIN song_images si ON s.id = si.song_id AND si.image_type = 'lyrics_page'
        GROUP BY s.id
        ORDER BY s.created_at DESC
    ");
    $songs = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['message'] = 'שגיאה בטעינת רשימת השירים';
    $_SESSION['message_type'] = 'danger';
    $songs = [];
}
?>

<div class="mb-4">
    <h3>גרור ושחרר שירים כאן להעלאה מהירה</h3>
    <div id="dropZone" class="drop-zone">
        גרור קבצים לכאן או לחץ לבחירה
        <input type="file" id="fileInput" multiple accept="audio/mpeg,audio/wav" class="d-none">
    </div>
    <div id="uploadStatus"></div>
</div>

<div class="card">
    <div class="card-body">
        <table id="songsTable" class="table table-striped">
            <thead>
                <tr>
                    <th>שם השיר</th>
                    <th>אמן</th>
                    <th>קטגוריה</th>
                    <th>קישורים</th>
                    <th width="150">נגן</th>
                    <th>תאריך</th>
                    <th width="200">פעולות</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($songs as $song): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($song['title']) ?></strong>
                        <?php if ($song['title_he'] || $song['title_en']): ?>
                            <br>
                            <small class="text-muted">
                                <?= htmlspecialchars($song['title_he'] ?? '') ?>
                                <?= ($song['title_he'] && $song['title_en']) ? ' / ' : '' ?>
                                <?= htmlspecialchars($song['title_en'] ?? '') ?>
                            </small>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($song['artist'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($song['category_name']) ?></td>
                    <td>
                        <?php if ($song['youtube_link']): ?>
                            <a href="<?= htmlspecialchars($song['youtube_link']) ?>" 
                               target="_blank" 
                               class="badge bg-danger text-white"
                               title="YouTube">
                                <i class="bi bi-youtube"></i>
                            </a>
                        <?php endif; ?>
                        <?php if ($song['google_drive_link']): ?>
                            <a href="<?= htmlspecialchars($song['google_drive_link']) ?>" 
                               target="_blank" 
                               class="badge bg-primary text-white"
                               title="Google Drive">
                                <i class="bi bi-cloud"></i>
                            </a>
                        <?php endif; ?>
                        <?php if ($song['lyrics_images_count'] > 0): ?>
                            <span class="badge bg-info text-white" title="תמונות מילים">
                                <i class="bi bi-images"></i> <?= $song['lyrics_images_count'] ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($song['file_path']): ?>
                            <audio controls style="width: 150px">
                                <source src="../../public<?= htmlspecialchars($song['file_path']) ?>" type="audio/mpeg">
                                הדפדפן שלך לא תומך בנגן השמע
                            </audio>
                        <?php else: ?>
                            <small class="text-muted">קישור בלבד</small>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d/m/Y', strtotime($song['created_at'])) ?></td>
                    <td>
                        <div class="btn-group">
                            <?php if ($song['file_path']): ?>
                                <a href="../../public<?= htmlspecialchars($song['file_path']) ?>" 
                                   class="btn btn-sm btn-success"
                                   download
                                   title="הורד שיר">
                                    <i class="bi bi-download"></i>
                                </a>
                            <?php endif; ?>
                            <?php if ($song['lyrics_images_count'] > 0): ?>
                                <a href="download_zip.php?id=<?= $song['id'] ?>" 
                                   class="btn btn-sm btn-info"
                                   title="הורד ZIP של מילים">
                                    <i class="bi bi-file-zip"></i>
                                </a>
                            <?php endif; ?>
                            <a href="edit.php?id=<?= htmlspecialchars($song['id']) ?>" 
                               class="btn btn-sm btn-warning"
                               title="ערוך">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" 
                                    class="btn btn-sm btn-danger"
                                    onclick="deleteSong(<?= htmlspecialchars($song['id']) ?>)"
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

<!-- Modal for song details -->
<div class="modal fade" id="songModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">פרטי שיר</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="songForm">
                    <div class="mb-3">
                        <label class="form-label">שם השיר*</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">קטגוריה*</label>
                        <select class="form-select" name="category_id" required>
                            <option value="">בחר קטגוריה</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category['id']) ?>">
                                    <?= htmlspecialchars($category['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="file_path">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ביטול</button>
                <button type="button" class="btn btn-primary" id="saveSongButton">שמור</button>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = <<<JS
// Initialize DataTable
$(document).ready(function() {
    $('#songsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/he.json'
        },
        order: [[5, 'desc']], // Order by date descending
        columnDefs: [
            { orderable: false, targets: [3, 4, 6] } // Links, Player, Actions
        ]
    });
});

// Handle drag-and-drop file upload
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('dropZone');
    const uploadStatus = document.getElementById('uploadStatus');

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
        input.accept = 'audio/mpeg,audio/wav,audio/mp3';
        input.onchange = e => handleFiles(e.target.files);
        input.click();
    });
});

function handleFiles(files) {
    document.getElementById('uploadStatus').innerHTML = '';
    const fileQueue = Array.from(files);
    processNextFile(fileQueue);
}

function processNextFile(fileQueue) {
    if (fileQueue.length === 0) {
        console.log('All files processed.');
        // כאן תוכל לרענן את הטבלה או לדף כולו
        refreshSongsTable(); // קריאה לפונקציה לרענן את הטבלה
        return;
    }
    const file = fileQueue.shift();
    console.log('Processing file:', file.name);

    // Upload the file
    const formData = new FormData();
    formData.append('song', file);

    fetch('upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSongDetailsModal(data.filePath, data.originalName, fileQueue);
        } else {
            displayError(file.name, data.error || 'שגיאה בהעלאה');
            processNextFile(fileQueue);
        }
    })
    .catch(error => {
        displayError(file.name, 'שגיאה בהעלאה');
        console.error('Upload error:', error);
        processNextFile(fileQueue);
    });
}
function refreshSongsTable() {
    fetch('get_songs.php') // ודא שיש לך קובץ שיחזיר את כל השירים
        .then(response => response.json())
        .then(data => {
            console.log('Fetched songs:', data); // הדפסת הנתונים
            const tableBody = $('#songsTable tbody');
            tableBody.empty(); // מנקה את התוכן הקודם

            // עדכון ה-DataTable עם נתונים חדשים
            const dataTable = $('#songsTable').DataTable();
            dataTable.clear(); // מנקה את הנתונים הקיימים

            data.forEach(song => {
                const row = [
                    htmlspecialchars(song.title),
                    htmlspecialchars(song.category_name),
                    `<audio controls style="width: 150px">
                        <source src="../../public\${htmlspecialchars(song.file_path)}" type="audio/mpeg">
                        הדפדפן שלך לא תומך בנגן השמע
                    </audio>`,
                    new Date(song.created_at).toLocaleDateString(),
                    `<div class="btn-group">
                        <a href="../../public\${htmlspecialchars(song.file_path)}" class="btn btn-sm btn-success" download>
                            <i class="bi bi-download"></i>
                        </a>
                        <a href="edit.php?id=\${htmlspecialchars(song.id)}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteSong(\${htmlspecialchars(song.id)})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>`
                ];
                dataTable.row.add(row); // הוספת השורה החדשה ל-DataTable
            });

            dataTable.draw(); // רענן את הטבלה
        })
        .catch(error => console.error('Error fetching songs:', error));
}



function htmlspecialchars(str) {
    if (typeof str !== 'string') return str;
    return str.replace(/&/g, '&amp;')
              .replace(/</g, '&lt;')
              .replace(/>/g, '&gt;')
              .replace(/"/g, '&quot;')
              .replace(/'/g, '&#039;');
}


function showSongDetailsModal(filePath, fileName, fileQueue) {
    const form = document.getElementById('songForm');
    form.reset();
    form.querySelector('[name="title"]').value = fileName.replace(/\.[^/.]+$/, "");
    form.querySelector('[name="file_path"]').value = filePath;

    const modal = new bootstrap.Modal(document.getElementById('songModal'));
    modal.show();

    const saveButton = document.getElementById('saveSongButton');
    saveButton.onclick = function() {
        const formData = new FormData(form);
        fetch('save_song.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modal.hide();
                document.getElementById('uploadStatus').innerHTML += `
                    <div class="alert alert-success">\${fileName} - נשמר בהצלחה</div>
                `;
				
            } else {
                // מציג את הודעת השגיאה המתקבלת מהשרת
                alert(data.error || 'שגיאה בשמירת השיר');
            }
            processNextFile(fileQueue);
        })
        .catch(error => {
            console.error('שגיאת שמירה:', error);
            alert('שגיאה בשמירת השיר');
            processNextFile(fileQueue);
        });
    };
}

function displayError(fileName, message) {
    const progressDiv = document.createElement('div');
    progressDiv.className = 'alert alert-danger';
    progressDiv.innerHTML = `\${fileName} - \${message}`;
    document.getElementById('uploadStatus').appendChild(progressDiv);
}

function deleteSong(id) {
    if (confirm('האם אתה בטוח שברצונך למחוק את השיר? פעולה זו בלתי הפיכה!')) {
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
                location.reload(); // רענן את הדף במקרה של הצלחה
            } else {
                alert(data.error || 'אירעה שגיאה במחיקת השיר');
                console.error('Error during deletion:', data.error);
            }
        })
        .catch(error => {
            console.error('Error fetching delete response:', error);
            alert('שגיאה במהלך המחיקה');
        });
    }
}


// Stop other audio players when one starts
document.addEventListener('play', function(e) {
    if (e.target.tagName.toLowerCase() === 'audio') {
        const audios = document.getElementsByTagName('audio');
        for (let i = 0; i < audios.length; i++) {
            if (audios[i] !== e.target) {
                audios[i].pause();
            }
        }
    }
}, true);
JS;

require_once '../../includes/templates/footer.php';
?>

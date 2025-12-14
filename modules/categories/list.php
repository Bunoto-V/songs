<?php
// modules/categories/list.php

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Page configuration
$pageTitle = 'ניהול קטגוריות';
$pageHeader = 'ניהול קטגוריות';
$pageHeaderButtons = '<a href="add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> הוסף קטגוריה חדשה</a>';

$extraStyles = [
    'https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css'
];

$extraScripts = [
    'https://code.jquery.com/jquery-3.6.0.min.js',
    'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js'
];

require_once '../../includes/templates/header.php';

// Get categories with song count
try {
    $stmt = $pdo->query("
        SELECT 
            c.*,
            COUNT(s.id) as songs_count
        FROM categories c
        LEFT JOIN songs s ON c.id = s.category_id
        GROUP BY c.id, c.category_name, c.created_at, c.updated_at
        ORDER BY c.category_name
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['message'] = 'שגיאה בטעינת רשימת הקטגוריות';
    $_SESSION['message_type'] = 'danger';
    $categories = [];
}
?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="categoriesTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>שם הקטגוריה</th>
                        <th>מספר שירים</th>
                        <th>תאריך יצירה</th>
                        <th>עדכון אחרון</th>
                        <th width="120">פעולות</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?= htmlspecialchars($category['category_name']) ?></td>
                        <td>
                            <span class="badge bg-info">
                                <?= $category['songs_count'] ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($category['created_at'])) ?></td>
                        <td>
                            <?= $category['updated_at'] ? date('d/m/Y H:i', strtotime($category['updated_at'])) : '-' ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="edit.php?id=<?= $category['id'] ?>" 
                                   class="btn btn-sm btn-outline-primary"
                                   title="ערוך">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($category['songs_count'] == 0): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="deleteCategory(<?= $category['id'] ?>)"
                                            title="מחק">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Page specific JavaScript
$extraJs = <<<JS
$(document).ready(function() {
    $('#categoriesTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/he.json'
        },
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: [4] },
            { type: 'num', targets: [1] },
            { type: 'date', targets: [2, 3] }
        ]
    });
});

function deleteCategory(id) {
    if (confirm('האם אתה בטוח שברצונך למחוק את הקטגוריה?')) {
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
                alert(data.error || 'אירעה שגיאה במחיקת הקטגוריה');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('אירעה שגיאה בתהליך המחיקה');
        });
    }
}
JS;

require_once '../../includes/templates/footer.php';
?>

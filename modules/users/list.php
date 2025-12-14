<?php
// modules/users/list.php
//session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Page configuration
$pageTitle = 'ניהול משתמשים';
$pageHeader = 'ניהול משתמשים';
$pageHeaderButtons = '<a href="add.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> הוסף משתמש חדש</a>';

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
    // Get users with their roles (simplified query without activity log)
    $stmt = $pdo->query("
        SELECT 
            u.*,
            r.role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        ORDER BY u.username
    ");
    $users = $stmt->fetchAll();

    // Get all roles for filter
    $roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll();
} catch (PDOException $e) {
    $_SESSION['message'] = 'שגיאה בטעינת רשימת המשתמשים';
    $_SESSION['message_type'] = 'danger';
    $users = [];
    $roles = [];
}
?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="usersTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>שם משתמש</th>
                        <th>תפקיד</th>
                        <th>דוא"ל</th>
                        <th>סטטוס</th>
                        <th>כניסה אחרונה</th>
                        <th width="150">פעולות</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td>
                            <span class="badge bg-primary">
                                <?= htmlspecialchars($user['role_name']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <?php if ($user['is_active']): ?>
                                <span class="badge bg-success">פעיל</span>
                            <?php else: ?>
                                <span class="badge bg-danger">לא פעיל</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user['last_login']): ?>
                                <?= date('d/m/Y H:i', strtotime($user['last_login'])) ?>
                            <?php else: ?>
                                <span class="text-muted">טרם התחבר</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="edit.php?id=<?= $user['id'] ?>" 
                                   class="btn btn-sm btn-outline-primary"
                                   title="ערוך">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-<?= $user['is_active'] ? 'warning' : 'success' ?>"
                                            onclick="toggleUserStatus(<?= $user['id'] ?>)"
                                            title="<?= $user['is_active'] ? 'השבת' : 'הפעל' ?>">
                                        <i class="bi bi-<?= $user['is_active'] ? 'pause' : 'play' ?>"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="deleteUser(<?= $user['id'] ?>)"
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
$extraJs = <<<JS
// Initialize DataTable
$(document).ready(function() {
    $('#usersTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/he.json'
        },
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: [5] }
        ]
    });
});

// Toggle user status
function toggleUserStatus(id) {
    if (confirm('האם אתה בטוח שברצונך לשנות את סטטוס המשתמש?')) {
        fetch('toggle_status.php', {
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
                alert(data.error || 'אירעה שגיאה בשינוי סטטוס המשתמש');
            }
        });
    }
}

// Delete user
function deleteUser(id) {
    if (confirm('האם אתה בטוח שברצונך למחוק את המשתמש? פעולה זו בלתי הפיכה!')) {
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
                alert(data.error || 'אירעה שגיאה במחיקת המשתמש');
            }
        });
    }
}
JS;

require_once '../../includes/templates/footer.php';
?>
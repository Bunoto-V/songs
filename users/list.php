<?php
// modules/users/list.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
requirePermission('manage_users');

// Get all users with their roles
$users = $pdo->query("
    SELECT 
        u.*,
        r.role_name
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    ORDER BY u.username
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ניהול משתמשים</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>ניהול משתמשים</h1>
            <a href="add.php" class="btn btn-primary">הוסף משתמש חדש</a>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>שם משתמש</th>
                            <th>אימייל</th>
                            <th>תפקיד</th>
                            <th>סטטוס</th>
                            <th>כניסה אחרונה</th>
                            <th>פעולות</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['role_name']) ?></td>
                            <td>
                                <span class="badge bg-<?= $user['is_active'] ? 'success' : 'danger' ?>">
                                    <?= $user['is_active'] ? 'פעיל' : 'לא פעיל' ?>
                                </span>
                            </td>
                            <td><?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : '-' ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="edit.php?id=<?= $user['id'] ?>" 
                                       class="btn btn-sm btn-outline-primary">עריכה</a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-<?= $user['is_active'] ? 'warning' : 'success' ?>"
                                                onclick="toggleUserStatus(<?= $user['id'] ?>)">
                                            <?= $user['is_active'] ? 'השבת' : 'הפעל' ?>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="deleteUser(<?= $user['id'] ?>)">מחיקה</button>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function toggleUserStatus(id) {
            if (confirm('האם אתה בטוח שברצונך לשנות את סטטוס המשתמש?')) {
                fetch(`toggle_status.php?id=${id}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('אירעה שגיאה בשינוי סטטוס המשתמש');
                    }
                });
            }
        }

        function deleteUser(id) {
            if (confirm('האם אתה בטוח שברצונך למחוק משתמש זה?')) {
                fetch(`delete.php?id=${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('אירעה שגיאה במחיקת המשתמש');
                    }
                });
            }
        }
    </script>
</body>
</html>
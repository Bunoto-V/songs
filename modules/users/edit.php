<?php
// modules/users/edit.php
//session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Page configuration
$pageTitle = 'עריכת משתמש';
$pageHeader = 'עריכת משתמש';

// Get user ID and validate
$userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$userId) {
    $_SESSION['message'] = 'מזהה משתמש לא תקין';
    $_SESSION['message_type'] = 'danger';
    redirectTo('list.php');
}

try {
    // Get user data
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            r.role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['message'] = 'משתמש לא נמצא';
        $_SESSION['message_type'] = 'danger';
        redirectTo('list.php');
    }

    // Get available roles
    $roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll();

} catch (PDOException $e) {
    $_SESSION['message'] = 'שגיאה בטעינת נתוני המשתמש';
    $_SESSION['message_type'] = 'danger';
    redirectTo('list.php');
}

// Set back button
$pageHeaderButtons = '<a href="list.php" class="btn btn-secondary">חזרה לרשימה</a>';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        if (empty($_POST['username']) || empty($_POST['email'])) {
            throw new Exception('שדות שם משתמש ודוא"ל הם שדות חובה');
        }

        $username = sanitizeInput($_POST['username']);
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $roleId = filter_var($_POST['role_id'], FILTER_VALIDATE_INT);
        $password = $_POST['password'] ?? '';

        if (!$email) {
            throw new Exception('כתובת האימייל אינה תקינה');
        }

        // Check if username or email already taken by another user
        $stmt = $pdo->prepare("
            SELECT id 
            FROM users 
            WHERE (username = ? OR email = ?) 
            AND id != ?
        ");
        $stmt->execute([$username, $email, $userId]);
        if ($stmt->fetch()) {
            throw new Exception('שם המשתמש או כתובת האימייל כבר קיימים במערכת');
        }

        // Build update query
        $sql = "
            UPDATE users 
            SET 
                username = ?,
                email = ?,
                role_id = ?,
                updated_at = NOW()
        ";
        $params = [$username, $email, $roleId];

        // Add password update if provided
        if (!empty($password)) {
            // Validate password strength
            if (strlen($password) < 8) {
                throw new Exception('הסיסמה חייבת להכיל לפחות 8 תווים');
            }

            if (!preg_match("/[A-Z]/", $password) || 
                !preg_match("/[a-z]/", $password) || 
                !preg_match("/[0-9]/", $password)) {
                throw new Exception('הסיסמה חייבת להכיל אותיות גדולות, קטנות ומספרים');
            }

            $sql .= ", password = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = ?";
        $params[] = $userId;

        // Update user
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $_SESSION['message'] = 'המשתמש עודכן בהצלחה';
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
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">שם משתמש*</label>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username"
                               value="<?= htmlspecialchars($user['username']) ?>"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">דוא"ל*</label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email"
                               value="<?= htmlspecialchars($user['email']) ?>"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">סיסמה</label>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password"
                               placeholder="השאר ריק לשמירה על הסיסמה הנוכחית">
                        <div class="form-text">
                            הסיסמה חייבת להכיל לפחות 8 תווים, אות גדולה, אות קטנה ומספר
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="role_id" class="form-label">תפקיד*</label>
                        <select class="form-select" 
                                id="role_id" 
                                name="role_id"
                                required
                                <?= $userId == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= $role['id'] ?>"
                                        <?= $user['role_id'] == $role['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($role['role_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($userId == $_SESSION['user_id']): ?>
                            <div class="form-text text-warning">
                                לא ניתן לשנות את התפקיד של המשתמש הנוכחי
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">נוצר בתאריך:</label>
                        <div><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></div>
                    </div>

                    <?php if ($user['last_login']): ?>
                        <div class="mb-3">
                            <label class="form-label text-muted">כניסה אחרונה:</label>
                            <div><?= date('d/m/Y H:i', strtotime($user['last_login'])) ?></div>
                        </div>
                    <?php endif; ?>

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
// Client-side validation
$extraJs = <<<JS
// Form validation
(function() {
    'use strict';
    
    const form = document.querySelector('.needs-validation');
    const passwordInput = form.querySelector('#password');
    
    form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        // Validate password only if it's not empty
        if (passwordInput.value !== '') {
            if (passwordInput.value.length < 8 || 
                !/[A-Z]/.test(passwordInput.value) || 
                !/[a-z]/.test(passwordInput.value) || 
                !/[0-9]/.test(passwordInput.value)) {
                
                passwordInput.setCustomValidity('הסיסמה אינה עומדת בדרישות');
                event.preventDefault();
                event.stopPropagation();
            } else {
                passwordInput.setCustomValidity('');
            }
        } else {
            passwordInput.setCustomValidity('');
        }
        
        form.classList.add('was-validated');
    }, false);
})();
JS;

require_once '../../includes/templates/footer.php';
?>
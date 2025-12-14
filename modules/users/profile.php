<?php
// modules/users/profile.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Verify user is logged in
requireLogin();

// Page configuration
$pageTitle = 'פרופיל משתמש';
$pageHeader = 'הפרופיל שלי';
$pageHeaderButtons = '<a href="../../public" class="btn btn-secondary">חזרה לדף הבית</a>';

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
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['message'] = 'משתמש לא נמצא';
        $_SESSION['message_type'] = 'danger';
        redirectTo('/');
    }

} catch (PDOException $e) {
    $_SESSION['message'] = 'שגיאה בטעינת נתוני המשתמש';
    $_SESSION['message_type'] = 'danger';
    redirectTo('/');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate email
        if (empty($_POST['email'])) {
            throw new Exception('דוא"ל הוא שדה חובה');
        }

        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        if (!$email) {
            throw new Exception('כתובת דוא"ל לא תקינה');
        }

        // Check if email is taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user['id']]);
        if ($stmt->fetch()) {
            throw new Exception('כתובת הדוא"ל כבר קיימת במערכת');
        }

        // Build update query
        $updateFields = ['email = ?'];
        $params = [$email];

        // Handle password update if provided
        if (!empty($_POST['new_password'])) {
            // Verify current password
            if (empty($_POST['current_password'])) {
                throw new Exception('יש להזין את הסיסמה הנוכחית');
            }

            if (!password_verify($_POST['current_password'], $user['password'])) {
                throw new Exception('הסיסמה הנוכחית שגויה');
            }

            $newPassword = $_POST['new_password'];

            // Validate password strength
            if (strlen($newPassword) < 8) {
                throw new Exception('הסיסמה החדשה חייבת להכיל לפחות 8 תווים');
            }

            if (!preg_match("/[A-Z]/", $newPassword) || 
                !preg_match("/[a-z]/", $newPassword) || 
                !preg_match("/[0-9]/", $newPassword)) {
                throw new Exception('הסיסמה חייבת להכיל אותיות גדולות, קטנות ומספרים');
            }

            $updateFields[] = 'password = ?';
            $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        // Add user ID to params
        $params[] = $user['id'];

        // Update user
        $stmt = $pdo->prepare("
            UPDATE users 
            SET " . implode(', ', $updateFields) . ", updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute($params);

        $_SESSION['message'] = 'הפרטים עודכנו בהצלחה';
        $_SESSION['message_type'] = 'success';
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $user = $stmt->fetch();

    } catch (Exception $e) {
        $_SESSION['message'] = $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
}

require_once '../../includes/templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>פרטי חשבון</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th>שם משתמש</th>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                            </tr>
                            <tr>
                                <th>תפקיד</th>
                                <td>
                                    <span class="badge bg-primary">
                                        <?= htmlspecialchars($user['role_name']) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>נוצר בתאריך</th>
                                <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                            </tr>
                            <tr>
                                <th>כניסה אחרונה</th>
                                <td>
                                    <?= $user['last_login'] 
                                        ? date('d/m/Y H:i', strtotime($user['last_login']))
                                        : 'טרם התחבר' ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <h5>עדכון פרטים</h5>
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">דוא"ל*</label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email"
                               value="<?= htmlspecialchars($user['email']) ?>"
                               required>
                    </div>

                    <hr>

                    <h5>שינוי סיסמה</h5>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">סיסמה נוכחית</label>
                        <input type="password" 
                               class="form-control" 
                               id="current_password" 
                               name="current_password">
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">סיסמה חדשה</label>
                        <input type="password" 
                               class="form-control" 
                               id="new_password" 
                               name="new_password">
                        <div class="form-text">
                            להחלפת סיסמה, יש למלא את הסיסמה הנוכחית והחדשה.
                            הסיסמה החדשה חייבת להכיל לפחות 8 תווים, אות גדולה, אות קטנה ומספר.
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="/" class="btn btn-secondary">חזרה</a>
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
    const currentPassword = form.querySelector('#current_password');
    const newPassword = form.querySelector('#new_password');
    
    form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        // Validate password only if one of the password fields is filled
        if (currentPassword.value || newPassword.value) {
            if (!currentPassword.value) {
                currentPassword.setCustomValidity('יש להזין את הסיסמה הנוכחית');
                event.preventDefault();
            } else {
                currentPassword.setCustomValidity('');
            }
            
            if (newPassword.value) {
                if (newPassword.value.length < 8 || 
                    !/[A-Z]/.test(newPassword.value) || 
                    !/[a-z]/.test(newPassword.value) || 
                    !/[0-9]/.test(newPassword.value)) {
                    
                    newPassword.setCustomValidity('הסיסמה אינה עומדת בדרישות');
                    event.preventDefault();
                } else {
                    newPassword.setCustomValidity('');
                }
            }
        }
        
        form.classList.add('was-validated');
    }, false);
})();
JS;

require_once '../../includes/templates/footer.php';
?>
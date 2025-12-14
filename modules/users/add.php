<?php
// modules/users/add.php
//session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Page configuration
$pageTitle = 'הוספת משתמש חדש';
$pageHeader = 'הוספת משתמש';
$pageHeaderButtons = '<a href="list.php" class="btn btn-secondary">חזרה לרשימה</a>';

require_once '../../includes/templates/header.php';

// Get available roles
try {
    $roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll();
} catch (PDOException $e) {
    $_SESSION['message'] = 'שגיאה בטעינת תפקידים';
    $_SESSION['message_type'] = 'danger';
    redirectTo('list.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password'])) {
            throw new Exception('כל שדות החובה חייבים להיות מלאים');
        }

        $username = sanitizeInput($_POST['username']);
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'];
        $roleId = filter_var($_POST['role_id'], FILTER_VALIDATE_INT);

        if (!$email) {
            throw new Exception('כתובת האימייל אינה תקינה');
        }

        // Validate password strength
        if (strlen($password) < 8) {
            throw new Exception('הסיסמה חייבת להכיל לפחות 8 תווים');
        }

        if (!preg_match("/[A-Z]/", $password) || 
            !preg_match("/[a-z]/", $password) || 
            !preg_match("/[0-9]/", $password)) {
            throw new Exception('הסיסמה חייבת להכיל אותיות גדולות, קטנות ומספרים');
        }

        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            throw new Exception('שם המשתמש או כתובת האימייל כבר קיימים במערכת');
        }

        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users (
                username, 
                email, 
                password, 
                role_id,
                is_active,
                created_at
            ) VALUES (?, ?, ?, ?, 1, NOW())
        ");

        $stmt->execute([
            $username,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $roleId
        ]);

        $_SESSION['message'] = 'המשתמש נוצר בהצלחה';
        $_SESSION['message_type'] = 'success';
        redirectTo('list.php');

    } catch (Exception $e) {
        $_SESSION['message'] = $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
}
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
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">דוא"ל*</label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">סיסמה*</label>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password"
                               required>
                        <div class="form-text">
                            הסיסמה חייבת להכיל לפחות 8 תווים, אות גדולה, אות קטנה ומספר
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="role_id" class="form-label">תפקיד*</label>
                        <select class="form-select" 
                                id="role_id" 
                                name="role_id"
                                required>
                            <option value="">בחר תפקיד</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= $role['id'] ?>"
                                        <?= (isset($_POST['role_id']) && $_POST['role_id'] == $role['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($role['role_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="list.php" class="btn btn-secondary">ביטול</a>
                        <button type="submit" class="btn btn-primary">צור משתמש</button>
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
    
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            // Additional password validation
            const password = form.querySelector('#password');
            if (password.value.length < 8 || 
                !/[A-Z]/.test(password.value) || 
                !/[a-z]/.test(password.value) || 
                !/[0-9]/.test(password.value)) {
                
                password.setCustomValidity('הסיסמה אינה עומדת בדרישות');
                event.preventDefault();
                event.stopPropagation();
            } else {
                password.setCustomValidity('');
            }
            
            form.classList.add('was-validated');
        }, false);
    });
})();
JS;

require_once '../../includes/templates/footer.php';
?>
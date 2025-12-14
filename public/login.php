<?php
// public/login.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'יש להזין שם משתמש וסיסמה';
    } else {
        try {
            // Get user details including role name
            $stmt = $pdo->prepare("
                SELECT users.*, roles.role_name 
                FROM users 
                LEFT JOIN roles ON users.role_id = roles.id 
                WHERE username = ? AND is_active = 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['role_name'] = $user['role_name'];
                
                // Update last login
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Redirect based on role
                if ($user['role_name'] === 'admin') {
                    redirectTo('../admin/dashboard.php');
                } else {
                    redirectTo('./');
                }
            } else {
                $error = 'שם משתמש או סיסמה שגויים';
            }
        } catch (PDOException $e) {
            $error = 'אירעה שגיאה בהתחברות';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>התחברות למערכת</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	 <link href="../../public/assets/css/style.css?v=<?= time(); ?>" rel="stylesheet">
 
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="card shadow">
                <div class="card-header">
                    <h3 class="text-center mb-0">התחברות למערכת</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">שם משתמש</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                   required 
                                   autofocus>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">סיסמה</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">התחבר</button>
                    </form>
                </div>
            </div>
            <div class="text-center mt-3">
                <a href="/" class="text-muted">חזרה לדף הבית</a>
            </div>
        </div>
    </div>
</body>
</html>
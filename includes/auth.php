<?php
// config/auth.php
function authenticateUser($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            r.role_name
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.username = ? AND u.is_active = 1
    ");
    
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    
    return false;
}

// public/login.php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    redirectTo('admin/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'יש להזין שם משתמש וסיסמה';
    } else {
        $user = authenticateUser($username, $password);
        
        if ($user) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role_name'];
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Redirect to dashboard
            redirectTo('admin/dashboard.php');
        } else {
            $error = 'שם משתמש או סיסמה שגויים';
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
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo img {
            max-width: 150px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="logo">
                <h1>מערכת ניהול סושיאל</h1>
            </div>
            
            <div class="card shadow">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="username" class="form-label">שם משתמש</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   required 
                                   autofocus>
                            <div class="invalid-feedback">
                                יש להזין שם משתמש
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">סיסמה</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   required>
                            <div class="invalid-feedback">
                                יש להזין סיסמה
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   id="remember" 
                                   name="remember">
                            <label class="form-check-label" for="remember">זכור אותי</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">התחבר</button>
                    </form>
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="forgot-password.php" class="text-muted">שכחת סיסמה?</a>
            </div>
        </div>
    </div>

    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.needs-validation');
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    </script>
</body>
</html>
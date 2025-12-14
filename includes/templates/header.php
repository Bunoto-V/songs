<?php
// includes/templates/header.php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check if user is logged in
requireLogin();

// Get current user data
$currentUser = getCurrentUser();
if (!$currentUser) {
    session_destroy();
    redirectTo('../public/login.php');
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'מערכת ניהול' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../../public/assets/css/style.css?v=<?= time(); ?>" rel="stylesheet">
    <?php if (isset($extraStyles)): ?>
        <?php foreach ($extraStyles as $style): ?>
            <link href="<?= $style ?>?v=<?= time(); ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>

<?php //include '../../includes/templates/toast.html';
//if (hasPermission('manage_categories')): 

 ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="https://bunoto.com/social/c/public">מאגר תוכן</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <!-- תפריט ניהול - רק למנהל מערכת -->
                <?php if (hasPermission('manage_users')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/modules/users/list.php">
                            <i class="bi bi-people"></i> ניהול משתמשים
                        </a>
                    </li>
                <?php endif; ?>

                <!-- תפריט ניהול תוכן - למנהל מערכת ומנהל תוכן -->
              
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="contentDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear"></i> ניהול תוכן
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="https://bunoto.com/social/c/modules/categories/list.php">
                                    <i class="bi bi-tags"></i> ניהול קטגוריות
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="https://bunoto.com/social/c/modules/songs/list.php">
                                    <i class="bi bi-music-note"></i> ניהול שירים
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="https://bunoto.com/social/c/modules/logos/list.php">
                                    <i class="bi bi-image"></i> ניהול לוגואים
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="https://bunoto.com/social/c/modules/plugins/list.php">
                                    <i class="bi bi-plugin"></i> ניהול פלאגינים
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="https://bunoto.com/social/c/modules/stories/list.php">
                                    <i class="bi bi-film"></i> תוכן לסטוריז
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="https://bunoto.com/social/c/modules/clients/list.php">
                                    <i class="bi bi-people"></i> ניהול לקוחות
                                </a>
                            </li>
                        </ul>
                    </li>
              
            </ul>

            <!-- תפריט משתמש -->
            <div class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <?= htmlspecialchars($_SESSION['username']) ?>
                            <?php
                            $roleNames = [
                                'admin' => 'מנהל מערכת',
                                'manager' => 'מנהל תוכן',
                                'user' => 'משתמש'
                            ];
						/*  <small>(<?= $roleNames[$_SESSION['role']] ?? $_SESSION['role'] ?>)</small>*/
                            ?>
                          
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if (hasPermission('manage_users')): ?>
                                <li>
                                    <a class="dropdown-item" href="../c/admin/dashboard.php">
                                        <i class="bi bi-speedometer2"></i> לוח בקרה
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item" href="../modules/users/profile.php">
                                    <i class="bi bi-person"></i> פרופיל
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="../admin/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> התנתק
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
    <?php if (isset($pageHeader)): ?>
    <div class="bg-light py-3 mb-4">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h2 mb-0"><?= $pageHeader ?></h1>
                <?php if (isset($pageHeaderButtons)): ?>
                    <div class="btn-group">
                        <?= $pageHeaderButtons ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <main class="container mt-4">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] ?? 'info' ?> alert-dismissible fade show">
                <?= $_SESSION['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>
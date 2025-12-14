<?php
// admin/dashboard.php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/templates/header.php';

// Page configuration
$pageTitle = 'לוח בקרה';
$pageHeader = 'לוח בקרה';

// Example statistics - you can replace these with real data queries if needed
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalClients = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$totalSongs = $pdo->query("SELECT COUNT(*) FROM songs")->fetchColumn();

?>

<div class="container mt-4">
    <div class="row">
        <!-- Cards for basic statistics -->
        <div class="col-md-3">
            <div class="card text-center shadow">
                <div class="card-body">
                    <h5 class="card-title">משתמשים</h5>
                    <p class="card-text display-4"><?= $totalUsers ?></p>
                    <a href="../modules/users/list.php" class="btn btn-primary">ניהול משתמשים</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center shadow">
                <div class="card-body">
                    <h5 class="card-title">לקוחות</h5>
                    <p class="card-text display-4"><?= $totalClients ?></p>
                    <a href="../modules/clients/list.php" class="btn btn-primary">ניהול לקוחות</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center shadow">
                <div class="card-body">
                    <h5 class="card-title">שירים</h5>
                    <p class="card-text display-4"><?= $totalSongs ?></p>
                    <a href="../modules/songs/list.php" class="btn btn-primary">ניהול שירים</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center shadow">
                <div class="card-body">
                    <h5 class="card-title">לוגואים</h5>
                    <p class="card-text display-4">
                        <?php
                        try {
                            echo $pdo->query("SELECT COUNT(*) FROM logos")->fetchColumn();
                        } catch (PDOException $e) {
                            echo '0';
                        }
                        ?>
                    </p>
                    <a href="../modules/logos/list.php" class="btn btn-primary">ניהול לוגואים</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Access Links -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">גישה מהירה למודולים</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-3">
                        <a href="../modules/categories/list.php" class="btn btn-outline-secondary">
                            <i class="bi bi-tags"></i> ניהול קטגוריות
                        </a>
                        <a href="../modules/clients/list.php" class="btn btn-outline-secondary">
                            <i class="bi bi-people"></i> ניהול לקוחות
                        </a>
                        <a href="../modules/songs/list.php" class="btn btn-outline-primary">
                            <i class="bi bi-music-note"></i> ניהול שירים
                        </a>
                        <a href="../modules/logos/list.php" class="btn btn-outline-info">
                            <i class="bi bi-image"></i> ניהול לוגואים
                        </a>
                        <a href="../modules/plugins/list.php" class="btn btn-outline-success">
                            <i class="bi bi-plugin"></i> ניהול פלאגינים
                        </a>
                        <a href="../modules/stories/list.php" class="btn btn-outline-warning">
                            <i class="bi bi-film"></i> תוכן לסטוריז
                        </a>
                        <a href="../modules/stats/dashboard.php" class="btn btn-outline-secondary">
                            <i class="bi bi-bar-chart"></i> סטטיסטיקות
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/templates/footer.php';
?>

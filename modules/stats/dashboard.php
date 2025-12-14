<?php
// modules/stats/dashboard.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
hasPermission('view_stats');

// קביעת טווח תאריכים
$endDate = date('Y-m-d');
$startDate = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end']) ? $_GET['end'] : $endDate;

// שליפת סטטיסטיקות של הורדות
$downloadStats = $pdo->prepare("
    SELECT 
        DATE(last_downloaded) as date,
        COUNT(*) as download_count
    FROM song_metadata
    WHERE last_downloaded BETWEEN ? AND ?
    GROUP BY DATE(last_downloaded)
    ORDER BY date ASC
");
$downloadStats->execute([$startDate, $endDate]);
$downloads = $downloadStats->fetchAll(PDO::FETCH_ASSOC);

// שליפת השירים המובילים
$topSongs = $pdo->prepare("
    SELECT 
        s.title,
        COUNT(sm.download_count) as download_count
    FROM songs s
    LEFT JOIN song_metadata sm ON s.id = sm.song_id
    WHERE sm.download_count > 0
    GROUP BY s.id
    ORDER BY download_count DESC
    LIMIT 10
");
$topSongs->execute();
$popularSongs = $topSongs->fetchAll();
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>סטטיסטיקות מערכת</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>סטטיסטיקות מערכת</h1>
            <form class="d-flex gap-2">
                <input type="date" name="start" value="<?= $startDate ?>" class="form-control">
                <input type="date" name="end" value="<?= $endDate ?>" class="form-control">
                <button type="submit" class="btn btn-primary">סנן</button>
            </form>
        </div>

        <div class="row">
            <!-- גרף הורדות -->
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">הורדות לאורך זמן</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="downloadsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- שירים פופולריים -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">שירים פופולריים</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <?php foreach ($popularSongs as $song): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= htmlspecialchars($song['title']) ?>
                                    <span class="badge bg-primary"><?= number_format($song['download_count']) ?> הורדות</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // יצירת גרף הורדות עם Chart.js
        const downloadsCtx = document.getElementById('downloadsChart').getContext('2d');
        const downloadChart = new Chart(downloadsCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($downloads, 'date')) ?>,
                datasets: [{
                    label: 'הורדות',
                    data: <?= json_encode(array_column($downloads, 'download_count')) ?>,
                    borderColor: '#007bff',
                    fill: false,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>

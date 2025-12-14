<?php
// modules/social/manager.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
requirePermission('manage_social');

// Get social platforms configuration
$platforms = [
    'facebook' => [
        'name' => 'Facebook',
        'icon' => 'bi-facebook',
        'color' => '#1877F2'
    ],
    'instagram' => [
        'name' => 'Instagram',
        'icon' => 'bi-instagram',
        'color' => '#E4405F'
    ],
    'tiktok' => [
        'name' => 'TikTok',
        'icon' => 'bi-tiktok',
        'color' => '#000000'
    ]
];

// Get scheduled posts
$scheduledPosts = $pdo->query("
    SELECT 
        sp.*,
        s.title as song_title,
        c.client_name,
        GROUP_CONCAT(DISTINCT p.platform_name) as platforms
    FROM social_posts sp
    LEFT JOIN songs s ON sp.song_id = s.id
    LEFT JOIN clients c ON s.client_id = c.id
    LEFT JOIN post_platforms p ON sp.id = p.post_id
    WHERE sp.scheduled_time > NOW()
    GROUP BY sp.id
    ORDER BY sp.scheduled_time ASC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ניהול סושיאל מדיה</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .platform-icon {
            font-size: 1.5rem;
            margin: 0 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>ניהול סושיאל מדיה</h1>
            <a href="schedule_post.php" class="btn btn-primary">תזמן פוסט חדש</a>
        </div>

        <!-- Platform Overview -->
        <div class="row mb-4">
            <?php foreach ($platforms as $key => $platform): ?>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="bi <?= $platform['icon'] ?> platform-icon" 
                               style="color: <?= $platform['color'] ?>"></i>
                            <div>
                                <h5 class="card-title mb-0"><?= $platform['name'] ?></h5>
                                <p class="card-text text-muted">
                                    <?= $pdo->query("
                                        SELECT COUNT(*) FROM post_platforms 
                                        WHERE platform_name = '$key'
                                    ")->fetchColumn() ?> פוסטים מתוזמנים
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Calendar View -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">לוח זמנים</h5>
            </div>
            <div class="card-body">
                <div id="socialCalendar"></div>
            </div>
        </div>

        <!-- Scheduled Posts -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">פוסטים מתוזמנים</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>תאריך</th>
                                <th>שיר</th>
                                <th>לקוח</th>
                                <th>פלטפורמות</th>
                                <th>סטטוס</th>
                                <th>פעולות</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scheduledPosts as $post): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($post['scheduled_time'])) ?></td>
                                <td><?= htmlspecialchars($post['song_title']) ?></td>
                                <td><?= htmlspecialchars($post['client_name']) ?></td>
                                <td>
                                    <?php foreach (explode(',', $post['platforms']) as $platform): ?>
                                        <i class="bi <?= $platforms[$platform]['icon'] ?>"
                                           style="color: <?= $platforms[$platform]['color'] ?>"></i>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <span class="badge bg-primary">מתוזמן</span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="edit_post.php?id=<?= $post['id'] ?>"
                                           class="btn btn-sm btn-outline-primary">ערוך</a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="deletePost(<?= $post['id'] ?>)">
                                            מחק
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('socialCalendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'he',
                direction: 'rtl',
                events: <?= json_encode(array_map(function($post) {
                    return [
                        'title' => $post['song_title'],
                        'start' => $post['scheduled_time'],
                        'backgroundColor' => '#4a90e2'
                    ];
                }, $scheduledPosts)) ?>,
                eventClick: function(info) {
                    // Handle event click
                    const postId = info.event.id;
                    window.location.href = `edit_post.php?id=${postId}`;
                }
            });
            calendar.render();
        });

        function deletePost(id) {
            if (confirm('האם אתה בטוח שברצונך למחוק את הפוסט המתוזמן?')) {
                fetch(`delete_post.php?id=${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('אירעה שגיאה במחיקת הפוסט');
                    }
                });
            }
        }
    </script>
</body>
</html>
<?php
// modules/player/player.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Get song details
$songId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$songId) {
    die('Invalid song ID');
}

$stmt = $pdo->prepare("
    SELECT 
        s.*,
        c.category_name,
        cl.client_name,
        cl.logo_path,
        sm.duration,
        sm.bpm,
        sm.key_signature,
        GROUP_CONCAT(t.tag_name) as tags
    FROM songs s
    LEFT JOIN categories c ON s.category_id = c.id
    LEFT JOIN clients cl ON s.client_id = cl.id
    LEFT JOIN song_metadata sm ON s.id = sm.song_id
    LEFT JOIN song_tags st ON s.id = st.song_id
    LEFT JOIN tags t ON st.tag_id = t.id
    WHERE s.id = ?
    GROUP BY s.id
");
$stmt->execute([$songId]);
$song = $stmt->fetch();

if (!$song) {
    die('Song not found');
}
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($song['title']) ?> - נגן שירים</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .player-container {
            max-width: 600px;
            margin: 2rem auto;
        }
        .song-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .waveform-container {
            background: #eee;
            border-radius: 8px;
            margin: 1rem 0;
            height: 120px;
        }
        .tag {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background: #e9ecef;
            border-radius: 4px;
            margin: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="player-container">
            <div class="song-info">
                <div class="d-flex align-items-center mb-3">
                    <?php if ($song['logo_path']): ?>
                        <img src="<?= htmlspecialchars($song['logo_path']) ?>" 
                             alt="<?= htmlspecialchars($song['client_name']) ?>"
                             style="width: 50px; height: 50px; object-fit: contain; margin-left: 1rem;">
                    <?php endif; ?>
                    <div>
                        <h2 class="mb-0"><?= htmlspecialchars($song['title']) ?></h2>
                        <p class="text-muted mb-0"><?= htmlspecialchars($song['client_name']) ?></p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>קטגוריה:</strong> <?= htmlspecialchars($song['category_name']) ?></p>
                        <?php if ($song['duration']): ?>
                            <p><strong>אורך:</strong> <?= htmlspecialchars($song['duration']) ?></p>
                        <?php endif; ?>
                        <?php if ($song['bpm']): ?>
                            <p><strong>BPM:</strong> <?= htmlspecialchars($song['bpm']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <?php if ($song['key_signature']): ?>
                            <p><strong>סולם:</strong> <?= htmlspecialchars($song['key_signature']) ?></p>
                        <?php endif; ?>
                        <?php if ($song['tags']): ?>
                            <div>
                                <strong>תגיות:</strong><br>
                                <?php foreach (explode(',', $song['tags']) as $tag): ?>
                                    <span class="tag"><?= htmlspecialchars($tag) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="waveform-container" id="waveform"></div>

            <div class="d-flex justify-content-between align-items-center">
                <div class="btn-group">
                    <button id="playBtn" class="btn btn-primary">
                        <i class="bi bi-play-fill"></i>
                    </button>
                    <button id="stopBtn" class="btn btn-secondary">
                        <i class="bi bi-stop-fill"></i>
                    </button>
                </div>
                <span id="timer">00:00 / 00:00</span>
                <div>
                    <a href="<?= $song['file_path'] ?>" 
                       class="btn btn-success"
                       download
                       onclick="incrementDownload(<?= $song['id'] ?>)">
                        הורד שיר
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/wavesurfer.js@7"></script>
    <script>
        // Initialize WaveSurfer
        const wavesurfer = WaveSurfer.create({
            container: '#waveform',
            waveColor: '#4a90e2',
            progressColor: '#2c5282',
            cursorColor: '#2c5282',
            barWidth: 2,
            barRadius: 3,
            cursorWidth: 1,
            height: 100,
            barGap: 3
        });

        // Load audio file
        wavesurfer.load('<?= $song['file_path'] ?>');

        // UI controls
        const playBtn = document.getElementById('playBtn');
        const stopBtn = document.getElementById('stopBtn');
        const timer = document.getElementById('timer');

        playBtn.onclick = () => {
            wavesurfer.playPause();
            playBtn.innerHTML = wavesurfer.isPlaying() ? 
                '<i class="bi bi-pause-fill"></i>' : 
                '<i class="bi bi-play-fill"></i>';
        };

        stopBtn.onclick = () => {
            wavesurfer.stop();
            playBtn.innerHTML = '<i class="bi bi-play-fill"></i>';
        };

        // Update timer
        wavesurfer.on('audioprocess', () => {
				const formatTime = (time) => {
                const minutes = Math.floor(time / 60);
                const seconds = Math.floor(time % 60);
                return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            };

            const current = formatTime(wavesurfer.getCurrentTime());
            const total = formatTime(wavesurfer.getDuration());
            timer.textContent = `${current} / ${total}`;
        });

        // Track downloads
        async function incrementDownload(songId) {
            try {
                await fetch('increment_download.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ songId })
                });
            } catch (error) {
                console.error('Error tracking download:', error);
            }
        }

        // Add keyboard controls
        document.addEventListener('keydown', (e) => {
            if (e.code === 'Space') {
                e.preventDefault();
                wavesurfer.playPause();
                playBtn.innerHTML = wavesurfer.isPlaying() ? 
                    '<i class="bi bi-pause-fill"></i>' : 
                    '<i class="bi bi-play-fill"></i>';
            }
        });
    </script>
</body>
</html>
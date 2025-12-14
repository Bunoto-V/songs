<?php
require_once 'config.php';
require_once 'functions.php';

// Handle Delete
if (isset($_POST['delete_song'])) {
    $songId = $_POST['song_id'];
    try {
        $sql = "DELETE FROM songs WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$songId]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        $error = 'שגיאה במחיקת השיר';
    }
}
// Regular search
	$search = $_GET['search'] ?? '';
	$sqlSearch = "%" . $search . "%";
	$sql = "SELECT * FROM songs WHERE title_he LIKE :srch OR title_en LIKE :srch ORDER BY id DESC";
	$stmt = $pdo->prepare($sql);
	$stmt->bindParam(":srch", $sqlSearch);
	$stmt->execute();
	$songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>שירים</title>
    <link rel="stylesheet" href="style.css?ver=<?= time(); ?>">
    <style>

    </style>
</head>
<body>
    <nav>
        <div class="nav-content">
            <div class="nav-title">
                <img src="vl-logo.png" alt="Logo">
                <h1>היפוך טקסט</h1>
            </div>
            <div class="nav-links">
                <a href="./" class="active">שירים</a>
                <a href="rev.php">היפוך טקסט</a>
            </div>
        </div>
    </nav>
	<a href="rev.php">היפוך טקסט</a>
    <div class="container">
        <div class="search-form">
            <h2>חיפוש שירים</h2>
            <form method="get">
                <input
                    type="text"
                    name="search"
                    placeholder="חפש שיר בעברית או באנגלית"
                    value="<?php echo htmlspecialchars($search); ?>"
                >
                <button type="submit">חיפוש</button>
            </form>
        </div>

        <div class="song-grid">
    <?php foreach($songs as $song): ?>
        <div class="song-card">
            <div class="song-header">
                <h3><?php echo htmlspecialchars($song['title_he'] . " / " . $song['title_en']); ?></h3>
            </div>
            <div class="song-content">
                <!-- Existing action buttons -->
                <div class="action-buttons">
                    <?php if (!empty($song['youtube_link'])): ?>
                        <a href="<?php echo htmlspecialchars($song['youtube_link']); ?>" 
                           target="_blank" 
                           class="btn">
                            צפה ביוטיוב
                        </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($song['lyrics'])): ?>
                        <button class="btn btn-secondary" 
                                onclick="showLyrics(`<?php echo str_replace(["\r\n", "\r", "\n"], '<br>', htmlspecialchars($song['lyrics'])); ?>`)">
                            הצג מילים
                        </button>
                    <?php endif; ?>

                    <?php if (!empty($song['xml_content'])): ?>
                        <button class="btn btn-secondary" 
                                onclick="copyToClipboard(`<?php echo str_replace("\n", "\\n", htmlspecialchars($song['xml_content'])); ?>`)">
                            העתק XML
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Add admin buttons -->
                <div class="admin-buttons">
                    <button class="btn edit-btn" onclick="showEditForm(<?php echo $song['id']; ?>)">ערוך</button>
                    <button class="btn delete-btn" onclick="confirmDelete(<?php echo $song['id']; ?>)">מחק</button>
                </div>

                <!-- Images remain the same -->
                <?php
                $images = getImagesForSong($pdo, $song['id']);
                if ($images): ?>
                    <div class="song-images">
                        <?php foreach ($images as $img): ?>
                            <img src="<?php echo htmlspecialchars($img['image_path']); ?>" 
                                 onclick="showModal(this.src)" 
                                 alt="תמונה לשיר" />
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Edit form for this song -->
        <div id="edit-form-<?php echo $song['id']; ?>" class="edit-form">
            <h3>עריכת שיר</h3>
            <label>כותרת בעברית:</label>
            <input type="text" id="title-he-<?php echo $song['id']; ?>" 
                   value="<?php echo htmlspecialchars($song['title_he']); ?>">
            
            <label>כותרת באנגלית:</label>
            <input type="text" id="title-en-<?php echo $song['id']; ?>"
                   value="<?php echo htmlspecialchars($song['title_en']); ?>">
            
            <label>קישור יוטיוב:</label>
            <input type="text" id="youtube-<?php echo $song['id']; ?>"
                   value="<?php echo htmlspecialchars($song['youtube_link']); ?>">
            
            <label>מילים:</label>
            <textarea id="lyrics-<?php echo $song['id']; ?>"><?php echo htmlspecialchars($song['lyrics']); ?></textarea>
            
            <label>תוכן XML:</label>
            <textarea id="xml-<?php echo $song['id']; ?>"><?php echo htmlspecialchars($song['xml_content']); ?></textarea>
            
            <div class="edit-form-buttons">
                <button class="btn" onclick="updateSong(<?php echo $song['id']; ?>)">שמור</button>
                <button class="btn btn-secondary" onclick="hideEditForm(<?php echo $song['id']; ?>)">ביטול</button>
            </div>
        </div>
    <?php endforeach; ?>

        </div>
    </div>

    <!-- Modals -->
    <div class="modal" id="imageModal">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <img id="modalImg" src="" alt="" />
    </div>

    <div class="overlay" id="overlay"></div>
    <div class="lyrics-popup" id="lyricsPopup">
        <span class="close-popup" onclick="closeLyrics()">&times;</span>
        <div id="lyricsContent"></div>
    </div>
    <!-- Add confirm delete modal -->
    <div class="confirm-delete" id="confirmDelete">
        <h3>האם אתה בטוח שברצונך למחוק את השיר?</h3>
        <div class="confirm-buttons">
            <form id="deleteForm" method="post">
                <input type="hidden" name="delete_song" value="1">
                <input type="hidden" name="song_id" id="deleteId">
                <button type="submit" class="delete-btn">מחק</button>
                <button type="button" class="btn" onclick="cancelDelete()">ביטול</button>
            </form>
        </div>
    </div>
    <script>

        function showEditForm(songId) {
            document.getElementById(`edit-form-${songId}`).style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
        }

        function hideEditForm(songId) {
            document.getElementById(`edit-form-${songId}`).style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
        }

        async function updateSong(songId) {
            const data = {
                id: songId,
                title_he: document.getElementById(`title-he-${songId}`).value,
                title_en: document.getElementById(`title-en-${songId}`).value,
                youtube_link: document.getElementById(`youtube-${songId}`).value,
                lyrics: document.getElementById(`lyrics-${songId}`).value,
                xml_content: document.getElementById(`xml-${songId}`).value
            };

            try {
                const response = await fetch('update_song.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                if (response.ok) {
                    location.reload();
                } else {
                    alert('אירעה שגיאה בעדכון השיר');
                }
            } catch (error) {
                alert('אירעה שגיאה בעדכון השיר');
            }
        }

        function confirmDelete(songId) {
            document.getElementById('deleteId').value = songId;
            document.getElementById('confirmDelete').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
        }

        function cancelDelete() {
            document.getElementById('confirmDelete').style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
        }

        // Update overlay click handler
        document.getElementById('overlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLyrics();
                cancelDelete();
                // Hide all edit forms
                document.querySelectorAll('.edit-form').forEach(form => {
                    form.style.display = 'none';
                });
            }
        });
		
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text)
                .then(() => {
                    alert('הטקסט הועתק!');
                })
                .catch(err => {
                    console.error('Copy failed', err);
                });
        }

        function showModal(src) {
            document.getElementById('imageModal').style.display = 'block';
            document.getElementById('modalImg').src = src;
        }

        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        function showLyrics(lyrics) {
            document.getElementById('lyricsContent').innerHTML = lyrics;
            document.getElementById('lyricsPopup').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
        }

        function closeLyrics() {
            document.getElementById('lyricsPopup').style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
        }

        // Close popups when clicking outside
        document.getElementById('overlay').addEventListener('click', closeLyrics);
        window.addEventListener('click', function(event) {
            if (event.target === document.getElementById('imageModal')) {
                closeModal();
            }
        });
    </script>
</body>
</html>
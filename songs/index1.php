<?php
/**
 * דוגמה למערכת ניהול שירים ב-PHP + MySQL.
 * הקוד הזה מדגים קבצי SQL, קוד PHP וטפסים עבור ממשק ניהול (admin) וממשק משתמש (index) עם יכולות:
 * - העלאת שירים (עברית/אנגלית)
 * - העלאת תמונות בשיטת drag & drop
 * - עמוד למשתמש לחיפוש והצגת שירים
 * - אפשרות להגדיל תמונות
 * - כפתור copy-to-clipboard לקישור יוטיוב, מילים ו-XML.
 * - עיצוב ראשוני ב-orange/black, עם Nav bar.
 * יש צורך לעדכן את הגדרות החיבור ל-DB לפי הצורך.
 * שים לב שבמערכת אמיתית יש לדאוג לאבטחה, ולפזר את הקוד למספר קבצים (config, פונקציות, וכו').
 * כאן הכול ברמת דוגמה בסיסית בקובץ אחד.
 */

/************************
 * קובץ SQL להקמת הטבלאות:
 ************************
-- CREATE DATABASE IF NOT EXISTS my_songs_db;
-- USE my_songs_db;
-- CREATE TABLE songs (
--   id INT AUTO_INCREMENT PRIMARY KEY,
--   title_he VARCHAR(255) NOT NULL,
--   title_en VARCHAR(255) NOT NULL,
--   youtube_link VARCHAR(500),
--   lyrics TEXT,
--   xml_content TEXT
-- );
-- CREATE TABLE images (
--   id INT AUTO_INCREMENT PRIMARY KEY,
--   song_id INT,
--   image_path VARCHAR(500),
--   FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
-- );


/*****************
 * הגדרות חיבור DB
 *****************/
$host = "localhost";        // שם השרת
$dbname = "my_songs_db";    // שם המסד
$user = "adm_tkc";          // שם המשתמש
$pass = "a1367$1661va";     // סיסמה

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "שגיאה בחיבור למסד נתונים: " . $e->getMessage();
    exit;
}

// פונקציה להוספת שיר חדש
function addSong($pdo, $titleHe, $titleEn, $youtubeLink, $lyrics, $xmlContent) {
    $sql = "INSERT INTO songs (title_he, title_en, youtube_link, lyrics, xml_content) VALUES (:he, :en, :yt, :ly, :xml)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":he", $titleHe);
    $stmt->bindParam(":en", $titleEn);
    $stmt->bindParam(":yt", $youtubeLink);
    $stmt->bindParam(":ly", $lyrics);
    $stmt->bindParam(":xml", $xmlContent);
    $stmt->execute();
    return $pdo->lastInsertId();
}

// פונקציה להוספת תמונה לשיר
function addImage($pdo, $songId, $imagePath) {
    $sql = "INSERT INTO images (song_id, image_path) VALUES (:sid, :ipath)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":sid", $songId);
    $stmt->bindParam(":ipath", $imagePath);
    $stmt->execute();
}

/*****************************************
 * בדיקה האם אנחנו ב'מצב ניהול' או 'משתמש'
 *****************************************/
$adminMode = isset($_GET['admin']);

// אם במצב ניהול, נטפל בהעלאת שירים ותמונות
if ($adminMode) {
    // טיפול בהעלאת שיר חדש
    if (isset($_POST['title_he']) && isset($_POST['title_en'])) {
        $titleHe = $_POST['title_he'] ?? '';
        $titleEn = $_POST['title_en'] ?? '';
        $youtubeLink = $_POST['youtube_link'] ?? '';
        $lyrics = $_POST['lyrics'] ?? '';
        $xmlContent = $_POST['xml_content'] ?? '';

        $newSongId = addSong($pdo, $titleHe, $titleEn, $youtubeLink, $lyrics, $xmlContent);

        // טיפול בהעלאת תמונות (Drag & Drop) - "images[]"
        if (isset($_FILES['images'])) {
            for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                $fileName = $_FILES['images']['name'][$i];
                $tmpName = $_FILES['images']['tmp_name'][$i];
                $targetDir = "uploads/";
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                $targetFile = $targetDir . time() . "_" . basename($fileName);
                if (move_uploaded_file($tmpName, $targetFile)) {
                    addImage($pdo, $newSongId, $targetFile);
                }
            }
        }
    }
}

// חיפוש שירים במצב משתמש
$search = $_GET['search'] ?? '';

$sqlSearch = "%" . $search . "%";
$sql = "SELECT * FROM songs WHERE title_he LIKE :srch OR title_en LIKE :srch ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(":srch", $sqlSearch);
$stmt->execute();
$songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// שליפת התמונות
function getImagesForSong($pdo, $songId) {
    $sql = "SELECT * FROM images WHERE song_id = :songid";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":songid", $songId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8" />
    <title>Song Management</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f7f7f7;
            font-family: sans-serif;
            direction: rtl;
        }
        nav {
            background-color: #111;
            color: #fff;
            display: flex;
            align-items: center;
            padding: 10px;
        }
        nav img {
            height: 40px;
            margin-right: 10px;
        }
        nav h1 {
            margin: 0;
            font-size: 1.2rem;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .admin-form, .search-form {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        .admin-form h2, .search-form h2 {
            margin-top: 0;
        }
        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        .drag-drop {
            margin-top: 10px;
            padding: 20px;
            border: 2px dashed #ccc;
            text-align: center;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .drag-drop.dragover {
            background-color: #ffe0b3;
        }
        button {
            background-color: #ff6600;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background-color: #e05500;
        }
        .song {
            background: #fff;
            margin-bottom: 10px;
            padding: 20px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        .song h3 {
            margin-top: 0;
            color: #ff6600;
        }
        .images {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .images img {
            width: 100px;
            height: auto;
            cursor: pointer;
            border: 2px solid transparent;
            border-radius: 4px;
        }
        .images img:hover {
            border-color: #ff6600;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.8);
        }
        .modal img {
            display: block;
            margin: 50px auto;
            max-width: 80%;
        }
        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
        }
        .copy-btn {
            margin-right: 5px;
            background-color: #333;
        }
    </style>
</head>
<body>
    <nav>
        <img src="logo.png" alt="Logo">
        <h1>Song Management</h1>
    </nav>
    <div class="container">
        <?php if ($adminMode): ?>
            <div class="admin-form">
                <h2>הוסף שיר חדש</h2>
                <form method="post" enctype="multipart/form-data">
                    <label>שם השיר בעברית:</label>
                    <input type="text" name="title_he" required>

                    <label>שם השיר באנגלית:</label>
                    <input type="text" name="title_en" required>

                    <label>קישור יוטיוב:</label>
                    <input type="text" name="youtube_link">

                    <label>מילים של השיר:</label>
                    <textarea name="lyrics" rows="4"></textarea>

                    <label>תוכן XML של השיר:</label>
                    <textarea name="xml_content" rows="4"></textarea>

                    <label>תמונות (Drag & Drop):</label>
                    <div class="drag-drop" id="dragDropZone">גרור ושחרר כאן תמונות או לחץ לבחירה</div>
                    <input type="file" name="images[]" id="fileInput" multiple accept="image/*" style="display:none;">

                    <button type="submit">שמירה</button>
                </form>
            </div>
        <?php else: ?>
            <div class="search-form">
                <h2>חיפוש שירים</h2>
                <form method="get">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <input
                        type="text"
                        name="search"
                        placeholder="חפש שיר בעברית או באנגלית"
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                    <button type="submit">חיפוש</button>
                </form>
            </div>
        <?php endif; ?>

        <?php foreach($songs as $song): ?>
            <div class="song">
                <h3><?php echo htmlspecialchars($song['title_he'] . " / " . $song['title_en']); ?></h3>
                <?php if (!empty($song['youtube_link'])): ?>
                    <p>
                        <button class="copy-btn" onclick="copyToClipboard('<?php echo htmlspecialchars($song['youtube_link']); ?>')">העתק לינק</button>
                        <strong>קישור יוטיוב:</strong> <?php echo htmlspecialchars($song['youtube_link']); ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($song['lyrics'])): ?>
                    <p>
                        <button class="copy-btn" onclick="copyToClipboard(`<?php echo str_replace("\n", "\\n", addslashes($song['lyrics'])); ?>`)">העתק מילים</button>
                        <strong>מילים:</strong> <br>
                        <pre><?php echo htmlspecialchars($song['lyrics']); ?></pre>
                    </p>
                <?php endif; ?>
                <?php if (!empty($song['xml_content'])): ?>
                    <p>
                        <button class="copy-btn" onclick="copyToClipboard(`<?php echo str_replace("\n", "\\n", addslashes($song['xml_content'])); ?>`)">העתק XML</button>
                        <strong>XML:</strong> <br>
                        <pre><?php echo htmlspecialchars($song['xml_content']); ?></pre>
                    </p>
                <?php endif; ?>

                <?php
                $images = getImagesForSong($pdo, $song['id']);
                if ($images) {
                    echo '<div class="images">';
                    foreach ($images as $img) {
                        echo '<img src="'. htmlspecialchars($img['image_path']) .'" onclick="showModal(this.src)" />';
                    }
                    echo '</div>';
                }
                ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Modal for Image Enlarge -->
    <div class="modal" id="imageModal">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <img id="modalImg" src="" alt="" />
    </div>

    <script>
        // Drag & Drop image upload logic
        var dragDropZone = document.getElementById('dragDropZone');
        var fileInput = document.getElementById('fileInput');
        if(dragDropZone) {
            dragDropZone.addEventListener('click', function(){
                fileInput.click();
            });

            dragDropZone.addEventListener('dragover', function(e){
                e.preventDefault();
                dragDropZone.classList.add('dragover');
            });

            dragDropZone.addEventListener('dragleave', function(e){
                dragDropZone.classList.remove('dragover');
            });

            dragDropZone.addEventListener('drop', function(e){
                e.preventDefault();
                dragDropZone.classList.remove('dragover');
                var files = e.dataTransfer.files;
                fileInput.files = files;
            });
        }

        // Copy to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text)
                .then(() => {
                    alert('הטקסט הועתק!');
                })
                .catch(err => {
                    console.error('Copy failed', err);
                });
        }

        // Modal - enlarge image
        function showModal(src) {
            var modal = document.getElementById('imageModal');
            var modalImg = document.getElementById('modalImg');
            modalImg.src = src;
            modal.style.display = 'block';
        }

        function closeModal() {
            var modal = document.getElementById('imageModal');
            modal.style.display = 'none';
        }
    </script>
</body>
</html>

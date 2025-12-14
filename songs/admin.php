<?php
require_once 'config.php';
require_once 'functions.php';

$message = '';
$messageType = '';

if (isset($_POST['title_he']) && isset($_POST['title_en'])) {
    try {
        $titleHe = $_POST['title_he'] ?? '';
        $titleEn = $_POST['title_en'] ?? '';
        $youtubeLink = $_POST['youtube_link'] ?? '';
        $lyrics = $_POST['lyrics'] ?? '';
        $xmlContent = $_POST['xml_content'] ?? '';

        $newSongId = addSong($pdo, $titleHe, $titleEn, $youtubeLink, $lyrics, $xmlContent);

        if (isset($_FILES['images'])) {
            $uploadedFiles = 0;
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
                    $uploadedFiles++;
                }
            }
        }
        $message = 'השיר נוסף בהצלחה!';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'אירעה שגיאה בהוספת השיר';
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ניהול שירים</title>
    <style>
        /* Variables */
        :root {
            --primary: #FF6B2B;
            --primary-light: #FF8F5E;
            --primary-dark: #E85A1F;
            --secondary: #2D3142;
            --secondary-light: #4A4F63;
            --background: #F5F6FA;
            --surface: #FFFFFF;
            --text-primary: #2D3142;
            --text-secondary: #6B7280;
            --border: #E2E8F0;
            --success: #10B981;
            --error: #EF4444;

            --shadow-sm: 0 2px 4px rgba(45, 49, 66, 0.05);
            --shadow-md: 0 4px 6px rgba(45, 49, 66, 0.1);
            --shadow-lg: 0 8px 16px rgba(45, 49, 66, 0.12);

            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
        }

		/* Font */
		@font-face {
			font-family: 'Almoni';
			src: url('font_files/almoni-tzar-aaa-300.ttf') format('truetype');
		}

		body {
			margin: 0;
			padding: 0;
			background: var(--background);
			font-family: 'Almoni', Arial, sans-serif;
			direction: rtl;
			line-height: 1.7;
			color: var(--text-primary);
		}

        nav {
            background: var(--secondary);
            color: var(--surface);
            padding: 1rem 2rem;
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        nav img {
            height: 40px;
            border-radius: var(--radius-sm);
        }

        nav h1 {
            margin: 0;
            font-size: 1.5rem;
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .admin-form {
            background: var(--surface);
            padding: 2rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }

        .admin-form h2 {
            color: var(--primary);
            margin: 0 0 2rem 0;
            font-size: 1.75rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .form-row-double {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .form-row, .form-row-double {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        input[type="text"],
        textarea {
            width: 90%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--background);
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 107, 43, 0.1);
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .drag-drop {
            border: 2px dashed var(--border);
            padding: 2rem;
            text-align: center;
            border-radius: var(--radius-md);
            background: var(--background);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .drag-drop:hover {
            border-color: var(--primary);
            background: var(--surface);
        }

        .drag-drop.dragover {
            border-color: var(--primary);
            background: rgba(255, 107, 43, 0.05);
        }

        .drag-drop i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        button {
            background: var(--primary);
            color: var(--surface);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-sm);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
        }

        button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: #ECFDF5;
            color: var(--success);
            border: 1px solid #A7F3D0;
        }

        .alert-error {
            background: #FEF2F2;
            color: var(--error);
            border: 1px solid #FECACA;
        }

        .preview-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .preview-image {
            position: relative;
            aspect-ratio: 1;
            border-radius: var(--radius-sm);
            overflow: hidden;
        }

        .preview-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-image .remove {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .container {
                margin: 1rem auto;
            }

            .admin-form {
                padding: 1.5rem;
            }

            .drag-drop {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <nav>
        <img src="vl-logo.png" alt="Logo">
        <h1>ניהול שירים</h1>
    </nav>

    <div class="container">
        <div class="admin-form">
            <h2>הוסף שיר חדש</h2>

            <?php if ($message): ?>
                <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" id="songForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>שם השיר בעברית:</label>
                        <input type="text" name="title_he" required>
                    </div>

                    <div class="form-group">
                        <label>שם השיר באנגלית:</label>
                        <input type="text" name="title_en" required>
                    </div>

                    <div class="form-group">
                        <label>קישור יוטיוב:</label>
                        <input type="text" name="youtube_link" placeholder="הכנס קישור לסרטון">
                    </div>
                </div>

                <div class="form-row-double">
                    <div class="form-group">
                        <label>מילים:</label>
                        <textarea name="lyrics" placeholder="הכנס את מילות השיר"></textarea>
                    </div>

                    <div class="form-group">
                        <label>תוכן XML:</label>
                        <textarea name="xml_content" placeholder="הכנס את תוכן ה-XML"></textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label>תמונות:</label>
                    <div class="drag-drop" id="dragDropZone">
                        <i>📸</i>
                        <p>גרור ושחרר תמונות לכאן או לחץ לבחירה מהמחשב</p>
                    </div>
                    <input type="file" name="images[]" id="fileInput" multiple accept="image/*" style="display:none;">
                    <div class="preview-images" id="imagePreview"></div>
                </div>

                <button type="submit">הוסף שיר</button>
            </form>
        </div>
    </div>

    <script>
        const dragDropZone = document.getElementById('dragDropZone');
        const fileInput = document.getElementById('fileInput');
        const imagePreview = document.getElementById('imagePreview');
        let files = [];

        dragDropZone.addEventListener('click', () => fileInput.click());

        dragDropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dragDropZone.classList.add('dragover');
        });

        dragDropZone.addEventListener('dragleave', () => {
            dragDropZone.classList.remove('dragover');
        });

        dragDropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dragDropZone.classList.remove('dragover');
            const newFiles = Array.from(e.dataTransfer.files).filter(file => file.type.startsWith('image/'));
            addFiles(newFiles);
        });

        fileInput.addEventListener('change', (e) => {
            const newFiles = Array.from(e.target.files);
            addFiles(newFiles);
        });

        function addFiles(newFiles) {
            files = [...files, ...newFiles];
            updateFileInput();
            updatePreview();
        }

        function removeFile(index) {
            files.splice(index, 1);
            updateFileInput();
            updatePreview();
        }

        function updateFileInput() {
            const dataTransfer = new DataTransfer();
            files.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;
        }

        function updatePreview() {
            imagePreview.innerHTML = '';
            files.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const div = document.createElement('div');
                    div.className = 'preview-image';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="תצוגה מקדימה">
                        <span class="remove" onclick="removeFile(${index})">&times;</span>
                    `;
                    imagePreview.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        }
    </script>
</body>
</html>
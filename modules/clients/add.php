<?php
// modules/clients/add.php

// Page configuration
$pageTitle = 'הוספת לקוח חדש';
$pageHeader = 'הוספת לקוח';
$pageHeaderButtons = '<a href="list.php" class="btn btn-secondary">חזרה לרשימה</a>';

require_once '../../includes/templates/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        if (empty($_POST['client_name'])) {
            throw new Exception('שם לקוח הוא שדה חובה');
        }

        $clientName = sanitizeInput($_POST['client_name']);
        $producerName = sanitizeInput($_POST['producer_name']);
        $logoPath = null;

        // Handle logo upload if exists
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $logoPath = uploadFile(
                $_FILES['logo'], 
                __DIR__ . '/../../public/uploads/logos/',
                ['jpg', 'jpeg', 'png']
            );
        }

        // Insert new client
        $stmt = $pdo->prepare("
            INSERT INTO clients (client_name, producer_name, logo_path)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([$clientName, $producerName, $logoPath]);
        
        $_SESSION['message'] = 'הלקוח נוסף בהצלחה';
        $_SESSION['message_type'] = 'success';
        redirectTo('list.php');

    } catch (Exception $e) {
        $_SESSION['message'] = $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="client_name" class="form-label">שם לקוח*</label>
                        <input type="text" 
                               class="form-control" 
                               id="client_name" 
                               name="client_name" 
                               value="<?= htmlspecialchars($_POST['client_name'] ?? '') ?>"
                               required>
                        <div class="invalid-feedback">יש להזין שם לקוח</div>
                    </div>

                    <div class="mb-3">
                        <label for="producer_name" class="form-label">שם מפיק</label>
                        <input type="text" 
                               class="form-control" 
                               id="producer_name" 
                               name="producer_name" 
                               value="<?= htmlspecialchars($_POST['producer_name'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="logo" class="form-label">לוגו</label>
                        <input type="file" 
                               class="form-control" 
                               id="logo" 
                               name="logo"
                               accept="image/jpeg,image/png">
                        <div class="form-text">
                            <ul>
                                <li>גודל קובץ מקסימלי: 10MB</li>
                                <li>פורמטים נתמכים: JPG, JPEG, PNG</li>
                            </ul>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="list.php" class="btn btn-secondary">חזרה לרשימה</a>
                        <button type="submit" class="btn btn-primary">הוסף לקוח</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Page specific JavaScript
$extraJs = <<<JS
document.getElementById('logo').addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            let previewImg = document.querySelector('.preview-logo');
            if (!previewImg) {
                previewImg = document.createElement('img');
                previewImg.className = 'preview-logo mb-2';
                this.parentElement.insertBefore(previewImg, this.nextElementSibling);
            }
            previewImg.src = e.target.result;
        }.bind(this);
        reader.readAsDataURL(this.files[0]);
    }
});
JS;

require_once '../../includes/templates/footer.php';
?>

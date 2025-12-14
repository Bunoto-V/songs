<?php
// modules/clients/edit.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Page configuration
$pageTitle = 'עריכת לקוח';
$pageHeader = 'עריכת לקוח';
$pageHeaderButtons = '<a href="list.php" class="btn btn-secondary">חזרה לרשימה</a>';

require_once '../../includes/templates/header.php';

// Get client ID from URL
$clientId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$clientId) {
    die('מזהה לקוח לא תקין');
}

// Get client data
try {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch();

    if (!$client) {
        die('לקוח לא נמצא');
    }
} catch (PDOException $e) {
    die('שגיאה בטעינת נתוני הלקוח');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        if (empty($_POST['producer_name_eng']) || empty($_POST['producer_name_heb'])) {
            throw new Exception('שמות המפיק בעברית ובאנגלית הם שדות חובה');
        }

        $producerNameHeb = sanitizeInput($_POST['producer_name_heb']);
        $producerNameEng = sanitizeInput($_POST['producer_name_eng']);
        $currentLogoPath = $client['logo_path'];
        $logoPath = $currentLogoPath; // שמירת הלוגו הקיים כברירת מחדל

        // Handle logo upload if exists
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            // Delete old logo if exists
            if ($currentLogoPath && file_exists($_SERVER['DOCUMENT_ROOT'] . $currentLogoPath)) {
                unlink($_SERVER['DOCUMENT_ROOT'] . $currentLogoPath);
            }

            $logoPath = uploadFile(
                $_FILES['logo'], 
                '../../public/uploads/logos/',
                ['jpg', 'jpeg', 'png'],
                10 * 1024 * 1024 // 10MB max size
            );
        }

        // Update client
        $stmt = $pdo->prepare("
            UPDATE clients 
            SET producer_name_eng = ?, 
                producer_name_heb = ?, 
                logo_path = ?, 
                updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$producerNameEng, $producerNameHeb, $logoPath, $clientId]);
        
        $_SESSION['message'] = 'הלקוח עודכן בהצלחה';
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
                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="producer_name_heb" class="form-label">שם מפיק בעברית*</label>
                        <input type="text" 
                               class="form-control" 
                               id="producer_name_heb" 
                               name="producer_name_heb" 
                               value="<?= htmlspecialchars($client['producer_name_heb']) ?>"
                               required>
                        <div class="invalid-feedback">
                            יש להזין שם מפיק בעברית
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="producer_name_eng" class="form-label">שם מפיק באנגלית*</label>
                        <input type="text" 
                               class="form-control" 
                               id="producer_name_eng" 
                               name="producer_name_eng" 
                               value="<?= htmlspecialchars($client['producer_name_eng']) ?>"
                               required>
                        <div class="invalid-feedback">
                            יש להזין שם מפיק באנגלית
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="logo" class="form-label">לוגו</label>
<?php if ($client['logo_path']): ?>
    <div class="mb-2">
        <img src="../../public<?= htmlspecialchars($client['logo_path']) ?>" 
             alt="לוגו נוכחי"
             class="preview-logo">
    </div>
<?php endif; ?>
                        <input type="file" 
                               class="form-control" 
                               id="logo" 
                               name="logo"
                               accept="image/jpeg,image/png">
                        <div class="form-text">
                            <ul>
                                <li>גודל קובץ מקסימלי: 10MB</li>
                                <li>פורמטים נתמכים: JPG, JPEG, PNG</li>
                                <li>השאר ריק כדי לשמור את הלוגו הקיים</li>
                            </ul>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="list.php" class="btn btn-secondary">ביטול</a>
                        <button type="submit" class="btn btn-primary">שמור שינויים</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Page specific JavaScript
$extraJs = <<<JS
// Form validation
(function () {
    'use strict'

    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.querySelectorAll('.needs-validation')

    // Loop over them and prevent submission
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }

                form.classList.add('was-validated')
            }, false)
        })
})()

// Preview logo on file change
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

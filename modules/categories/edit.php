<?php
// modules/categories/edit.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Page configuration
$pageTitle = 'עריכת קטגוריה';
$pageHeader = 'עריכת קטגוריה';
$pageHeaderButtons = '<a href="list.php" class="btn btn-secondary">חזרה לרשימה</a>';

require_once '../../includes/templates/header.php';

// Get category ID from URL
$categoryId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$categoryId) {
    die('מזהה קטגוריה לא תקין');
}

// Get category data
try {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch();

    if (!$category) {
        die('קטגוריה לא נמצאה');
    }
} catch (PDOException $e) {
    die('שגיאה בטעינת נתוני הקטגוריה');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        if (empty($_POST['category_name'])) {
            throw new Exception('שם קטגוריה הוא שדה חובה');
        }

        $categoryName = sanitizeInput($_POST['category_name']);

        // Check if category name already exists (excluding current category)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE category_name = ? AND id != ?");
        $stmt->execute([$categoryName, $categoryId]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('קטגוריה בשם זה כבר קיימת במערכת');
        }

        // Update category
        $stmt = $pdo->prepare("
            UPDATE categories 
            SET category_name = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$categoryName, $categoryId]);
        
        $_SESSION['message'] = 'הקטגוריה עודכנה בהצלחה';
        $_SESSION['message_type'] = 'success';
        redirectTo('list.php');

    } catch (Exception $e) {
        $_SESSION['message'] = $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="category_name" class="form-label">שם הקטגוריה*</label>
                        <input type="text" 
                               class="form-control" 
                               id="category_name" 
                               name="category_name" 
                               value="<?= htmlspecialchars($category['category_name']) ?>"
                               required>
                        <div class="invalid-feedback">
                            יש להזין שם קטגוריה
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
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
JS;

require_once '../../includes/templates/footer.php';
?>

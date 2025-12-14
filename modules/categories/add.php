<?php
// modules/categories/add.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Page configuration
$pageTitle = 'הוספת קטגוריה חדשה';
$pageHeader = 'הוספת קטגוריה';
$pageHeaderButtons = '<a href="list.php" class="btn btn-secondary">חזרה לרשימה</a>';

require_once '../../includes/templates/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        if (empty($_POST['category_name'])) {
            throw new Exception('שם קטגוריה הוא שדה חובה');
        }

        $categoryName = sanitizeInput($_POST['category_name']);

        // Check if category already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE category_name = ?");
        $stmt->execute([$categoryName]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('קטגוריה בשם זה כבר קיימת במערכת');
        }

        // Insert new category
        $stmt = $pdo->prepare("
            INSERT INTO categories (category_name)
            VALUES (?)
        ");

        $stmt->execute([$categoryName]);
        
        $_SESSION['message'] = 'הקטגוריה נוספה בהצלחה';
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
                               value="<?= htmlspecialchars($_POST['category_name'] ?? '') ?>"
                               required
                               autofocus>
                        <div class="invalid-feedback">
                            יש להזין שם קטגוריה
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="list.php" class="btn btn-secondary">ביטול</a>
                        <button type="submit" class="btn btn-primary">שמור קטגוריה</button>
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

<?php
/*********************************
 * קובץ config.php – הגדרות חיבור למסד
 *********************************/

$host = "localhost";        // שם השרת
$dbname = "songs_management";    // שם המסד המאוחד
$user = "adm_tkc";          // שם המשתמש
$pass = "a1367$1661va";     // סיסמה

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "שגיאה בחיבור למסד נתונים: " . $e->getMessage();
    exit;
}

?>
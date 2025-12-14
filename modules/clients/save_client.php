<?php
// modules/clients/save_client.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

try {
    if (empty($_POST['producers'])) {
        throw new Exception('לא התקבלו פרטי מפיקים');
    }

    $pdo->beginTransaction();

    foreach ($_POST['producers'] as $data) {
        if (empty($data['producer_name_heb']) || empty($data['producer_name_eng']) || empty($data['logo_path'])) {
            throw new Exception('כל השדות הם שדות חובה');
        }

        $producerNameHeb = sanitizeInput($data['producer_name_heb']);
        $producerNameEng = sanitizeInput($data['producer_name_eng']);
        $logoPath = $data['logo_path'];

        // Check if producer already exists - בדיקה מעודכנת
        $stmt = $pdo->prepare("
            SELECT id 
            FROM clients 
            WHERE producer_name_heb = ? 
            OR producer_name_eng = ?
        ");
        
        $stmt->execute([$producerNameHeb, $producerNameEng]);
        if ($stmt->fetch()) {
            throw new Exception('מפיק בשם זה כבר קיים במערכת');
        }

        // Validate that the logo file exists
        if (!file_exists('../../public/' . $logoPath)) {
            throw new Exception('קובץ הלוגו לא נמצא');
        }

        // Insert new producer with updated column names
        $stmt = $pdo->prepare("
            INSERT INTO clients (
                client_name, 
                producer_name_heb, 
                producer_name_eng, 
                logo_path
            ) VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $producerNameHeb,  // client_name is same as producer_name_heb
            $producerNameHeb,
            $producerNameEng,
            $logoPath
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'המפיקים נשמרו בהצלחה'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
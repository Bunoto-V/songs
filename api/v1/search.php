<?php
// api/search.php
require_once '../../config/database.php';
header('Content-Type: application/json');

// Get search parameters
$searchType = $_GET['type'] ?? 'songs';
$category = $_GET['category'] ?? '';
$search = trim($_GET['search'] ?? '');

try {
    if ($searchType === 'songs') {
        $params = [];
        $sql = "
            SELECT 
                s.*,
                c.category_name
            FROM songs s
            LEFT JOIN categories c ON s.category_id = c.id
            WHERE 1=1
        ";
        
        if ($category) {
            $sql .= " AND s.category_id = ?";
            $params[] = $category;
        }
        
        if ($search) {
            $sql .= " AND (s.title LIKE ? OR c.category_name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql .= " ORDER BY s.title ASC";
        
    } else {
        $params = [];
        $sql = "
            SELECT 
                id,
                client_name,
                producer_name_heb,
                producer_name_eng,
                logo_path
            FROM clients
            WHERE logo_path IS NOT NULL
        ";
        
        if ($search) {
            $sql .= " AND (producer_name_heb LIKE ? OR producer_name_eng LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql .= " ORDER BY producer_name_heb ASC";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $results
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
}
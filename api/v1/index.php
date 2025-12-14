<?php
// api/v1/index.php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Simple API key validation
function validateApiKey() {
    $headers = getallheaders();
    $apiKey = $headers['Authorization'] ?? '';
    
    if (empty($apiKey)) {
        throw new Exception('API key is required');
    }

    // In production, validate against database of API keys
    if ($apiKey !== 'your-api-key') {
        throw new Exception('Invalid API key');
    }
}

try {
    validateApiKey();

    $route = $_GET['route'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($route) {
        case 'songs':
            if ($method === 'GET') {
                // Get songs with optional filters
                $where = [];
                $params = [];

                if (!empty($_GET['category'])) {
                    $where[] = "s.category_id = ?";
                    $params[] = $_GET['category'];
                }

                if (!empty($_GET['client'])) {
                    $where[] = "s.client_id = ?";
                    $params[] = $_GET['client'];
                }

                $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

                $query = "
                    SELECT 
                        s.*,
                        c.category_name,
                        cl.client_name,
                        sm.duration,
                        sm.bpm,
                        sm.key_signature,
                        GROUP_CONCAT(t.tag_name) as tags
                    FROM songs s
                    LEFT JOIN categories c ON s.category_id = c.id
                    LEFT JOIN clients cl ON s.client_id = cl.id
                    LEFT JOIN song_metadata sm ON s.id = sm.song_id
                    LEFT JOIN song_tags st ON s.id = st.song_id
                    LEFT JOIN tags t ON st.tag_id = t.id
                    {$whereClause}
                    GROUP BY s.id
                    ORDER BY s.created_at DESC
                ";

                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $songs = $stmt->fetchAll();

                echo json_encode(['success' => true, 'data' => $songs]);
            }
            break;

        case 'categories':
            if ($method === 'GET') {
                $categories = $pdo->query("
                    SELECT 
                        c.*,
                        COUNT(s.id) as song_count
                    FROM categories c
                    LEFT JOIN songs s ON c.id = s.category_id
                    GROUP BY c.id
                    ORDER BY c.category_name
                ")->fetchAll();

                echo json_encode(['success' => true, 'data' => $categories]);
            }
            break;

        case 'clients':
            if ($method === 'GET') {
                $clients = $pdo->query("
                    SELECT 
                        c.*,
                        COUNT(s.id) as song_count
                    FROM clients c
                    LEFT JOIN songs s ON c.id = s.client_id
                    GROUP BY c.id
                    ORDER BY c.client_name
                ")->fetchAll();

                echo json_encode(['success' => true, 'data' => $clients]);
            }
            break;

        case 'stats':
            if ($method === 'GET') {
                $stats = [
                    'total_songs' => $pdo->query("SELECT COUNT(*) FROM songs")->fetchColumn(),
                    'total_downloads' => $pdo->query("SELECT SUM(download_count) FROM song_metadata")->fetchColumn(),
                    'total_categories' => $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
                    'total_clients' => $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn(),
                    'recent_downloads' => $pdo->query("
                        SELECT COUNT(*) 
                        FROM song_metadata 
                        WHERE last_downloaded >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    ")->fetchColumn()
                ];

                echo json_encode(['success' => true, 'data' => $stats]);
            }
            break;

        default:
            throw new Exception('Invalid route');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
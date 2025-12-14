<?php
// modules/clients/delete.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';
requireLogin(); // Ensure the user is authenticated

header('Content-Type: application/json');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Decode and validate the request data
    $data = json_decode(file_get_contents('php://input'), true);
    $clientId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$clientId) {
        throw new Exception('Invalid client ID');
    }

    // Fetch client details for potential clean-up
    $stmt = $pdo->prepare("SELECT logo_path FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch();

    if (!$client) {
        throw new Exception('Client not found');
    }

    // Remove logo file if it exists
    if ($client['logo_path']) {
        $logoFullPath = '../../public/' . $client['logo_path']; // Ensure correct path
        if (file_exists($logoFullPath)) {
            unlink($logoFullPath); // Delete the file from the server
        }
    }

    // Delete client from the database
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Failed to delete client');
    }

    echo json_encode(['success' => true, 'message' => 'Client deleted successfully']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

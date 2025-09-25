<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['action']) || $input['action'] !== 'generate') {
        throw new Exception('Invalid action');
    }

    $current_user = getCurrentUser();

    // Generate new QR code
    $result = generateQRCode($current_user['id']);

    if ($result['success']) {
        // Log activity
        logActivity($current_user['id'], 'qr_generate', 'QR Code generated successfully');

        echo json_encode([
            'success' => true,
            'message' => 'QR Code berhasil digenerate',
            'qr_path' => $result['qr_path'],
            'token' => substr($result['token'], 0, 8) . '...'
        ]);
    } else {
        throw new Exception($result['message']);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

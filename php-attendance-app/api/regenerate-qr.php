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

    if (!isset($input['action']) || $input['action'] !== 'regenerate') {
        throw new Exception('Invalid action');
    }

    $current_user = getCurrentUser();
    $conn = getDbConnection();

    // Deactivate existing QR codes
    $stmt = $conn->prepare("UPDATE qr_codes SET is_active = FALSE WHERE user_id = ?");
    $stmt->execute([$current_user['id']]);

    // Delete old QR code files
    $stmt = $conn->prepare("SELECT qr_image_path FROM qr_codes WHERE user_id = ? AND qr_image_path IS NOT NULL");
    $stmt->execute([$current_user['id']]);
    $old_qr_codes = $stmt->fetchAll();

    foreach ($old_qr_codes as $qr) {
        $file_path = '../' . $qr['qr_image_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // Generate new QR code
    $result = generateQRCode($current_user['id']);

    if ($result['success']) {
        // Log activity
        logActivity($current_user['id'], 'qr_regenerate', 'QR Code regenerated successfully');

        echo json_encode([
            'success' => true,
            'message' => 'QR Code berhasil digenerate ulang',
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

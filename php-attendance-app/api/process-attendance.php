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

    if (!isset($input['qr_data']) || empty($input['qr_data'])) {
        throw new Exception('QR data is required');
    }

    $qr_data = $input['qr_data'];
    $current_user = getCurrentUser();

    // Verify QR Code
    $qr_verification = verifyQRCode($qr_data);

    if (!$qr_verification['success']) {
        throw new Exception($qr_verification['message']);
    }

    // Check if it's the user's own QR code or admin
    if ($qr_verification['user_id'] != $current_user['id'] && !hasRole('admin')) {
        throw new Exception('QR Code tidak valid untuk user ini');
    }

    // Get student information
    $student = getStudentByUserId($qr_verification['user_id']);

    if (!$student) {
        throw new Exception('Data siswa tidak ditemukan');
    }

    // Check if already attended today
    $today_attendance = getTodayAttendance($student['id']);

    if ($today_attendance && $today_attendance['check_in_time']) {
        // If already checked in, this could be checkout
        if (!$today_attendance['check_out_time']) {
            // Process checkout
            $result = processCheckout($student['id'], $today_attendance['id']);
        } else {
            throw new Exception('Anda sudah melakukan absensi lengkap hari ini');
        }
    } else {
        // Process checkin
        $result = processCheckin($student['id']);
    }

    if ($result['success']) {
        // Log activity
        logActivity($qr_verification['user_id'], 'attendance_scan',
                   'QR attendance scan - Status: ' . $result['status']);

        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'status' => $result['status'],
            'time' => date('H:i:s'),
            'data' => $result['data'] ?? null
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

/**
 * Process check-in attendance
 */
function processCheckin($student_id) {
    try {
        $conn = getDbConnection();

        // Get attendance settings
        $settings = getSettings();
        $attendance_start = $settings['attendance_start_time'] ?? '07:00';
        $attendance_end = $settings['attendance_end_time'] ?? '07:30';
        $late_tolerance = intval($settings['late_tolerance'] ?? 10);

        $current_time = date('H:i:s');
        $current_datetime = date('Y-m-d H:i:s');

        // Determine status
        $status = 'present';
        if ($current_time > $attendance_end) {
            $late_deadline = date('H:i:s', strtotime($attendance_end) + ($late_tolerance * 60));
            if ($current_time <= $late_deadline) {
                $status = 'late';
            } else {
                $status = 'absent'; // Very late
            }
        }

        // Get student's class
        $stmt = $conn->prepare("SELECT class_id FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();

        if (!$student) {
            return ['success' => false, 'message' => 'Data siswa tidak ditemukan'];
        }

        // Insert attendance record
        $stmt = $conn->prepare("
            INSERT INTO attendance
            (student_id, class_id, attendance_date, check_in_time, status, qr_scanned, ip_address, user_agent)
            VALUES (?, ?, CURDATE(), ?, ?, TRUE, ?, ?)
            ON DUPLICATE KEY UPDATE
            check_in_time = VALUES(check_in_time),
            status = VALUES(status),
            qr_scanned = TRUE,
            ip_address = VALUES(ip_address),
            updated_at = NOW()
        ");

        $stmt->execute([
            $student_id,
            $student['class_id'],
            $current_datetime,
            $status,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);

        $status_messages = [
            'present' => 'Absensi berhasil! Anda hadir tepat waktu.',
            'late' => 'Absensi berhasil! Anda terlambat, harap datang lebih awal.',
            'absent' => 'Absensi tercatat! Anda sangat terlambat.'
        ];

        return [
            'success' => true,
            'message' => $status_messages[$status],
            'status' => $status,
            'data' => [
                'check_in_time' => $current_datetime,
                'attendance_status' => $status
            ]
        ];

    } catch (Exception $e) {
        error_log("Check-in error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Gagal memproses check-in'];
    }
}

/**
 * Process check-out attendance
 */
function processCheckout($student_id, $attendance_id) {
    try {
        $conn = getDbConnection();

        $current_datetime = date('Y-m-d H:i:s');

        // Update attendance record with checkout time
        $stmt = $conn->prepare("
            UPDATE attendance
            SET check_out_time = ?,
                location_checkout = ?,
                updated_at = NOW()
            WHERE id = ? AND student_id = ?
        ");

        $stmt->execute([
            $current_datetime,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $attendance_id,
            $student_id
        ]);

        if ($stmt->rowCount() > 0) {
            return [
                'success' => true,
                'message' => 'Check-out berhasil! Terima kasih sudah hadir hari ini.',
                'status' => 'checked_out',
                'data' => [
                    'check_out_time' => $current_datetime
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Gagal memproses check-out'];
        }

    } catch (Exception $e) {
        error_log("Check-out error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Gagal memproses check-out'];
    }
}
?>

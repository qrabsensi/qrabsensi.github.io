<?php
/**
 * Functions Utility File
 * Fungsi-fungsi umum untuk aplikasi absensi QR Code
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Sanitize input data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate secure token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check user role
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }
}

/**
 * Redirect if not admin
 */
function requireAdmin() {
    requireLogin();
    if (!hasRole('admin')) {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit();
    }
}

/**
 * Redirect if not teacher
 */
function requireTeacher() {
    requireLogin();
    if (!hasRole('teacher') && !hasRole('admin')) {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit();
    }
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error getting current user: " . $e->getMessage());
        return null;
    }
}

/**
 * Get user by ID
 */
function getUserById($id) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error getting user by ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Get student info by user ID
 */
function getStudentByUserId($user_id) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("
            SELECT s.*, u.full_name, u.email, c.class_name, c.class_code
            FROM students s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN classes c ON s.class_id = c.id
            WHERE s.user_id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error getting student: " . $e->getMessage());
        return null;
    }
}

/**
 * Get teacher info by user ID
 */
function getTeacherByUserId($user_id) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("
            SELECT t.*, u.full_name, u.email
            FROM teachers t
            JOIN users u ON t.user_id = u.id
            WHERE t.user_id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error getting teacher: " . $e->getMessage());
        return null;
    }
}

/**
 * Generate QR Code for user
 */
function generateQRCode($user_id) {
    require_once __DIR__ . '/../vendor/phpqrcode/qrlib.php';

    try {
        $conn = getDbConnection();

        // Generate unique token
        $token = generateToken(16);

        // Create QR code directory if not exists
        if (!file_exists(QR_CODE_PATH)) {
            mkdir(QR_CODE_PATH, 0777, true);
        }

        // QR code file path
        $qr_filename = 'qr_' . $user_id . '_' . time() . '.png';
        $qr_filepath = QR_CODE_PATH . $qr_filename;

        // QR code data (JSON format)
        $qr_data = json_encode([
            'user_id' => $user_id,
            'token' => $token,
            'timestamp' => time(),
            'type' => 'attendance'
        ]);

        // Generate QR code
        QRcode::png($qr_data, $qr_filepath, QR_ECLEVEL_M, 8, 2);

        // Save to database
        $stmt = $conn->prepare("
            INSERT INTO qr_codes (user_id, qr_token, qr_image_path, expires_at)
            VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))
            ON DUPLICATE KEY UPDATE
            qr_token = VALUES(qr_token),
            qr_image_path = VALUES(qr_image_path),
            expires_at = VALUES(expires_at),
            is_active = TRUE
        ");
        $stmt->execute([$user_id, $token, 'uploads/qr_codes/' . $qr_filename]);

        // Update user QR code path
        $stmt = $conn->prepare("UPDATE users SET qr_code = ? WHERE id = ?");
        $stmt->execute(['uploads/qr_codes/' . $qr_filename, $user_id]);

        return [
            'success' => true,
            'qr_path' => 'uploads/qr_codes/' . $qr_filename,
            'token' => $token
        ];

    } catch (Exception $e) {
        error_log("Error generating QR code: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to generate QR code'];
    }
}

/**
 * Verify QR Code token
 */
function verifyQRCode($qr_data) {
    try {
        $data = json_decode($qr_data, true);

        if (!$data || !isset($data['user_id'], $data['token'])) {
            return ['success' => false, 'message' => 'Invalid QR code format'];
        }

        $conn = getDbConnection();
        $stmt = $conn->prepare("
            SELECT qc.*, u.full_name, u.role
            FROM qr_codes qc
            JOIN users u ON qc.user_id = u.id
            WHERE qc.user_id = ? AND qc.qr_token = ? AND qc.is_active = TRUE AND qc.expires_at > NOW()
        ");
        $stmt->execute([$data['user_id'], $data['token']]);
        $qr_record = $stmt->fetch();

        if (!$qr_record) {
            return ['success' => false, 'message' => 'QR code not found or expired'];
        }

        return [
            'success' => true,
            'user_id' => $qr_record['user_id'],
            'full_name' => $qr_record['full_name'],
            'role' => $qr_record['role']
        ];

    } catch (Exception $e) {
        error_log("Error verifying QR code: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error verifying QR code'];
    }
}

/**
 * Record attendance
 */
function recordAttendance($student_id, $status = 'present', $notes = '') {
    try {
        $conn = getDbConnection();

        // Get student class
        $stmt = $conn->prepare("SELECT class_id FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();

        if (!$student) {
            return ['success' => false, 'message' => 'Student not found'];
        }

        // Check if already recorded today
        $stmt = $conn->prepare("
            SELECT * FROM attendance
            WHERE student_id = ? AND attendance_date = CURDATE()
        ");
        $stmt->execute([$student_id]);
        $existing = $stmt->fetch();

        $current_time = date('Y-m-d H:i:s');
        $attendance_start = date('Y-m-d 07:00:00');
        $late_time = date('Y-m-d 07:30:00');

        // Determine status if not provided
        if ($status === 'present') {
            $status = (strtotime($current_time) > strtotime($late_time)) ? 'late' : 'present';
        }

        if ($existing) {
            // Update existing record
            $stmt = $conn->prepare("
                UPDATE attendance
                SET check_in_time = ?, status = ?, notes = ?, qr_scanned = TRUE,
                    ip_address = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $current_time,
                $status,
                $notes,
                $_SERVER['REMOTE_ADDR'],
                $existing['id']
            ]);
        } else {
            // Insert new record
            $stmt = $conn->prepare("
                INSERT INTO attendance
                (student_id, class_id, attendance_date, check_in_time, status, notes, qr_scanned, ip_address)
                VALUES (?, ?, CURDATE(), ?, ?, ?, TRUE, ?)
            ");
            $stmt->execute([
                $student_id,
                $student['class_id'],
                $current_time,
                $status,
                $notes,
                $_SERVER['REMOTE_ADDR']
            ]);
        }

        return ['success' => true, 'message' => 'Attendance recorded successfully', 'status' => $status];

    } catch (Exception $e) {
        error_log("Error recording attendance: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to record attendance'];
    }
}

/**
 * Get attendance history
 */
function getAttendanceHistory($student_id, $limit = 30) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("
            SELECT a.*, c.class_name
            FROM attendance a
            JOIN classes c ON a.class_id = c.id
            WHERE a.student_id = ?
            ORDER BY a.attendance_date DESC
            LIMIT ?
        ");
        $stmt->execute([$student_id, $limit]);
        return $stmt->fetchAll();

    } catch (Exception $e) {
        error_log("Error getting attendance history: " . $e->getMessage());
        return [];
    }
}

/**
 * Get today's attendance status
 */
function getTodayAttendance($student_id) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("
            SELECT * FROM attendance
            WHERE student_id = ? AND attendance_date = CURDATE()
        ");
        $stmt->execute([$student_id]);
        return $stmt->fetch();

    } catch (Exception $e) {
        error_log("Error getting today's attendance: " . $e->getMessage());
        return null;
    }
}

/**
 * Upload file
 */
function uploadFile($file, $destination, $allowed_types = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']) {
    try {
        if (!isset($file['name']) || empty($file['name'])) {
            return ['success' => false, 'message' => 'No file uploaded'];
        }

        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_types)) {
            return ['success' => false, 'message' => 'Invalid file type'];
        }

        if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            return ['success' => false, 'message' => 'File too large (max 5MB)'];
        }

        if (!file_exists(dirname($destination))) {
            mkdir(dirname($destination), 0777, true);
        }

        $filename = time() . '_' . uniqid() . '.' . $file_ext;
        $filepath = dirname($destination) . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to upload file'];
        }

    } catch (Exception $e) {
        error_log("Error uploading file: " . $e->getMessage());
        return ['success' => false, 'message' => 'Upload error'];
    }
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime)) return '-';
    return date($format, strtotime($datetime));
}

/**
 * Get attendance statistics
 */
function getAttendanceStats($student_id, $month = null, $year = null) {
    try {
        $conn = getDbConnection();

        $where_clause = "WHERE student_id = ?";
        $params = [$student_id];

        if ($month && $year) {
            $where_clause .= " AND MONTH(attendance_date) = ? AND YEAR(attendance_date) = ?";
            $params[] = $month;
            $params[] = $year;
        } elseif ($year) {
            $where_clause .= " AND YEAR(attendance_date) = ?";
            $params[] = $year;
        }

        $stmt = $conn->prepare("
            SELECT
                COUNT(*) as total_days,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN status = 'sick' THEN 1 ELSE 0 END) as sick_days,
                SUM(CASE WHEN status = 'permission' THEN 1 ELSE 0 END) as permission_days
            FROM attendance
            $where_clause
        ");
        $stmt->execute($params);
        return $stmt->fetch();

    } catch (Exception $e) {
        error_log("Error getting attendance stats: " . $e->getMessage());
        return [];
    }
}

/**
 * Log activity
 */
function logActivity($user_id, $action, $description = '') {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);

    } catch (Exception $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
}

/**
 * Get system settings
 */
function getSettings() {
    try {
        $conn = getDbConnection();
        $stmt = $conn->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];

        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $settings;

    } catch (Exception $e) {
        error_log("Error getting settings: " . $e->getMessage());
        return [];
    }
}

/**
 * Update setting
 */
function updateSetting($key, $value) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("
            UPDATE settings SET setting_value = ?, updated_at = NOW()
            WHERE setting_key = ?
        ");
        $stmt->execute([$value, $key]);

        return $stmt->rowCount() > 0;

    } catch (Exception $e) {
        error_log("Error updating setting: " . $e->getMessage());
        return false;
    }
}

/**
 * Send JSON response
 */
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Generate letter number
 */
function generateLetterNumber($type = 'SK') {
    try {
        $conn = getDbConnection();

        $table = ($type === 'SK') ? 'sick_letters' : 'permission_letters';

        $stmt = $conn->prepare("
            SELECT MAX(CAST(SUBSTRING(letter_number, 4) AS UNSIGNED)) as max_num
            FROM $table
            WHERE YEAR(created_at) = YEAR(NOW())
        ");
        $stmt->execute();
        $result = $stmt->fetch();

        $next_number = ($result['max_num'] ?? 0) + 1;

        return $type . '-' . str_pad($next_number, 4, '0', STR_PAD_LEFT);

    } catch (Exception $e) {
        error_log("Error generating letter number: " . $e->getMessage());
        return $type . '-0001';
    }
}

/**
 * Check if time is within attendance hours
 */
function isAttendanceTime() {
    $current_time = date('H:i');
    $start_time = '06:30';
    $end_time = '08:00';

    return ($current_time >= $start_time && $current_time <= $end_time);
}

/**
 * Get class students
 */
function getClassStudents($class_id) {
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("
            SELECT s.*, u.full_name, u.email
            FROM students s
            JOIN users u ON s.user_id = u.id
            WHERE s.class_id = ? AND u.is_active = TRUE
            ORDER BY u.full_name
        ");
        $stmt->execute([$class_id]);
        return $stmt->fetchAll();

    } catch (Exception $e) {
        error_log("Error getting class students: " . $e->getMessage());
        return [];
    }
}

/**
 * Validate Indonesian phone number
 */
function validatePhoneNumber($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);

    if (substr($phone, 0, 1) === '0') {
        $phone = '62' . substr($phone, 1);
    } elseif (substr($phone, 0, 2) !== '62') {
        $phone = '62' . $phone;
    }

    return (strlen($phone) >= 10 && strlen($phone) <= 15) ? $phone : false;
}

/**
 * Generate secure filename
 */
function generateSecureFilename($original_filename) {
    $ext = pathinfo($original_filename, PATHINFO_EXTENSION);
    return time() . '_' . uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
}
?>

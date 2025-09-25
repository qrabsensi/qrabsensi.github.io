-- Database Schema untuk Aplikasi Absensi QR Code
-- Created: 2024
-- Description: Schema lengkap untuk sistem absensi QR Code dan surat ijin sakit siswa

-- Hapus database jika sudah ada (untuk development)
DROP DATABASE IF EXISTS attendance_qr_system;

-- Buat database baru
CREATE DATABASE attendance_qr_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE attendance_qr_system;

-- Tabel Users (untuk semua role: admin, teacher, student)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'student',
    phone VARCHAR(20),
    address TEXT,
    profile_picture VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    qr_code VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel Classes (Kelas)
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(50) NOT NULL,
    class_code VARCHAR(10) UNIQUE NOT NULL,
    teacher_id INT,
    academic_year VARCHAR(20) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabel Students (Detail informasi siswa)
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    student_number VARCHAR(20) UNIQUE NOT NULL,
    class_id INT,
    parent_name VARCHAR(100),
    parent_phone VARCHAR(20),
    parent_email VARCHAR(100),
    birth_date DATE,
    gender ENUM('male', 'female') NOT NULL,
    blood_type VARCHAR(5),
    emergency_contact VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
);

-- Tabel Teachers (Detail informasi guru)
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    employee_number VARCHAR(20) UNIQUE NOT NULL,
    department VARCHAR(50),
    position VARCHAR(50),
    hire_date DATE,
    salary DECIMAL(12,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Attendance (Absensi)
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    check_in_time TIMESTAMP NULL,
    check_out_time TIMESTAMP NULL,
    status ENUM('present', 'late', 'absent', 'sick', 'permission') NOT NULL DEFAULT 'absent',
    location_checkin VARCHAR(255),
    location_checkout VARCHAR(255),
    notes TEXT,
    qr_scanned BOOLEAN DEFAULT FALSE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_daily_attendance (student_id, attendance_date)
);

-- Tabel Sick Letters (Surat Ijin Sakit)
CREATE TABLE sick_letters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    letter_number VARCHAR(50) UNIQUE NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT NOT NULL,
    doctor_name VARCHAR(100),
    hospital_name VARCHAR(100),
    attachment VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabel Permission Letters (Surat Ijin Lainnya)
CREATE TABLE permission_letters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    letter_number VARCHAR(50) UNIQUE NOT NULL,
    permission_date DATE NOT NULL,
    permission_type ENUM('family_event', 'medical_checkup', 'other') NOT NULL,
    reason TEXT NOT NULL,
    attachment VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabel QR Codes (untuk tracking QR code yang digunakan)
CREATE TABLE qr_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    qr_token VARCHAR(255) UNIQUE NOT NULL,
    qr_image_path VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Settings (Pengaturan sistem)
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel Activity Logs (Log aktivitas sistem)
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert data awal

-- Admin default
INSERT INTO users (username, email, password, full_name, role, is_active) VALUES
('admin', 'admin@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', TRUE);

-- Guru default
INSERT INTO users (username, email, password, full_name, role, phone, is_active) VALUES
('teacher1', 'teacher1@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Budi Santoso, S.Pd', 'teacher', '081234567890', TRUE),
('teacher2', 'teacher2@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Siti Nurhaliza, S.Pd', 'teacher', '081234567891', TRUE);

-- Siswa default
INSERT INTO users (username, email, password, full_name, role, phone, is_active) VALUES
('student1', 'student1@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ahmad Rizki', 'student', '081234567892', TRUE),
('student2', 'student2@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sari Indah', 'student', '081234567893', TRUE),
('student3', 'student3@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Doni Prasetyo', 'student', '081234567894', TRUE);

-- Kelas
INSERT INTO classes (class_name, class_code, teacher_id, academic_year, description) VALUES
('Kelas X IPA 1', 'X-IPA-1', 2, '2024/2025', 'Kelas X IPA 1 Tahun Ajaran 2024/2025'),
('Kelas X IPA 2', 'X-IPA-2', 3, '2024/2025', 'Kelas X IPA 2 Tahun Ajaran 2024/2025');

-- Detail guru
INSERT INTO teachers (user_id, employee_number, department, position, hire_date) VALUES
(2, 'TCH001', 'Matematika', 'Guru Matematika', '2020-07-01'),
(3, 'TCH002', 'Bahasa Indonesia', 'Guru Bahasa Indonesia', '2019-08-15');

-- Detail siswa
INSERT INTO students (user_id, student_number, class_id, parent_name, parent_phone, birth_date, gender) VALUES
(4, 'STD001', 1, 'Bapak Rizki', '081234567800', '2008-05-15', 'male'),
(5, 'STD002', 1, 'Ibu Indah', '081234567801', '2008-03-20', 'female'),
(6, 'STD003', 2, 'Bapak Prasetyo', '081234567802', '2008-07-10', 'male');

-- Pengaturan sistem
INSERT INTO settings (setting_key, setting_value, description) VALUES
('school_name', 'SMA Negeri 1 Jakarta', 'Nama sekolah'),
('school_address', 'Jl. Pendidikan No. 123, Jakarta', 'Alamat sekolah'),
('school_phone', '021-12345678', 'Nomor telepon sekolah'),
('attendance_start_time', '07:00', 'Jam mulai absensi'),
('attendance_end_time', '07:30', 'Jam berakhir absensi'),
('late_tolerance', '10', 'Toleransi keterlambatan (menit)'),
('qr_expiry_hours', '24', 'Masa berlaku QR code (jam)');

-- Views untuk kemudahan query

-- View untuk attendance dengan informasi lengkap
CREATE VIEW v_attendance_detail AS
SELECT
    a.id,
    a.attendance_date,
    a.check_in_time,
    a.check_out_time,
    a.status,
    a.notes,
    u.full_name as student_name,
    s.student_number,
    c.class_name,
    c.class_code
FROM attendance a
JOIN students st ON a.student_id = st.id
JOIN users u ON st.user_id = u.id
JOIN classes c ON a.class_id = c.id;

-- View untuk sick letters dengan informasi lengkap
CREATE VIEW v_sick_letters_detail AS
SELECT
    sl.id,
    sl.letter_number,
    sl.start_date,
    sl.end_date,
    sl.reason,
    sl.status,
    sl.created_at,
    u.full_name as student_name,
    s.student_number,
    c.class_name,
    approver.full_name as approved_by_name
FROM sick_letters sl
JOIN students st ON sl.student_id = st.id
JOIN users u ON st.user_id = u.id
JOIN classes c ON st.class_id = c.id
LEFT JOIN users approver ON sl.approved_by = approver.id;

-- Indexes untuk performa
CREATE INDEX idx_attendance_date ON attendance(attendance_date);
CREATE INDEX idx_attendance_student ON attendance(student_id);
CREATE INDEX idx_sick_letters_status ON sick_letters(status);
CREATE INDEX idx_permission_letters_status ON permission_letters(status);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_qr_codes_token ON qr_codes(qr_token);

-- Triggers untuk auto-generate nomor surat

DELIMITER //

-- Trigger untuk auto-generate nomor surat sakit
CREATE TRIGGER generate_sick_letter_number
    BEFORE INSERT ON sick_letters
    FOR EACH ROW
BEGIN
    DECLARE next_number INT;
    DECLARE letter_num VARCHAR(50);

    SELECT COALESCE(MAX(CAST(SUBSTRING(letter_number, 4) AS UNSIGNED)), 0) + 1
    INTO next_number
    FROM sick_letters
    WHERE YEAR(created_at) = YEAR(NOW());

    SET letter_num = CONCAT('SK-', LPAD(next_number, 4, '0'));
    SET NEW.letter_number = letter_num;
END//

-- Trigger untuk auto-generate nomor surat ijin
CREATE TRIGGER generate_permission_letter_number
    BEFORE INSERT ON permission_letters
    FOR EACH ROW
BEGIN
    DECLARE next_number INT;
    DECLARE letter_num VARCHAR(50);

    SELECT COALESCE(MAX(CAST(SUBSTRING(letter_number, 4) AS UNSIGNED)), 0) + 1
    INTO next_number
    FROM permission_letters
    WHERE YEAR(created_at) = YEAR(NOW());

    SET letter_num = CONCAT('IJ-', LPAD(next_number, 4, '0'));
    SET NEW.letter_number = letter_num;
END//

-- Trigger untuk log aktivitas
CREATE TRIGGER log_user_activity
    AFTER INSERT ON attendance
    FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (user_id, action, description)
    VALUES (
        (SELECT user_id FROM students WHERE id = NEW.student_id),
        'attendance_recorded',
        CONCAT('Absensi tercatat dengan status: ', NEW.status)
    );
END//

DELIMITER ;

-- Stored Procedures

DELIMITER //

-- Procedure untuk mendapatkan statistik dashboard
CREATE PROCEDURE GetDashboardStats(IN class_id_param INT)
BEGIN
    SELECT
        (SELECT COUNT(*) FROM students WHERE class_id = class_id_param OR class_id_param IS NULL) as total_students,
        (SELECT COUNT(*) FROM attendance WHERE attendance_date = CURDATE() AND status = 'present' AND (class_id = class_id_param OR class_id_param IS NULL)) as present_today,
        (SELECT COUNT(*) FROM attendance WHERE attendance_date = CURDATE() AND status = 'late' AND (class_id = class_id_param OR class_id_param IS NULL)) as late_today,
        (SELECT COUNT(*) FROM attendance WHERE attendance_date = CURDATE() AND status = 'absent' AND (class_id = class_id_param OR class_id_param IS NULL)) as absent_today,
        (SELECT COUNT(*) FROM sick_letters WHERE status = 'pending') as pending_sick_letters,
        (SELECT COUNT(*) FROM permission_letters WHERE status = 'pending') as pending_permissions;
END//

-- Procedure untuk generate QR code token
CREATE PROCEDURE GenerateQRToken(IN user_id_param INT)
BEGIN
    DECLARE token VARCHAR(255);
    SET token = SHA2(CONCAT(user_id_param, NOW(), RAND()), 256);

    UPDATE qr_codes SET is_active = FALSE WHERE user_id = user_id_param;

    INSERT INTO qr_codes (user_id, qr_token, expires_at)
    VALUES (user_id_param, token, DATE_ADD(NOW(), INTERVAL 24 HOUR));

    SELECT token;
END//

DELIMITER ;

-- Final message
SELECT 'Database schema created successfully!' as message;

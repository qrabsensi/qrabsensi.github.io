<?php
/**
 * Database Configuration
 * Konfigurasi database untuk aplikasi absensi QR Code
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'attendance_qr_system';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    public $conn;

    /**
     * Koneksi ke database
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);

        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            throw new Exception("Database connection failed");
        }

        return $this->conn;
    }

    /**
     * Test koneksi database
     */
    public static function testConnection() {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            return $conn !== null;
        } catch(Exception $e) {
            return false;
        }
    }

    /**
     * Konfigurasi untuk hosting (production)
     * Uncomment dan sesuaikan dengan hosting Anda
     */
    /*
    private $host = 'your_hosting_server';
    private $db_name = 'your_database_name';
    private $username = 'your_username';
    private $password = 'your_password';
    */
}

// Fungsi helper untuk mendapatkan koneksi database
function getDbConnection() {
    $database = new Database();
    return $database->getConnection();
}

// Konstanta untuk konfigurasi aplikasi
define('DB_HOST', 'localhost');
define('DB_NAME', 'attendance_qr_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Base URL aplikasi
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$base_url = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
define('BASE_URL', rtrim($base_url, '/'));

// Path upload
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');
define('QR_CODE_PATH', UPLOAD_PATH . 'qr_codes/');
define('SICK_LETTER_PATH', UPLOAD_PATH . 'sick_letters/');
?>

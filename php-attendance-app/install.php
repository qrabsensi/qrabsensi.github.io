<?php
/**
 * Installation Script for PHP Attendance System
 * This script will help setup the database and initial configuration
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if already installed
if (file_exists('config/installed.lock')) {
    die('System already installed. Delete config/installed.lock to reinstall.');
}

$step = $_GET['step'] ?? 1;
$errors = [];
$success = [];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Sistem Absensi QR Code</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .step-indicator {
            transition: all 0.3s ease;
        }
        .step-active {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }
        .step-completed {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-2xl mb-4">
                <i class="fas fa-qrcode text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Sistem Absensi QR Code</h1>
            <p class="text-gray-600">Setup dan Instalasi</p>
        </div>

        <!-- Step Indicator -->
        <div class="flex justify-center mb-8">
            <div class="flex items-center space-x-4">
                <?php
                $steps = [
                    1 => 'Persyaratan',
                    2 => 'Database',
                    3 => 'Admin',
                    4 => 'Selesai'
                ];

                foreach ($steps as $num => $title):
                    $class = 'step-indicator w-12 h-12 rounded-full flex items-center justify-center text-sm font-medium border-2';
                    if ($num < $step) {
                        $class .= ' step-completed border-green-500';
                    } elseif ($num == $step) {
                        $class .= ' step-active border-blue-500';
                    } else {
                        $class .= ' border-gray-300 bg-white text-gray-500';
                    }
                ?>
                <div class="text-center">
                    <div class="<?php echo $class; ?>">
                        <?php if ($num < $step): ?>
                            <i class="fas fa-check"></i>
                        <?php else: ?>
                            <?php echo $num; ?>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs mt-2 text-gray-600"><?php echo $title; ?></p>
                </div>
                <?php if ($num < count($steps)): ?>
                    <div class="w-8 h-px bg-gray-300"></div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <?php if ($step == 1): ?>
                <!-- Step 1: Requirements Check -->
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Pemeriksaan Persyaratan</h2>

                <div class="space-y-4">
                    <?php
                    $requirements = [
                        ['name' => 'PHP Version >= 8.0', 'check' => version_compare(PHP_VERSION, '8.0.0', '>='), 'current' => PHP_VERSION],
                        ['name' => 'PDO Extension', 'check' => extension_loaded('pdo'), 'current' => extension_loaded('pdo') ? 'Loaded' : 'Not loaded'],
                        ['name' => 'PDO MySQL', 'check' => extension_loaded('pdo_mysql'), 'current' => extension_loaded('pdo_mysql') ? 'Loaded' : 'Not loaded'],
                        ['name' => 'GD Extension', 'check' => extension_loaded('gd'), 'current' => extension_loaded('gd') ? 'Loaded' : 'Not loaded'],
                        ['name' => 'JSON Extension', 'check' => extension_loaded('json'), 'current' => extension_loaded('json') ? 'Loaded' : 'Not loaded'],
                        ['name' => 'Uploads Directory Writable', 'check' => is_writable('uploads/'), 'current' => is_writable('uploads/') ? 'Writable' : 'Not writable'],
                        ['name' => 'Config Directory Writable', 'check' => is_writable('config/'), 'current' => is_writable('config/') ? 'Writable' : 'Not writable']
                    ];

                    $all_passed = true;
                    foreach ($requirements as $req):
                        if (!$req['check']) $all_passed = false;
                    ?>
                    <div class="flex items-center justify-between p-4 border rounded-lg <?php echo $req['check'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'; ?>">
                        <div>
                            <h3 class="font-medium"><?php echo $req['name']; ?></h3>
                            <p class="text-sm text-gray-600"><?php echo $req['current']; ?></p>
                        </div>
                        <div>
                            <?php if ($req['check']): ?>
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            <?php else: ?>
                                <i class="fas fa-times-circle text-red-600 text-xl"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-8 text-center">
                    <?php if ($all_passed): ?>
                        <a href="?step=2" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center">
                            Lanjut ke Database <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    <?php else: ?>
                        <p class="text-red-600 mb-4">Harap perbaiki persyaratan yang belum terpenuhi sebelum melanjutkan.</p>
                        <a href="?step=1" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors inline-flex items-center">
                            <i class="fas fa-refresh mr-2"></i> Periksa Ulang
                        </a>
                    <?php endif; ?>
                </div>

            <?php elseif ($step == 2): ?>
                <!-- Step 2: Database Configuration -->
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Konfigurasi Database</h2>

                <?php
                if ($_POST) {
                    $host = trim($_POST['host']);
                    $database = trim($_POST['database']);
                    $username = trim($_POST['username']);
                    $password = trim($_POST['password']);

                    try {
                        // Test connection
                        $dsn = "mysql:host={$host};charset=utf8mb4";
                        $pdo = new PDO($dsn, $username, $password);
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                        // Create database if not exists
                        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        $pdo->exec("USE `{$database}`");

                        // Read and execute SQL schema
                        $sql = file_get_contents('database/schema.sql');
                        // Remove the initial database creation commands
                        $sql = preg_replace('/DROP DATABASE.*?;/s', '', $sql);
                        $sql = preg_replace('/CREATE DATABASE.*?;/s', '', $sql);
                        $sql = preg_replace('/USE .*;/', '', $sql);

                        // Execute SQL
                        $pdo->exec($sql);

                        // Create config file
                        $config_content = "<?php
class Database {
    private \$host = '{$host}';
    private \$db_name = '{$database}';
    private \$username = '{$username}';
    private \$password = '{$password}';
    private \$charset = 'utf8mb4';
    public \$conn;

    public function getConnection() {
        \$this->conn = null;
        try {
            \$dsn = \"mysql:host=\" . \$this->host . \";dbname=\" . \$this->db_name . \";charset=\" . \$this->charset;
            \$options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES utf8mb4\"
            ];
            \$this->conn = new PDO(\$dsn, \$this->username, \$this->password, \$options);
        } catch(PDOException \$exception) {
            error_log(\"Connection error: \" . \$exception->getMessage());
            throw new Exception(\"Database connection failed\");
        }
        return \$this->conn;
    }
}

function getDbConnection() {
    \$database = new Database();
    return \$database->getConnection();
}

define('DB_HOST', '{$host}');
define('DB_NAME', '{$database}');
define('DB_USER', '{$username}');
define('DB_PASS', '{$password}');

date_default_timezone_set('Asia/Jakarta');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

\$protocol = isset(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
\$base_url = \$protocol . \$_SERVER['HTTP_HOST'] . dirname(\$_SERVER['SCRIPT_NAME']);
define('BASE_URL', rtrim(\$base_url, '/'));

define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');
define('QR_CODE_PATH', UPLOAD_PATH . 'qr_codes/');
define('SICK_LETTER_PATH', UPLOAD_PATH . 'sick_letters/');
?>";

                        file_put_contents('config/database.php', $config_content);

                        $_SESSION['db_configured'] = true;
                        $success[] = "Database berhasil dikonfigurasi!";

                    } catch (Exception $e) {
                        $errors[] = "Error: " . $e->getMessage();
                    }
                }
                ?>

                <?php if (!empty($errors)): ?>
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <?php foreach ($errors as $error): ?>
                            <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <?php foreach ($success as $msg): ?>
                            <p class="text-green-700"><?php echo htmlspecialchars($msg); ?></p>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-8">
                        <a href="?step=3" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center">
                            Lanjut ke Admin Setup <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <form method="POST" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Host Database</label>
                            <input type="text" name="host" value="localhost" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Database</label>
                            <input type="text" name="database" value="attendance_qr_system" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Username Database</label>
                            <input type="text" name="username" value="root" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password Database</label>
                            <input type="password" name="password" value=""
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="text-center">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                                Test & Setup Database
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

            <?php elseif ($step == 3): ?>
                <!-- Step 3: Admin Account -->
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Setup Admin Account</h2>

                <?php
                if ($_POST) {
                    require_once 'config/database.php';

                    $admin_email = trim($_POST['admin_email']);
                    $admin_password = trim($_POST['admin_password']);
                    $admin_name = trim($_POST['admin_name']);
                    $school_name = trim($_POST['school_name']);

                    try {
                        $conn = getDbConnection();

                        // Update admin user
                        $hashed_password = password_hash($admin_password, PASSWORD_BCRYPT, ['cost' => 12]);
                        $stmt = $conn->prepare("UPDATE users SET email = ?, password = ?, full_name = ? WHERE role = 'admin' LIMIT 1");
                        $stmt->execute([$admin_email, $hashed_password, $admin_name]);

                        // Update school settings
                        $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'school_name'");
                        $stmt->execute([$school_name]);

                        $_SESSION['admin_configured'] = true;
                        $success[] = "Admin account berhasil dikonfigurasi!";

                    } catch (Exception $e) {
                        $errors[] = "Error: " . $e->getMessage();
                    }
                }
                ?>

                <?php if (!empty($errors)): ?>
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <?php foreach ($errors as $error): ?>
                            <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <?php foreach ($success as $msg): ?>
                            <p class="text-green-700"><?php echo htmlspecialchars($msg); ?></p>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-8">
                        <a href="?step=4" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center">
                            Selesai <i class="fas fa-check ml-2"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <form method="POST" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Sekolah/Institusi</label>
                            <input type="text" name="school_name" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="SMA Negeri 1 Jakarta">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Admin</label>
                            <input type="text" name="admin_name" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Administrator">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email Admin</label>
                            <input type="email" name="admin_email" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="admin@sekolah.com">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password Admin</label>
                            <input type="password" name="admin_password" required minlength="6"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Minimal 6 karakter">
                        </div>

                        <div class="text-center">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                                Setup Admin Account
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

            <?php elseif ($step == 4): ?>
                <!-- Step 4: Installation Complete -->
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-24 h-24 bg-green-100 rounded-full mb-6">
                        <i class="fas fa-check text-green-600 text-4xl"></i>
                    </div>

                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Instalasi Selesai!</h2>
                    <p class="text-gray-600 mb-8">Sistem Absensi QR Code telah berhasil diinstall dan siap digunakan.</p>

                    <div class="bg-gray-50 rounded-lg p-6 mb-8">
                        <h3 class="text-lg font-semibold mb-4">Informasi Login:</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div class="bg-white p-4 rounded-lg border">
                                <h4 class="font-medium text-blue-600 mb-2">Administrator</h4>
                                <p>Email: <?php echo htmlspecialchars($_SESSION['admin_email'] ?? 'admin@school.com'); ?></p>
                                <p>Password: [Yang Anda set]</p>
                            </div>
                            <div class="bg-white p-4 rounded-lg border">
                                <h4 class="font-medium text-green-600 mb-2">Guru (Demo)</h4>
                                <p>Email: teacher1@school.com</p>
                                <p>Password: password</p>
                            </div>
                            <div class="bg-white p-4 rounded-lg border">
                                <h4 class="font-medium text-purple-600 mb-2">Siswa (Demo)</h4>
                                <p>Email: student1@school.com</p>
                                <p>Password: password</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <a href="login.php" class="bg-blue-600 text-white px-8 py-4 rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center text-lg">
                            <i class="fas fa-sign-in-alt mr-3"></i>
                            Login ke Sistem
                        </a>

                        <div class="text-sm text-gray-500">
                            <p>⚠️ Jangan lupa untuk:</p>
                            <ul class="list-disc list-inside mt-2 space-y-1">
                                <li>Menghapus file install.php untuk keamanan</li>
                                <li>Backup database secara berkala</li>
                                <li>Update password default demo accounts</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <?php
                // Create installation lock file
                file_put_contents('config/installed.lock', date('Y-m-d H:i:s'));
                ?>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-gray-500 text-sm">
            <p>&copy; <?php echo date('Y'); ?> Sistem Absensi QR Code. Dibuat dengan ❤️</p>
        </div>
    </div>
</body>
</html>

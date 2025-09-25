<?php
session_start();

$page_title = 'Dashboard Siswa';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a student
requireLogin();
if (!hasRole('student') && !hasRole('admin')) {
    header('Location: ../login.php');
    exit();
}

$current_user = getCurrentUser();
$student = getStudentByUserId($current_user['id']);

if (!$student && !hasRole('admin')) {
    $_SESSION['error_message'] = 'Data siswa tidak ditemukan. Silakan hubungi administrator.';
    header('Location: ../login.php');
    exit();
}

// Get today's attendance
$today_attendance = null;
$attendance_stats = [];

if ($student) {
    $today_attendance = getTodayAttendance($student['id']);
    $attendance_stats = getAttendanceStats($student['id'], date('n'), date('Y'));
}

// Get recent activities
try {
    $conn = getDbConnection();

    // Get recent attendance (last 7 days)
    $stmt = $conn->prepare("
        SELECT a.*, c.class_name
        FROM attendance a
        LEFT JOIN classes c ON a.class_id = c.id
        WHERE a.student_id = ?
        ORDER BY a.attendance_date DESC
        LIMIT 7
    ");
    $stmt->execute([$student['id'] ?? 0]);
    $recent_attendance = $stmt->fetchAll();

    // Get pending sick letters
    $stmt = $conn->prepare("
        SELECT * FROM sick_letters
        WHERE student_id = ? AND status = 'pending'
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$student['id'] ?? 0]);
    $pending_letters = $stmt->fetchAll();

    // Get attendance streak
    $stmt = $conn->prepare("
        SELECT COUNT(*) as streak FROM (
            SELECT attendance_date FROM attendance
            WHERE student_id = ? AND status IN ('present', 'late')
            AND attendance_date <= CURDATE()
            ORDER BY attendance_date DESC
        ) a
    ");
    $stmt->execute([$student['id'] ?? 0]);
    $attendance_streak = $stmt->fetch()['streak'] ?? 0;

    // Get this week stats
    $stmt = $conn->prepare("
        SELECT
            COUNT(*) as total_days,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days
        FROM attendance
        WHERE student_id = ?
        AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
        AND attendance_date <= CURDATE()
    ");
    $stmt->execute([$student['id'] ?? 0]);
    $week_stats = $stmt->fetch();

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $recent_attendance = [];
    $pending_letters = [];
    $attendance_streak = 0;
    $week_stats = ['total_days' => 0, 'present_days' => 0, 'late_days' => 0];
}

require_once '../includes/header.php';
?>

<!-- Hero Section -->
<div class="relative overflow-hidden bg-gradient-to-br from-blue-500 via-purple-600 to-indigo-700">
    <!-- Background Pattern -->
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.05"%3E%3Ccircle cx="7" cy="7" r="7"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-20"></div>

    <!-- Floating Elements -->
    <div class="absolute top-10 left-10 w-20 h-20 bg-white/10 rounded-full animate-float"></div>
    <div class="absolute top-32 right-20 w-16 h-16 bg-yellow-400/20 rounded-full animate-float" style="animation-delay: 2s;"></div>
    <div class="absolute bottom-20 left-32 w-12 h-12 bg-pink-400/20 rounded-full animate-float" style="animation-delay: 4s;"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center" data-aos="fade-up">
            <!-- Welcome Message -->
            <div class="mb-8">
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-display font-bold text-white mb-4">
                    Selamat Datang,
                    <span class="block bg-gradient-to-r from-yellow-400 to-pink-400 bg-clip-text text-transparent animate-gradient">
                        <?php echo htmlspecialchars(explode(' ', $current_user['full_name'])[0]); ?>! 👋
                    </span>
                </h1>
                <p class="text-xl md:text-2xl text-blue-100 font-medium">
                    Siap untuk memulai hari yang produktif?
                </p>

                <?php if ($student): ?>
                <div class="mt-6 flex flex-wrap justify-center items-center gap-4 text-blue-100">
                    <div class="flex items-center bg-white/10 backdrop-blur-sm rounded-full px-4 py-2">
                        <i class="fas fa-id-card mr-2"></i>
                        <span class="font-medium"><?php echo htmlspecialchars($student['student_number']); ?></span>
                    </div>
                    <div class="flex items-center bg-white/10 backdrop-blur-sm rounded-full px-4 py-2">
                        <i class="fas fa-school mr-2"></i>
                        <span class="font-medium"><?php echo htmlspecialchars($student['class_name'] ?? 'Belum ada kelas'); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8" data-aos="fade-up" data-aos-delay="200">
                <div class="bg-white/15 backdrop-blur-sm rounded-2xl p-4 text-center">
                    <div class="text-2xl md:text-3xl font-bold text-white mb-1">
                        <?php echo $attendance_streak; ?>
                    </div>
                    <div class="text-sm text-blue-200">Hari Berturut</div>
                </div>

                <div class="bg-white/15 backdrop-blur-sm rounded-2xl p-4 text-center">
                    <div class="text-2xl md:text-3xl font-bold text-white mb-1">
                        <?php echo $week_stats['present_days'] ?? 0; ?>
                    </div>
                    <div class="text-sm text-blue-200">Hadir Minggu Ini</div>
                </div>

                <div class="bg-white/15 backdrop-blur-sm rounded-2xl p-4 text-center">
                    <div class="text-2xl md:text-3xl font-bold text-white mb-1">
                        <?php echo count($pending_letters); ?>
                    </div>
                    <div class="text-sm text-blue-200">Surat Pending</div>
                </div>

                <div class="bg-white/15 backdrop-blur-sm rounded-2xl p-4 text-center">
                    <div class="text-2xl md:text-3xl font-bold text-white mb-1">
                        <?php echo $attendance_stats['total_days'] ?? 0; ?>
                    </div>
                    <div class="text-sm text-blue-200">Total Hari</div>
                </div>
            </div>

            <!-- Current Time -->
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 inline-flex items-center space-x-6" data-aos="fade-up" data-aos-delay="400">
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-mono font-bold text-white live-clock"></div>
                    <div class="text-blue-200 text-sm mt-1"><?php echo date('l, d F Y'); ?></div>
                </div>
                <div class="h-12 w-px bg-white/30"></div>
                <div class="text-center">
                    <div class="text-lg font-semibold text-white">
                        <?php
                        $hour = date('H');
                        if ($hour < 12) echo "Selamat Pagi";
                        elseif ($hour < 15) echo "Selamat Siang";
                        elseif ($hour < 18) echo "Selamat Sore";
                        else echo "Selamat Malam";
                        ?>
                    </div>
                    <div class="text-blue-200 text-sm">
                        <?php echo isAttendanceTime() ? "Jam Absensi Aktif" : "Di Luar Jam Absensi"; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Dashboard Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-8 relative z-10">

    <!-- Quick Actions -->
    <div class="mb-8" data-aos="fade-up">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-display font-bold text-gray-900 mb-2">Aksi Cepat</h2>
            <p class="text-gray-600">Pilih aksi yang ingin Anda lakukan</p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- QR Code Scan -->
            <a href="attendance.php" class="group relative bg-gradient-to-br from-emerald-400 to-cyan-500 p-6 rounded-3xl text-white hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 card-hover">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500 to-cyan-600 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-white/20 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-qrcode text-3xl group-hover:animate-pulse"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-1">Scan QR</h3>
                    <p class="text-sm opacity-90">Absensi Masuk/Keluar</p>
                </div>
                <!-- Floating icon -->
                <div class="absolute -top-2 -right-2 w-8 h-8 bg-yellow-400 rounded-full flex items-center justify-center text-yellow-900">
                    <i class="fas fa-star text-sm animate-spin"></i>
                </div>
            </a>

            <!-- QR Code Personal -->
            <a href="qr-code.php" class="group relative bg-gradient-to-br from-blue-400 to-indigo-600 p-6 rounded-3xl text-white hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 card-hover">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-indigo-700 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-white/20 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-id-card text-3xl group-hover:animate-bounce"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-1">QR Saya</h3>
                    <p class="text-sm opacity-90">Lihat & Download</p>
                </div>
            </a>

            <!-- Surat Ijin -->
            <a href="sick-letter.php" class="group relative bg-gradient-to-br from-rose-400 to-pink-600 p-6 rounded-3xl text-white hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 card-hover">
                <div class="absolute inset-0 bg-gradient-to-br from-rose-500 to-pink-700 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-white/20 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-file-medical text-3xl group-hover:animate-pulse"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-1">Surat Sakit</h3>
                    <p class="text-sm opacity-90">Buat Permohonan</p>
                </div>
                <?php if (count($pending_letters) > 0): ?>
                <div class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                    <?php echo count($pending_letters); ?>
                </div>
                <?php endif; ?>
            </a>

            <!-- Riwayat -->
            <a href="history.php" class="group relative bg-gradient-to-br from-purple-400 to-violet-600 p-6 rounded-3xl text-white hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 card-hover">
                <div class="absolute inset-0 bg-gradient-to-br from-purple-500 to-violet-700 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-white/20 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-history text-3xl group-hover:animate-spin"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-1">Riwayat</h3>
                    <p class="text-sm opacity-90">Lihat Semua</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Today's Attendance Status -->
    <?php if ($today_attendance): ?>
    <div class="mb-8" data-aos="fade-up" data-aos-delay="200">
        <div class="bg-white rounded-3xl shadow-soft border border-gray-100 overflow-hidden">
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-8 py-6 border-b border-green-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-500 rounded-2xl flex items-center justify-center mr-4">
                            <i class="fas fa-check-circle text-white text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Absensi Hari Ini</h2>
                            <p class="text-green-600 font-medium">Status tercatat dengan baik</p>
                        </div>
                    </div>
                    <?php
                    $status_configs = [
                        'present' => ['bg' => 'bg-green-500', 'text' => 'Hadir', 'icon' => 'fas fa-check'],
                        'late' => ['bg' => 'bg-yellow-500', 'text' => 'Terlambat', 'icon' => 'fas fa-clock'],
                        'absent' => ['bg' => 'bg-red-500', 'text' => 'Tidak Hadir', 'icon' => 'fas fa-times'],
                        'sick' => ['bg' => 'bg-blue-500', 'text' => 'Sakit', 'icon' => 'fas fa-thermometer'],
                        'permission' => ['bg' => 'bg-purple-500', 'text' => 'Izin', 'icon' => 'fas fa-calendar-alt']
                    ];
                    $status_config = $status_configs[$today_attendance['status']] ?? $status_configs['absent'];
                    ?>
                    <div class="flex items-center <?php echo $status_config['bg']; ?> text-white px-6 py-3 rounded-2xl shadow-lg">
                        <i class="<?php echo $status_config['icon']; ?> mr-2"></i>
                        <span class="font-bold"><?php echo $status_config['text']; ?></span>
                    </div>
                </div>
            </div>

            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Check In Time -->
                    <div class="text-center p-6 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl border border-blue-100">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-sign-in-alt text-white text-xl"></i>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-1">Waktu Masuk</h3>
                        <p class="text-2xl font-mono font-bold text-blue-600">
                            <?php echo $today_attendance['check_in_time'] ? formatDateTime($today_attendance['check_in_time'], 'H:i:s') : '-'; ?>
                        </p>
                        <p class="text-sm text-gray-500 mt-1"><?php echo formatDate($today_attendance['attendance_date']); ?></p>
                    </div>

                    <!-- Check Out Time -->
                    <div class="text-center p-6 bg-gradient-to-br from-rose-50 to-pink-50 rounded-2xl border border-rose-100">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-r from-rose-500 to-pink-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-sign-out-alt text-white text-xl"></i>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-1">Waktu Keluar</h3>
                        <p class="text-2xl font-mono font-bold text-rose-600">
                            <?php echo $today_attendance['check_out_time'] ? formatDateTime($today_attendance['check_out_time'], 'H:i:s') : 'Belum'; ?>
                        </p>
                        <p class="text-sm text-gray-500 mt-1">
                            <?php echo $today_attendance['check_out_time'] ? 'Selesai' : 'Menunggu checkout'; ?>
                        </p>
                    </div>

                    <!-- Duration -->
                    <div class="text-center p-6 bg-gradient-to-br from-purple-50 to-violet-50 rounded-2xl border border-purple-100">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-r from-purple-500 to-violet-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-stopwatch text-white text-xl"></i>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-1">Durasi</h3>
                        <?php
                        $duration = '-';
                        if ($today_attendance['check_in_time'] && $today_attendance['check_out_time']) {
                            $start = new DateTime($today_attendance['check_in_time']);
                            $end = new DateTime($today_attendance['check_out_time']);
                            $diff = $start->diff($end);
                            $duration = $diff->format('%h:%I');
                        } elseif ($today_attendance['check_in_time']) {
                            $start = new DateTime($today_attendance['check_in_time']);
                            $now = new DateTime();
                            $diff = $start->diff($now);
                            $duration = $diff->format('%h:%I') . ' (aktif)';
                        }
                        ?>
                        <p class="text-2xl font-mono font-bold text-purple-600"><?php echo $duration; ?></p>
                        <p class="text-sm text-gray-500 mt-1">Jam kerja</p>
                    </div>
                </div>

                <?php if ($today_attendance['notes']): ?>
                <div class="mt-6 p-4 bg-gradient-to-r from-amber-50 to-orange-50 rounded-2xl border border-amber-100">
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-amber-500 rounded-xl flex items-center justify-center mr-3">
                            <i class="fas fa-sticky-note text-white"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-amber-800 mb-1">Catatan Hari Ini</h4>
                            <p class="text-amber-700"><?php echo htmlspecialchars($today_attendance['notes']); ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- No Attendance Today -->
    <div class="mb-8" data-aos="fade-up" data-aos-delay="200">
        <div class="bg-gradient-to-br from-orange-100 to-red-100 rounded-3xl p-8 text-center border border-orange-200">
            <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-r from-orange-500 to-red-500 rounded-3xl flex items-center justify-center shadow-xl">
                <i class="fas fa-calendar-times text-white text-3xl animate-pulse"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">Belum Absen Hari Ini</h3>
            <p class="text-gray-600 mb-6 max-w-md mx-auto">Jangan lupa untuk melakukan absensi. Absensi tepat waktu menunjukkan kedisiplinan yang baik.</p>
            <a href="attendance.php" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-orange-500 to-red-500 text-white font-bold rounded-2xl hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                <i class="fas fa-qrcode mr-3 text-xl"></i>
                <span>Absen Sekarang</span>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Statistics and Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Monthly Statistics -->
        <?php if (!empty($attendance_stats)): ?>
        <div class="lg:col-span-2" data-aos="fade-up" data-aos-delay="300">
            <div class="bg-white rounded-3xl shadow-soft border border-gray-100 p-8">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center mr-4">
                            <i class="fas fa-chart-pie text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Statistik Bulan Ini</h3>
                            <p class="text-gray-600"><?php echo date('F Y'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                    <div class="text-center p-6 bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl border border-green-100 hover:shadow-lg transition-all duration-300">
                        <div class="text-3xl font-bold text-green-600 mb-2 animate-bounce-in"><?php echo $attendance_stats['present_days'] ?? 0; ?></div>
                        <div class="text-sm font-medium text-green-700 mb-1">Hadir</div>
                        <div class="text-xs text-green-600">Tepat waktu</div>
                    </div>

                    <div class="text-center p-6 bg-gradient-to-br from-yellow-50 to-orange-50 rounded-2xl border border-yellow-100 hover:shadow-lg transition-all duration-300">
                        <div class="text-3xl font-bold text-yellow-600 mb-2 animate-bounce-in"><?php echo $attendance_stats['late_days'] ?? 0; ?></div>
                        <div class="text-sm font-medium text-yellow-700 mb-1">Terlambat</div>
                        <div class="text-xs text-yellow-600">Perlu perbaikan</div>
                    </div>

                    <div class="text-center p-6 bg-gradient-to-br from-red-50 to-rose-50 rounded-2xl border border-red-100 hover:shadow-lg transition-all duration-300">
                        <div class="text-3xl font-bold text-red-600 mb-2 animate-bounce-in"><?php echo $attendance_stats['absent_days'] ?? 0; ?></div>
                        <div class="text-sm font-medium text-red-700 mb-1">Tidak Hadir</div>
                        <div class="text-xs text-red-600">Alpha</div>
                    </div>

                    <div class="text-center p-6 bg-gradient-to-br from-blue-50 to-cyan-50 rounded-2xl border border-blue-100 hover:shadow-lg transition-all duration-300">
                        <div class="text-3xl font-bold text-blue-600 mb-2 animate-bounce-in"><?php echo $attendance_stats['sick_days'] ?? 0; ?></div>
                        <div class="text-sm font-medium text-blue-700 mb-1">Sakit</div>
                        <div class="text-xs text-blue-600">Dengan surat</div>
                    </div>

                    <div class="text-center p-6 bg-gradient-to-br from-purple-50 to-violet-50 rounded-2xl border border-purple-100 hover:shadow-lg transition-all duration-300">
                        <div class="text-3xl font-bold text-purple-600 mb-2 animate-bounce-in"><?php echo $attendance_stats['permission_days'] ?? 0; ?></div>
                        <div class="text-sm font-medium text-purple-700 mb-1">Izin</div>
                        <div class="text-xs text-purple-600">Resmi</div>
                    </div>

                    <div class="text-center p-6 bg-gradient-to-br from-gray-50 to-slate-50 rounded-2xl border border-gray-100 hover:shadow-lg transition-all duration-300">
                        <div class="text-3xl font-bold text-gray-600 mb-2 animate-bounce-in"><?php echo $attendance_stats['total_days'] ?? 0; ?></div>
                        <div class="text-sm font-medium text-gray-700 mb-1">Total</div>
                        <div class="text-xs text-gray-600">Hari aktif</div>
                    </div>
                </div>

                <!-- Attendance Rate -->
                <?php
                $total_days = $attendance_stats['total_days'] ?? 0;
                $present_days = ($attendance_stats['present_days'] ?? 0) + ($attendance_stats['late_days'] ?? 0);
                $attendance_rate = $total_days > 0 ? round(($present_days / $total_days) * 100) : 0;
                ?>
                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-2xl p-6 border border-indigo-100">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-bold text-gray-900">Tingkat Kehadiran</h4>
                        <span class="text-2xl font-bold text-indigo-600"><?php echo $attendance_rate; ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-4">
                        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 h-4 rounded-full transition-all duration-1000 ease-out" style="width: <?php echo $attendance_rate; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Sidebar Content -->
        <div class="space-y-6">

            <!-- Recent Activity -->
            <div class="bg-white rounded-3xl shadow-soft border border-gray-100 p-6" data-aos="fade-left" data-aos-delay="100">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl flex items-center justify-center mr-3">
                            <i class="fas fa-clock text-white"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900">Aktivitas Terkini</h3>
                            <p class="text-sm text-gray-600">7 hari terakhir</p>
                        </div>
                    </div>
                </div>

                <?php if (!empty($recent_attendance)): ?>
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    <?php foreach ($recent_attendance as $index => $attendance):
                        $status_configs = [
                            'present' => ['color' => 'green', 'icon' => 'fas fa-check-circle'],
                            'late' => ['color' => 'yellow', 'icon' => 'fas fa-clock'],
                            'absent' => ['color' => 'red', 'icon' => 'fas fa-times-circle'],
                            'sick' => ['color' => 'blue', 'icon' => 'fas fa-thermometer'],
                            'permission' => ['color' => 'purple', 'icon' => 'fas fa-calendar-alt']
                        ];
                        $config = $status_configs[$attendance['status']] ?? $status_configs['absent'];
                    ?>
                    <div class="flex items-center p-4 bg-gradient-to-r from-gray-50 to-slate-50 rounded-2xl hover:shadow-md transition-all duration-300 border border-gray-100" data-aos="fade-up" data-aos-delay="<?php echo 200 + ($index * 100); ?>">
                        <div class="w-12 h-12 bg-<?php echo $config['color']; ?>-100 rounded-xl flex items-center justify-center mr-4">
                            <i class="<?php echo $config['icon']; ?> text-<?php echo $config['color']; ?>-600"></i>
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-gray-900">
                                <?php echo formatDate($attendance['attendance_date']); ?>
                            </div>
                            <div class="text-sm text-gray-600">
                                <?php echo $attendance['class_name'] ?? 'Kelas'; ?> •
                                <?php echo $attendance['check_in_time'] ? formatDateTime($attendance['check_in_time'], 'H:i') : '-'; ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-<?php echo $config['color']; ?>-100 text-<?php echo $config['color']; ?>-800">
                                <?php
                                $status_texts = [
                                    'present' => 'Hadir',
                                    'late' => 'Terlambat',
                                    'absent' => 'Tidak Hadir',
                                    'sick' => 'Sakit',
                                    'permission' => 'Izin'
                                ];
                                echo $status_texts[$attendance['status']] ?? 'Unknown';
                                ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4 text-center">
                    <a href="history.php" class="inline-flex items-center text-indigo-600 hover:text-indigo-700 font-medium text-sm transition-colors">
                        <span>Lihat Semua Riwayat</span>
                        <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
                <?php else: ?>
                <div class="text-center py-8">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-calendar-times text-gray-400 text-2xl"></i>
                    </div>
                    <p class="text-gray-500 font-medium">Belum ada aktivitas</p>
                    <p class="text-sm text-gray-400 mt-1">Mulai absensi untuk melihat riwayat</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Pending Letters -->
            <?php if (!empty($pending_letters)): ?>
            <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-3xl p-6 border border-amber-200" data-aos="fade-left" data-aos-delay="200">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-amber-500 rounded-xl flex items-center justify-center mr-3">
                            <i class="fas fa-file-medical text-white"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-amber-800">Surat Pending</h3>
                            <p class="text-sm text-amber-600"><?php echo count($pending_letters); ?> menunggu persetujuan</p>
                        </div>
                    </div>
                    <div class="w-6 h-6 bg-amber-500 rounded-full flex items-center justify-center">
                        <span class="text-white font-bold text-xs"><?php echo count($pending_letters); ?></span>
                    </div>
                </div>

                <div class="space-y-3">
                    <?php foreach ($pending_letters as $letter): ?>
                    <div class="bg-white/70 backdrop-blur-sm rounded-xl p-4 border border-amber-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-medium text-amber-900"><?php echo htmlspecialchars($letter['letter_number']); ?></div>
                                <div class="text-sm text-amber-700 mt-1">
                                    <?php echo formatDate($letter['start_date']); ?> - <?php echo formatDate($letter['end_date']); ?>
                                </div>
                            </div>
                            <div class="flex items-center px-3 py-1 bg-amber-100 rounded-full">
                                <div class="w-2 h-2 bg-amber-500 rounded-full mr-2 animate-pulse"></div>
                                <span class="text-xs font-medium text-amber-800">Pending</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-4">
                    <a href="sick-letter.php" class="inline-flex items-center text-amber-700 hover:text-amber-800 font-medium text-sm transition-colors">
                        <span>Buat Surat Baru</span>
                        <i class="fas fa-plus ml-2"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Tips -->
            <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-3xl p-6 text-white relative overflow-hidden" data-aos="fade-left" data-aos-delay="300">
                <!-- Background Pattern -->
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white rounded-full -translate-y-16 translate-x-16"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white rounded-full translate-y-12 -translate-x-12"></div>
                </div>

                <div class="relative z-10">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center mr-3">
                            <i class="fas fa-lightbulb text-white"></i>
                        </div>
                        <h3 class="font-bold text-white">Tips Hari Ini</h3>
                    </div>

                    <div class="space-y-4 text-sm">
                        <div class="flex items-start">
                            <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center mr-3 mt-0.5">
                                <i class="fas fa-clock text-white text-xs"></i>
                            </div>
                            <p class="text-indigo-100 leading-relaxed">
                                <span class="font-medium text-white">Jam Absensi:</span>
                                07:00 - 07:30 WIB setiap hari
                            </p>
                        </div>

                        <div class="flex items-start">
                            <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center mr-3 mt-0.5">
                                <i class="fas fa-mobile-alt text-white text-xs"></i>
                            </div>
                            <p class="text-indigo-100 leading-relaxed">
                                <span class="font-medium text-white">QR Scanner:</span>
                                Pastikan kamera berfungsi dengan baik
                            </p>
                        </div>

                        <div class="flex items-start">
                            <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center mr-3 mt-0.5">
                                <i class="fas fa-file-medical text-white text-xs"></i>
                            </div>
                            <p class="text-indigo-100 leading-relaxed">
                                <span class="font-medium text-white">Surat Sakit:</span>
                                Ajukan H-1 untuk persetujuan cepat
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-white/20">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-indigo-200">Kehadiran bulan ini</span>
                            <span class="font-bold text-white"><?php echo $attendance_rate; ?>%</span>
                        </div>
                        <div class="mt-2 w-full bg-white/20 rounded-full h-2">
                            <div class="bg-white h-2 rounded-full transition-all duration-1000" style="width: <?php echo $attendance_rate; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Enhanced dashboard interactions
    document.addEventListener('DOMContentLoaded', function() {
        // Animate statistics counters
        const animateCounters = () => {
            const counters = document.querySelectorAll('.animate-bounce-in');
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const counter = entry.target;
                        const target = parseInt(counter.textContent);
                        let current = 0;
                        const increment = target / 30;
                        const timer = setInterval(() => {
                            current += increment;
                            if (current >= target) {
                                counter.textContent = target;
                                clearInterval(timer);
                            } else {
                                counter.textContent = Math.floor(current);
                            }
                        }, 50);
                        observer.unobserve(counter);
                    }
                });
            }, observerOptions);

            counters.forEach(counter => observer.observe(counter));
        };

        // Initialize counter animation
        setTimeout(animateCounters, 500);

        // Auto-refresh attendance status
        let refreshInterval;
        const startAutoRefresh = () => {
            refreshInterval = setInterval(() => {
                if (!document.hidden) {
                    fetch('../api/get-attendance-status.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.hasUpdate) {
                                showToast('info', 'Status absensi telah diperbarui');
                                setTimeout(() => location.reload(), 2000);
                            }
                        })
                        .catch(error => console.log('Auto-refresh error:', error));
                }
            }, 60000); // Check every minute
        };

        startAutoRefresh();

        // Stop auto-refresh when page is hidden
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                clearInterval(refreshInterval);
            } else {
                startAutoRefresh();
            }
        });

        // Enhanced quick action animations
        document.querySelectorAll('.card-hover').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });

            card.addEventListener('click', function(e) {
                // Add click ripple effect
                const ripple = document.createElement('div');
                ripple.className = 'absolute inset-0 bg-white/20 rounded-3xl animate-ping';
                ripple.style.animationDuration = '0.6s';
                this.appendChild(ripple);

                setTimeout(() => {
                    if (ripple.parentNode) {
                        ripple.parentNode.removeChild(ripple);
                    }
                }, 600);

                // Analytics tracking
                const action = this.getAttribute('href');
                console.log('Quick action clicked:', action);

                // Show loading state
                showLoading('Memuat halaman...');
            });
        });

        // Progressive loading for better UX
        const lazyLoadElements = document.querySelectorAll('[data-aos]');
        const lazyObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in');
                }
            });
        });

        lazyLoadElements.forEach(el => lazyObserver.observe(el));

        // Floating action button for quick attendance
        if (!<?php echo $today_attendance ? 'true' : 'false'; ?>) {
            const floatingBtn = document.createElement('div');
            floatingBtn.className = 'fixed bottom-8 right-8 z-50';
            floatingBtn.innerHTML = `
                <a href="attendance.php" class="group flex items-center justify-center w-16 h-16 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-full shadow-2xl hover:shadow-3xl transition-all duration-300 transform hover:scale-110 animate-pulse">
                    <i class="fas fa-qrcode text-xl group-hover:animate-spin"></i>
                </a>
            `;
            document.body.appendChild(floatingBtn);

            // Add tooltip
            floatingBtn.addEventListener('mouseenter', function() {
                const tooltip = document.createElement('div');
                tooltip.className = 'absolute right-20 top-1/2 transform -translate-y-1/2 bg-gray-900 text-white text-sm px-3 py-2 rounded-lg whitespace-nowrap';
                tooltip.textContent = 'Absen Sekarang!';
                this.appendChild(tooltip);
            });

            floatingBtn.addEventListener('mouseleave', function() {
                const tooltip = this.querySelector('.absolute');
                if (tooltip) tooltip.remove();
            });
        }

        // Welcome message with personalized greeting
        const showWelcomeMessage = () => {
            const hour = new Date().getHours();
            let greeting = 'Selamat datang';
            let emoji = '👋';

            if (hour < 10) {
                greeting = 'Selamat pagi';
                emoji = '🌅';
            } else if (hour < 15) {
                greeting = 'Selamat siang';
                emoji = '☀️';
            } else if (hour < 18) {
                greeting = 'Selamat sore';
                emoji = '🌆';
            } else {
                greeting = 'Selamat malam';
                emoji = '🌙';
            }

            // Only show if first visit today
            const lastVisit = localStorage.getItem('lastDashboardVisit');
            const today = new Date().toDateString();

            if (lastVisit !== today) {
                setTimeout(() => {
                    showToast('success',
                        `${greeting}, <?php echo explode(' ', $current_user['full_name'])[0]; ?>! ${emoji}`,
                        'Sistem Absensi'
                    );
                    localStorage.setItem('lastDashboardVisit', today);
                }, 1500);
            }
        };

        showWelcomeMessage();
    });
</script>

<?php require_once '../includes/footer.php'; ?>

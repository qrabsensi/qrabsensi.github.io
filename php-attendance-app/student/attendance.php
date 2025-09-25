<?php
session_start();

$page_title = 'Absensi';
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
    $_SESSION['error_message'] = 'Data siswa tidak ditemukan.';
    header('Location: dashboard.php');
    exit();
}

// Check today's attendance status
$today_attendance = null;
if ($student) {
    $today_attendance = getTodayAttendance($student['id']);
}

// Get attendance time settings
$settings = getSettings();
$attendance_start_time = $settings['attendance_start_time'] ?? '07:00';
$attendance_end_time = $settings['attendance_end_time'] ?? '07:30';
$late_tolerance = $settings['late_tolerance'] ?? 10;

$current_time = date('H:i');
$is_attendance_time = ($current_time >= $attendance_start_time && $current_time <= $attendance_end_time);

require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <!-- Page Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between" data-aos="fade-down">
                <div class="flex items-center">
                    <div class="bg-gradient-to-br from-green-500 to-blue-600 p-3 rounded-xl mr-4">
                        <i class="fas fa-qrcode text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Absensi Siswa</h1>
                        <p class="text-gray-600">Scan QR Code untuk absensi</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="bg-gray-100 px-4 py-2 rounded-lg mb-2">
                        <div class="text-sm text-gray-600">Waktu Sekarang</div>
                        <div class="text-lg font-mono font-bold text-gray-900 live-clock"></div>
                    </div>
                    <div class="text-xs text-gray-500">
                        Jam Absensi: <?php echo $attendance_start_time; ?> - <?php echo $attendance_end_time; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Attendance Status -->
        <?php if ($today_attendance): ?>
        <div class="mb-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6" data-aos="fade-up">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                    Absensi Hari Ini
                </h2>
                <?php
                $status_colors = [
                    'present' => 'bg-green-100 text-green-800',
                    'late' => 'bg-yellow-100 text-yellow-800',
                    'absent' => 'bg-red-100 text-red-800',
                    'sick' => 'bg-blue-100 text-blue-800',
                    'permission' => 'bg-purple-100 text-purple-800'
                ];
                $status_texts = [
                    'present' => 'Hadir',
                    'late' => 'Terlambat',
                    'absent' => 'Tidak Hadir',
                    'sick' => 'Sakit',
                    'permission' => 'Izin'
                ];
                ?>
                <span class="px-4 py-2 text-sm font-medium rounded-full <?php echo $status_colors[$today_attendance['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                    <?php echo $status_texts[$today_attendance['status']] ?? 'Unknown'; ?>
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-calendar-day text-blue-500 mr-3 text-xl"></i>
                        <div>
                            <p class="text-sm text-gray-600">Tanggal</p>
                            <p class="font-semibold text-gray-900"><?php echo formatDate($today_attendance['attendance_date']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-clock text-green-500 mr-3 text-xl"></i>
                        <div>
                            <p class="text-sm text-gray-600">Waktu Masuk</p>
                            <p class="font-semibold text-gray-900">
                                <?php echo $today_attendance['check_in_time'] ? formatDateTime($today_attendance['check_in_time'], 'H:i:s') : '-'; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-sign-out-alt text-red-500 mr-3 text-xl"></i>
                        <div>
                            <p class="text-sm text-gray-600">Waktu Keluar</p>
                            <p class="font-semibold text-gray-900">
                                <?php echo $today_attendance['check_out_time'] ? formatDateTime($today_attendance['check_out_time'], 'H:i:s') : '-'; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($today_attendance['notes']): ?>
            <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-sticky-note mr-2"></i>
                    <strong>Catatan:</strong> <?php echo htmlspecialchars($today_attendance['notes']); ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- QR Scanner -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6" data-aos="fade-up">
                <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-camera text-blue-500 mr-2"></i>
                    Scanner QR Code
                </h2>

                <!-- Scanner Container -->
                <div class="relative">
                    <div id="qr-scanner-container" class="bg-gray-900 rounded-lg overflow-hidden relative">
                        <div id="scanner-loading" class="flex items-center justify-center h-64 text-white">
                            <div class="text-center">
                                <div class="loading-spinner mx-auto mb-4"></div>
                                <p>Memuat kamera...</p>
                            </div>
                        </div>
                        <div id="qr-scanner" class="hidden"></div>
                    </div>

                    <!-- Scanner Overlay -->
                    <div class="absolute inset-0 pointer-events-none">
                        <div class="scanner-overlay">
                            <div class="scanner-box">
                                <div class="corner top-left"></div>
                                <div class="corner top-right"></div>
                                <div class="corner bottom-left"></div>
                                <div class="corner bottom-right"></div>
                                <div class="scanning-line"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scanner Controls -->
                <div class="mt-4 space-y-3">
                    <div class="flex space-x-3">
                        <button
                            id="start-scanner"
                            class="flex-1 bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center space-x-2"
                        >
                            <i class="fas fa-play"></i>
                            <span>Mulai Scanner</span>
                        </button>
                        <button
                            id="stop-scanner"
                            class="flex-1 bg-red-600 text-white py-3 px-4 rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center space-x-2 hidden"
                        >
                            <i class="fas fa-stop"></i>
                            <span>Berhenti</span>
                        </button>
                    </div>

                    <button
                        id="manual-input"
                        class="w-full bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition-colors flex items-center justify-center space-x-2"
                    >
                        <i class="fas fa-keyboard"></i>
                        <span>Input Manual QR Code</span>
                    </button>
                </div>

                <!-- Camera Selection -->
                <div class="mt-4 hidden" id="camera-selection">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Kamera:</label>
                    <select id="camera-select" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </select>
                </div>
            </div>

            <!-- Manual Input & Info -->
            <div class="space-y-6">
                <!-- Manual Input Modal Trigger -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6" data-aos="fade-up" data-aos-delay="100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        Informasi Absensi
                    </h3>

                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-600">Status Waktu:</span>
                            <span class="font-medium <?php echo $is_attendance_time ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $is_attendance_time ? 'Jam Absensi' : 'Di Luar Jam Absensi'; ?>
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-600">Jam Mulai:</span>
                            <span class="font-medium text-gray-900"><?php echo $attendance_start_time; ?></span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-600">Jam Selesai:</span>
                            <span class="font-medium text-gray-900"><?php echo $attendance_end_time; ?></span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-600">Toleransi:</span>
                            <span class="font-medium text-gray-900"><?php echo $late_tolerance; ?> menit</span>
                        </div>
                    </div>

                    <?php if (!$is_attendance_time): ?>
                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-xs text-yellow-800">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            <strong>Peringatan:</strong> Saat ini di luar jam absensi. Absensi akan dicatat sebagai terlambat atau izin khusus.
                        </p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Instructions -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl border border-blue-200 p-6" data-aos="fade-up" data-aos-delay="200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-question-circle text-indigo-500 mr-2"></i>
                        Cara Menggunakan
                    </h3>

                    <div class="space-y-3 text-sm">
                        <div class="flex items-start">
                            <div class="bg-indigo-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">1</div>
                            <p>Klik tombol <strong>"Mulai Scanner"</strong> untuk mengaktifkan kamera</p>
                        </div>

                        <div class="flex items-start">
                            <div class="bg-indigo-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">2</div>
                            <p>Arahkan kamera ke QR Code absensi yang tersedia</p>
                        </div>

                        <div class="flex items-start">
                            <div class="bg-indigo-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">3</div>
                            <p>QR Code akan otomatis terdeteksi dan absensi akan tercatat</p>
                        </div>

                        <div class="flex items-start">
                            <div class="bg-indigo-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">4</div>
                            <p>Atau gunakan <strong>"Input Manual"</strong> jika scanner tidak berfungsi</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6" data-aos="fade-up" data-aos-delay="300">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                        Aksi Cepat
                    </h3>

                    <div class="grid grid-cols-2 gap-3">
                        <a href="qr-code.php" class="p-3 bg-blue-50 hover:bg-blue-100 rounded-lg text-center transition-colors">
                            <i class="fas fa-qrcode text-blue-600 text-xl mb-2"></i>
                            <p class="text-sm font-medium text-blue-700">QR Saya</p>
                        </a>

                        <a href="history.php" class="p-3 bg-green-50 hover:bg-green-100 rounded-lg text-center transition-colors">
                            <i class="fas fa-history text-green-600 text-xl mb-2"></i>
                            <p class="text-sm font-medium text-green-700">Riwayat</p>
                        </a>

                        <a href="sick-letter.php" class="p-3 bg-red-50 hover:bg-red-100 rounded-lg text-center transition-colors">
                            <i class="fas fa-file-medical text-red-600 text-xl mb-2"></i>
                            <p class="text-sm font-medium text-red-700">Surat Sakit</p>
                        </a>

                        <button onclick="showHelp()" class="p-3 bg-purple-50 hover:bg-purple-100 rounded-lg text-center transition-colors">
                            <i class="fas fa-question text-purple-600 text-xl mb-2"></i>
                            <p class="text-sm font-medium text-purple-700">Bantuan</p>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Manual Input Modal -->
<div id="manualInputModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Input Manual QR Code</h3>
                <button onclick="closeManualInputModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="manualInputForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data QR Code:</label>
                    <textarea
                        id="qr-data-input"
                        rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Paste data QR code di sini..."
                        required
                    ></textarea>
                </div>

                <div class="flex space-x-3">
                    <button
                        type="submit"
                        class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700"
                    >
                        Proses Absensi
                    </button>
                    <button
                        type="button"
                        onclick="closeManualInputModal()"
                        class="flex-1 bg-gray-300 text-gray-700 py-2 rounded-lg hover:bg-gray-400"
                    >
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .scanner-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .scanner-box {
        position: relative;
        width: 200px;
        height: 200px;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .corner {
        position: absolute;
        width: 20px;
        height: 20px;
        border: 3px solid #3b82f6;
    }

    .corner.top-left {
        top: -3px;
        left: -3px;
        border-right: none;
        border-bottom: none;
    }

    .corner.top-right {
        top: -3px;
        right: -3px;
        border-left: none;
        border-bottom: none;
    }

    .corner.bottom-left {
        bottom: -3px;
        left: -3px;
        border-right: none;
        border-top: none;
    }

    .corner.bottom-right {
        bottom: -3px;
        right: -3px;
        border-left: none;
        border-top: none;
    }

    .scanning-line {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, transparent, #3b82f6, transparent);
        animation: scan 2s linear infinite;
    }

    @keyframes scan {
        0% {
            transform: translateY(0);
        }
        100% {
            transform: translateY(200px);
        }
    }

    #qr-scanner video {
        width: 100%;
        height: 300px;
        object-fit: cover;
    }
</style>

<script>
    let html5QrCode = null;
    let cameras = [];

    // Initialize QR Scanner
    function initQRScanner() {
        // Get available cameras
        Html5Qrcode.getCameras().then(devices => {
            if (devices && devices.length) {
                cameras = devices;

                // Populate camera selection
                const select = document.getElementById('camera-select');
                select.innerHTML = '';
                devices.forEach((device, index) => {
                    const option = document.createElement('option');
                    option.value = device.id;
                    option.text = device.label || `Camera ${index + 1}`;
                    select.appendChild(option);
                });

                if (devices.length > 1) {
                    document.getElementById('camera-selection').classList.remove('hidden');
                }

                // Initialize with first camera
                html5QrCode = new Html5Qrcode("qr-scanner");
                document.getElementById('scanner-loading').classList.add('hidden');
                document.getElementById('qr-scanner').classList.remove('hidden');
            } else {
                showToast('error', 'Tidak ada kamera yang tersedia');
                document.getElementById('scanner-loading').innerHTML = `
                    <div class="text-center text-white">
                        <i class="fas fa-camera-slash text-4xl mb-3"></i>
                        <p>Kamera tidak tersedia</p>
                        <p class="text-sm opacity-75">Gunakan input manual</p>
                    </div>
                `;
            }
        }).catch(err => {
            console.error('Error getting cameras:', err);
            showToast('error', 'Error mengakses kamera');
        });
    }

    // Start QR Scanner
    function startScanner() {
        if (!html5QrCode) {
            initQRScanner();
            return;
        }

        const cameraId = document.getElementById('camera-select').value || cameras[0]?.id;

        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        };

        html5QrCode.start(cameraId, config, onScanSuccess, onScanFailure)
            .then(() => {
                document.getElementById('start-scanner').classList.add('hidden');
                document.getElementById('stop-scanner').classList.remove('hidden');
                showToast('success', 'Scanner QR aktif');
            })
            .catch(err => {
                console.error('Error starting scanner:', err);
                showToast('error', 'Gagal memulai scanner');
            });
    }

    // Stop QR Scanner
    function stopScanner() {
        if (html5QrCode) {
            html5QrCode.stop().then(() => {
                document.getElementById('start-scanner').classList.remove('hidden');
                document.getElementById('stop-scanner').classList.add('hidden');
                showToast('info', 'Scanner QR dihentikan');
            }).catch(err => {
                console.error('Error stopping scanner:', err);
            });
        }
    }

    // QR Scan Success Handler
    function onScanSuccess(decodedText, decodedResult) {
        console.log('QR Code detected:', decodedText);
        stopScanner();
        processAttendance(decodedText);
    }

    // QR Scan Failure Handler
    function onScanFailure(error) {
        // Ignore scan failures (they happen frequently)
        // console.log('Scan failed:', error);
    }

    // Process Attendance
    function processAttendance(qrData) {
        showLoading();

        fetch('../api/process-attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                qr_data: qrData
            })
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Absensi Berhasil!',
                    text: data.message,
                    confirmButtonColor: '#10b981'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Absensi Gagal!',
                    text: data.message,
                    confirmButtonColor: '#ef4444'
                });
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Terjadi kesalahan jaringan',
                confirmButtonColor: '#ef4444'
            });
        });
    }

    // Manual Input Modal
    function showManualInputModal() {
        document.getElementById('manualInputModal').classList.remove('hidden');
    }

    function closeManualInputModal() {
        document.getElementById('manualInputModal').classList.add('hidden');
        document.getElementById('qr-data-input').value = '';
    }

    // Show Help
    function showHelp() {
        Swal.fire({
            title: 'Bantuan Absensi',
            html: `
                <div class="text-left text-sm space-y-3">
                    <div>
                        <strong>Jika Scanner Tidak Berfungsi:</strong>
                        <ul class="list-disc list-inside ml-4 mt-1">
                            <li>Pastikan browser memiliki izin kamera</li>
                            <li>Coba refresh halaman</li>
                            <li>Gunakan input manual sebagai alternatif</li>
                        </ul>
                    </div>

                    <div>
                        <strong>Tips Scanning:</strong>
                        <ul class="list-disc list-inside ml-4 mt-1">
                            <li>Pastikan cahaya cukup terang</li>
                            <li>Jaga jarak yang tepat dengan QR code</li>
                            <li>Pastikan QR code tidak terpotong</li>
                        </ul>
                    </div>

                    <div>
                        <strong>Kontak Support:</strong>
                        <p class="ml-4 mt-1">Hubungi administrator jika mengalami kesulitan</p>
                    </div>
                </div>
            `,
            confirmButtonColor: '#3b82f6',
            confirmButtonText: 'Mengerti'
        });
    }

    // Event Listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Scanner buttons
        document.getElementById('start-scanner').addEventListener('click', startScanner);
        document.getElementById('stop-scanner').addEventListener('click', stopScanner);
        document.getElementById('manual-input').addEventListener('click', showManualInputModal);

        // Camera selection change
        document.getElementById('camera-select').addEventListener('change', function() {
            if (html5QrCode && html5QrCode.getState() === Html5QrcodeScannerState.SCANNING) {
                stopScanner();
                setTimeout(startScanner, 500);
            }
        });

        // Manual input form
        document.getElementById('manualInputForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const qrData = document.getElementById('qr-data-input').value.trim();

            if (qrData) {
                closeManualInputModal();
                processAttendance(qrData);
            } else {
                showToast('error', 'Masukkan data QR code terlebih dahulu');
            }
        });

        // Initialize scanner after page load
        setTimeout(initQRScanner, 1000);
    });

    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        if (html5QrCode) {
            html5QrCode.stop().catch(err => console.log(err));
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>

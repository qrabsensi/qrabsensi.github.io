<?php
session_start();

$page_title = 'QR Code Saya';
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

$qr_code_data = null;
$qr_error = '';

// Generate or get existing QR code
try {
    $conn = getDbConnection();

    // Check if user has existing active QR code
    $stmt = $conn->prepare("
        SELECT * FROM qr_codes
        WHERE user_id = ? AND is_active = TRUE AND expires_at > NOW()
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$current_user['id']]);
    $existing_qr = $stmt->fetch();

    if (!$existing_qr) {
        // Generate new QR code
        $qr_result = generateQRCode($current_user['id']);

        if ($qr_result['success']) {
            $stmt = $conn->prepare("
                SELECT * FROM qr_codes
                WHERE user_id = ? AND is_active = TRUE
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$current_user['id']]);
            $qr_code_data = $stmt->fetch();
        } else {
            $qr_error = $qr_result['message'];
        }
    } else {
        $qr_code_data = $existing_qr;
    }

    // Get QR code usage history
    $stmt = $conn->prepare("
        SELECT a.attendance_date, a.check_in_time, a.status
        FROM attendance a
        WHERE a.student_id = ? AND a.qr_scanned = TRUE
        ORDER BY a.attendance_date DESC
        LIMIT 10
    ");
    $stmt->execute([$student['id'] ?? 0]);
    $qr_history = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("QR Code error: " . $e->getMessage());
    $qr_error = 'Terjadi kesalahan saat memuat QR code.';
}

require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <!-- Page Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center" data-aos="fade-down">
                <div class="bg-gradient-to-br from-blue-500 to-purple-600 p-3 rounded-xl mr-4">
                    <i class="fas fa-qrcode text-white text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">QR Code Saya</h1>
                    <p class="text-gray-600">QR Code untuk absensi digital</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($qr_error): ?>
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4" data-aos="fade-up">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                <span class="text-red-700"><?php echo htmlspecialchars($qr_error); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- QR Code Display -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6" data-aos="fade-up">
                <div class="text-center">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">QR Code Absensi</h2>

                    <?php if ($qr_code_data && file_exists('../' . $qr_code_data['qr_image_path'])): ?>
                    <div class="relative inline-block">
                        <!-- QR Code Image -->
                        <div class="bg-white p-6 rounded-2xl shadow-lg border-4 border-blue-100 mb-4 transform hover:scale-105 transition-all duration-300">
                            <img
                                src="../<?php echo htmlspecialchars($qr_code_data['qr_image_path']); ?>"
                                alt="QR Code"
                                class="w-64 h-64 mx-auto"
                                id="qr-image"
                            >
                        </div>

                        <!-- Scanning Animation Overlay -->
                        <div class="absolute inset-0 pointer-events-none">
                            <div class="scanning-line"></div>
                        </div>
                    </div>

                    <!-- QR Code Info -->
                    <div class="mt-6 space-y-3">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm text-gray-600">Status:</span>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>
                                Aktif
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm text-gray-600">Berlaku hingga:</span>
                            <span class="text-sm font-medium text-gray-900">
                                <?php echo formatDateTime($qr_code_data['expires_at']); ?>
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm text-gray-600">ID Token:</span>
                            <code class="text-xs bg-gray-200 px-2 py-1 rounded font-mono">
                                <?php echo substr($qr_code_data['qr_token'], 0, 8); ?>...
                            </code>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-6 space-y-3">
                        <button
                            onclick="downloadQR()"
                            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center space-x-2"
                        >
                            <i class="fas fa-download"></i>
                            <span>Download QR Code</span>
                        </button>

                        <button
                            onclick="printQR()"
                            class="w-full bg-gray-600 text-white py-3 px-4 rounded-lg hover:bg-gray-700 transition-colors flex items-center justify-center space-x-2"
                        >
                            <i class="fas fa-print"></i>
                            <span>Print QR Code</span>
                        </button>

                        <button
                            onclick="refreshQR()"
                            class="w-full bg-yellow-600 text-white py-3 px-4 rounded-lg hover:bg-yellow-700 transition-colors flex items-center justify-center space-x-2"
                        >
                            <i class="fas fa-sync-alt"></i>
                            <span>Generate Ulang</span>
                        </button>
                    </div>

                    <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-qrcode text-gray-300 text-6xl mb-4"></i>
                        <p class="text-gray-500 mb-4">QR Code tidak tersedia</p>
                        <button
                            onclick="generateNewQR()"
                            class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            Generate QR Code
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Information Panel -->
            <div class="space-y-6">
                <!-- Student Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6" data-aos="fade-up" data-aos-delay="100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-user text-blue-500 mr-2"></i>
                        Informasi Siswa
                    </h3>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Nama:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($current_user['full_name']); ?></span>
                        </div>

                        <?php if ($student): ?>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">NIS:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($student['student_number']); ?></span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Kelas:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($student['class_name'] ?? 'Belum ada kelas'); ?></span>
                        </div>
                        <?php endif; ?>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Email:</span>
                            <span class="font-medium text-sm"><?php echo htmlspecialchars($current_user['email']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Usage Instructions -->
                <div class="bg-gradient-to-br from-green-50 to-blue-50 rounded-xl border border-green-200 p-6" data-aos="fade-up" data-aos-delay="200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-info-circle text-green-500 mr-2"></i>
                        Cara Penggunaan
                    </h3>

                    <div class="space-y-3 text-sm">
                        <div class="flex items-start">
                            <div class="bg-green-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">1</div>
                            <p>Buka halaman <strong>Absensi</strong> atau scan QR menggunakan kamera</p>
                        </div>

                        <div class="flex items-start">
                            <div class="bg-green-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">2</div>
                            <p>Tunjukkan QR Code ini ke kamera atau scanner</p>
                        </div>

                        <div class="flex items-start">
                            <div class="bg-green-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3 mt-0.5">3</div>
                            <p>Tunggu konfirmasi absensi berhasil</p>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-yellow-100 border border-yellow-300 rounded-lg">
                        <p class="text-xs text-yellow-800">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            <strong>Penting:</strong> Gunakan QR code ini hanya untuk absensi resmi. Jaga kerahasiaan QR code Anda.
                        </p>
                    </div>
                </div>

                <!-- QR Usage History -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6" data-aos="fade-up" data-aos-delay="300">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-history text-purple-500 mr-2"></i>
                        Riwayat Penggunaan QR
                    </h3>

                    <?php if (!empty($qr_history)): ?>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        <?php foreach ($qr_history as $history): ?>
                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-sm"><?php echo formatDate($history['attendance_date']); ?></p>
                                <p class="text-xs text-gray-600"><?php echo formatDateTime($history['check_in_time'], 'H:i'); ?></p>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full <?php
                                echo match($history['status']) {
                                    'present' => 'bg-green-100 text-green-800',
                                    'late' => 'bg-yellow-100 text-yellow-800',
                                    'absent' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            ?>">
                                <?php
                                echo match($history['status']) {
                                    'present' => 'Hadir',
                                    'late' => 'Terlambat',
                                    'absent' => 'Tidak Hadir',
                                    default => ucfirst($history['status'])
                                };
                                ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-gray-500 text-sm text-center py-4">Belum ada riwayat penggunaan QR code</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Modal -->
<div id="printModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl max-w-md w-full p-6">
            <div class="text-center">
                <h3 class="text-lg font-semibold mb-4">Print QR Code</h3>
                <div class="mb-4">
                    <img id="printQRImage" src="" alt="QR Code" class="w-48 h-48 mx-auto border">
                </div>
                <div class="text-sm text-gray-600 mb-4">
                    <p><strong><?php echo htmlspecialchars($current_user['full_name']); ?></strong></p>
                    <?php if ($student): ?>
                    <p>NIS: <?php echo htmlspecialchars($student['student_number']); ?></p>
                    <p>Kelas: <?php echo htmlspecialchars($student['class_name'] ?? 'N/A'); ?></p>
                    <?php endif; ?>
                </div>
                <div class="flex space-x-3">
                    <button onclick="doPrint()" class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                        Print
                    </button>
                    <button onclick="closePrintModal()" class="flex-1 bg-gray-300 text-gray-700 py-2 rounded-lg hover:bg-gray-400">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
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
            opacity: 1;
        }
        50% {
            opacity: 0.7;
        }
        100% {
            transform: translateY(256px);
            opacity: 0;
        }
    }

    @media print {
        body * {
            visibility: hidden;
        }
        .print-content, .print-content * {
            visibility: visible;
        }
        .print-content {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
    }
</style>

<script>
    function downloadQR() {
        const qrImage = document.getElementById('qr-image');
        if (!qrImage) return;

        // Create a temporary link element
        const link = document.createElement('a');
        link.download = 'qr-code-<?php echo $student['student_number'] ?? 'user'; ?>.png';

        // Convert image to data URL
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = qrImage.naturalWidth;
        canvas.height = qrImage.naturalHeight;
        ctx.drawImage(qrImage, 0, 0);

        link.href = canvas.toDataURL();
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        showToast('success', 'QR Code berhasil didownload!');
    }

    function printQR() {
        const qrImage = document.getElementById('qr-image');
        if (!qrImage) return;

        document.getElementById('printQRImage').src = qrImage.src;
        document.getElementById('printModal').classList.remove('hidden');
    }

    function closePrintModal() {
        document.getElementById('printModal').classList.add('hidden');
    }

    function doPrint() {
        window.print();
        closePrintModal();
    }

    function refreshQR() {
        Swal.fire({
            title: 'Generate Ulang QR Code?',
            text: 'QR code lama akan tidak berlaku setelah generate ulang.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Generate Ulang',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading();

                // Make AJAX request to regenerate QR code
                fetch('../api/regenerate-qr.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'regenerate'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'QR Code berhasil di-generate ulang.',
                            confirmButtonColor: '#3b82f6'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: data.message || 'Terjadi kesalahan.',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                })
                .catch(error => {
                    hideLoading();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Terjadi kesalahan jaringan.',
                        confirmButtonColor: '#ef4444'
                    });
                });
            }
        });
    }

    function generateNewQR() {
        showLoading();

        fetch('../api/generate-qr.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'generate'
            })
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showToast('success', 'QR Code berhasil digenerate!');
                location.reload();
            } else {
                showToast('error', data.message || 'Gagal generate QR Code');
            }
        })
        .catch(error => {
            hideLoading();
            showToast('error', 'Terjadi kesalahan jaringan');
        });
    }

    // Auto-refresh QR code expiry check
    setInterval(function() {
        const expiryTime = new Date('<?php echo $qr_code_data['expires_at'] ?? ''; ?>').getTime();
        const now = new Date().getTime();
        const timeLeft = expiryTime - now;

        if (timeLeft <= 0) {
            showToast('warning', 'QR Code telah kedaluwarsa. Silakan generate ulang.');
        } else if (timeLeft <= 300000) { // 5 minutes
            showToast('info', 'QR Code akan kedaluwarsa dalam 5 menit.');
        }
    }, 60000); // Check every minute

    // Add visual feedback for QR code interactions
    document.getElementById('qr-image')?.addEventListener('click', function() {
        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
            this.style.transform = 'scale(1)';
        }, 150);
    });

    // Prevent right-click on QR image (basic protection)
    document.getElementById('qr-image')?.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        showToast('info', 'Gunakan tombol download untuk menyimpan QR Code');
    });
</script>

<?php require_once '../includes/footer.php'; ?>

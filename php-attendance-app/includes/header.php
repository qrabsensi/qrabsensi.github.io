<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$current_user = getCurrentUser();
$page_title = $page_title ?? 'Attendance System';
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Absensi QR Code dan Surat Ijin Sakit Digital">
    <meta name="author" content="School Management System">
    <title><?php echo htmlspecialchars($page_title); ?> - Sistem Absensi</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>/assets/images/favicon.ico">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- QR Code Scanner -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <script>
        // Tailwind Config
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'ui-sans-serif', 'system-ui'],
                        'display': ['Poppins', 'Inter', 'ui-sans-serif', 'system-ui'],
                    },
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                            950: '#082f49'
                        },
                        accent: {
                            50: '#fdf4ff',
                            100: '#fae8ff',
                            200: '#f5d0fe',
                            300: '#f0abfc',
                            400: '#e879f9',
                            500: '#d946ef',
                            600: '#c026d3',
                            700: '#a21caf',
                            800: '#86198f',
                            900: '#701a75',
                            950: '#4a044e'
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.6s ease-out',
                        'slide-up': 'slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1)',
                        'slide-down': 'slideDown 0.6s cubic-bezier(0.16, 1, 0.3, 1)',
                        'scale-in': 'scaleIn 0.4s cubic-bezier(0.16, 1, 0.3, 1)',
                        'bounce-in': 'bounceIn 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55)',
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-soft': 'pulseSoft 4s ease-in-out infinite',
                        'wiggle': 'wiggle 1s ease-in-out infinite',
                        'gradient': 'gradient 8s ease infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        slideUp: {
                            '0%': { opacity: '0', transform: 'translateY(100%)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        slideDown: {
                            '0%': { opacity: '0', transform: 'translateY(-30px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        scaleIn: {
                            '0%': { opacity: '0', transform: 'scale(0.9)' },
                            '100%': { opacity: '1', transform: 'scale(1)' }
                        },
                        bounceIn: {
                            '0%': { opacity: '0', transform: 'scale(0.3)' },
                            '50%': { opacity: '1', transform: 'scale(1.05)' },
                            '70%': { opacity: '1', transform: 'scale(0.9)' },
                            '100%': { opacity: '1', transform: 'scale(1)' }
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-20px)' }
                        },
                        pulseSoft: {
                            '0%, 100%': { opacity: '1' },
                            '50%': { opacity: '.8' }
                        },
                        wiggle: {
                            '0%, 100%': { transform: 'rotate(-3deg)' },
                            '50%': { transform: 'rotate(3deg)' }
                        },
                        gradient: {
                            '0%, 100%': { backgroundPosition: '0% 50%' },
                            '50%': { backgroundPosition: '100% 50%' }
                        }
                    },
                    backgroundSize: {
                        '300%': '300%',
                    },
                    boxShadow: {
                        'soft': '0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04)',
                        'medium': '0 4px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                        'large': '0 10px 40px -10px rgba(0, 0, 0, 0.15), 0 20px 25px -5px rgba(0, 0, 0, 0.1)',
                        'colored': '0 8px 30px rgba(59, 130, 246, 0.15)',
                        'glow': '0 0 20px rgba(59, 130, 246, 0.3)',
                    }
                }
            }
        }
    </script>

    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f8fafc;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
            border-radius: 10px;
            border: 2px solid #f8fafc;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #cbd5e1, #94a3b8);
        }

        /* Loading Animation */
        .loading-spinner {
            border: 3px solid #f1f5f9;
            border-top: 3px solid #0ea5e9;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Glass morphism effect */
        .glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .glass-dark {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Gradient backgrounds */
        .bg-gradient-primary {
            background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 50%, #6366f1 100%);
            background-size: 300% 300%;
            animation: gradient 8s ease infinite;
        }

        .bg-gradient-accent {
            background: linear-gradient(135deg, #f59e0b 0%, #ef4444 50%, #ec4899 100%);
            background-size: 300% 300%;
            animation: gradient 8s ease infinite;
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            background-size: 300% 300%;
            animation: gradient 8s ease infinite;
        }

        .bg-gradient-purple {
            background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 50%, #c084fc 100%);
            background-size: 300% 300%;
            animation: gradient 8s ease infinite;
        }

        /* Navigation styles */
        .nav-link {
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .nav-link::before {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            width: 0;
            height: 3px;
            background: linear-gradient(90deg, #0ea5e9, #3b82f6);
            border-radius: 2px;
            transform: translateX(-50%);
            transition: width 0.3s ease;
        }

        .nav-link:hover::before,
        .nav-link-active::before {
            width: 80%;
        }

        .nav-link-active {
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.1), rgba(59, 130, 246, 0.1));
            color: #0ea5e9;
            font-weight: 600;
        }

        /* Card styles */
        .card-hover {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .card-hover:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
        }

        /* Button styles */
        .btn-primary {
            background: linear-gradient(135deg, #0ea5e9, #3b82f6);
            background-size: 200% 200%;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-position: right center;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(14, 165, 233, 0.3);
        }

        /* Mobile menu animation */
        .mobile-menu {
            transform: translateX(-100%);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .mobile-menu.open {
            transform: translateX(0);
        }

        /* Notification badge */
        .notification-badge {
            animation: pulse 2s infinite;
        }

        /* Status indicators */
        .status-indicator {
            position: relative;
            display: inline-block;
        }

        .status-indicator::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 8px;
            height: 8px;
            border: 2px solid white;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .status-online::after { background-color: #10b981; }
        .status-away::after { background-color: #f59e0b; }
        .status-busy::after { background-color: #ef4444; }

        /* Custom focus styles */
        .focus-ring:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.3);
        }

        /* Smooth transitions */
        * {
            transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 font-sans antialiased">
    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-white/90 backdrop-blur-sm z-50 flex items-center justify-center hidden">
        <div class="text-center">
            <div class="loading-spinner mx-auto mb-4"></div>
            <p class="text-gray-600 font-medium">Memuat...</p>
        </div>
    </div>

    <!-- Header/Navigation -->
    <?php if (isLoggedIn()): ?>
    <header class="sticky top-0 z-40 border-b border-white/20 bg-white/80 backdrop-blur-xl shadow-soft" data-aos="slide-down">
        <div class="max-w-7xl mx-auto">
            <!-- Top Bar -->
            <div class="flex items-center justify-between px-4 sm:px-6 lg:px-8 py-4">
                <!-- Logo Section -->
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <div class="bg-gradient-primary p-3 rounded-2xl shadow-colored mr-3">
                            <i class="fas fa-qrcode text-white text-xl"></i>
                        </div>
                        <div class="hidden sm:block">
                            <h1 class="text-xl font-display font-bold bg-gradient-to-r from-primary-600 to-primary-800 bg-clip-text text-transparent">
                                Sistem Absensi
                            </h1>
                            <p class="text-sm text-gray-500 font-medium">QR Code & Digital</p>
                        </div>
                    </div>
                </div>

                <!-- Center Navigation - Desktop -->
                <nav class="hidden lg:flex items-center space-x-1 bg-gray-50/50 rounded-full px-2 py-1">
                    <?php
                    $current_page = basename($_SERVER['PHP_SELF']);
                    $nav_items = [];

                    if (hasRole('admin')) {
                        $nav_items = [
                            ['href' => '/admin/dashboard.php', 'icon' => 'fas fa-home', 'text' => 'Dashboard', 'page' => 'dashboard.php'],
                            ['href' => '/admin/students.php', 'icon' => 'fas fa-user-graduate', 'text' => 'Siswa', 'page' => 'students.php'],
                            ['href' => '/admin/teachers.php', 'icon' => 'fas fa-chalkboard-teacher', 'text' => 'Guru', 'page' => 'teachers.php'],
                            ['href' => '/admin/classes.php', 'icon' => 'fas fa-school', 'text' => 'Kelas', 'page' => 'classes.php'],
                            ['href' => '/admin/attendance.php', 'icon' => 'fas fa-calendar-check', 'text' => 'Absensi', 'page' => 'attendance.php'],
                            ['href' => '/admin/reports.php', 'icon' => 'fas fa-chart-line', 'text' => 'Laporan', 'page' => 'reports.php']
                        ];
                    } elseif (hasRole('teacher')) {
                        $nav_items = [
                            ['href' => '/teacher/dashboard.php', 'icon' => 'fas fa-home', 'text' => 'Dashboard', 'page' => 'dashboard.php'],
                            ['href' => '/teacher/attendance.php', 'icon' => 'fas fa-calendar-check', 'text' => 'Absensi', 'page' => 'attendance.php'],
                            ['href' => '/teacher/sick-letters.php', 'icon' => 'fas fa-file-medical', 'text' => 'Surat Sakit', 'page' => 'sick-letters.php'],
                            ['href' => '/teacher/students.php', 'icon' => 'fas fa-user-graduate', 'text' => 'Siswa', 'page' => 'students.php']
                        ];
                    } else {
                        $nav_items = [
                            ['href' => '/student/dashboard.php', 'icon' => 'fas fa-home', 'text' => 'Beranda', 'page' => 'dashboard.php'],
                            ['href' => '/student/attendance.php', 'icon' => 'fas fa-qrcode', 'text' => 'Absensi', 'page' => 'attendance.php'],
                            ['href' => '/student/qr-code.php', 'icon' => 'fas fa-id-card', 'text' => 'QR Saya', 'page' => 'qr-code.php'],
                            ['href' => '/student/sick-letter.php', 'icon' => 'fas fa-file-medical', 'text' => 'Surat Sakit', 'page' => 'sick-letter.php']
                        ];
                    }

                    foreach ($nav_items as $item):
                        $is_active = ($current_page === $item['page']);
                    ?>
                    <a href="<?php echo BASE_URL . $item['href']; ?>"
                       class="nav-link <?php echo $is_active ? 'nav-link-active' : 'hover:bg-white/50'; ?>
                              px-4 py-2.5 rounded-full text-sm font-medium flex items-center space-x-2 transition-all duration-300">
                        <i class="<?php echo $item['icon']; ?> text-sm"></i>
                        <span><?php echo $item['text']; ?></span>
                    </a>
                    <?php endforeach; ?>
                </nav>

                <!-- Right Section -->
                <div class="flex items-center space-x-3">
                    <!-- Time & Date -->
                    <div class="hidden md:block bg-gradient-to-r from-blue-50 to-indigo-50 px-4 py-2 rounded-xl border border-blue-100">
                        <div class="text-center">
                            <div class="text-xs text-blue-600 font-medium">
                                <?php echo date('l, d M Y'); ?>
                            </div>
                            <div class="text-sm font-bold text-blue-800 live-clock font-mono"></div>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div class="relative">
                        <button class="relative p-2.5 text-gray-600 hover:text-primary-600 hover:bg-white/50 rounded-xl transition-all duration-300 focus-ring group" id="notification-btn">
                            <i class="fas fa-bell text-lg group-hover:animate-wiggle"></i>
                            <span class="notification-badge absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold" id="notification-count">
                                3
                            </span>
                        </button>
                    </div>

                    <!-- User Menu -->
                    <div class="relative" id="user-menu">
                        <button class="flex items-center space-x-3 p-2 rounded-xl hover:bg-white/50 focus-ring transition-all duration-300 group" id="user-menu-btn">
                            <!-- Avatar -->
                            <div class="relative">
                                <?php if ($current_user['profile_picture']): ?>
                                    <img class="h-10 w-10 rounded-xl object-cover ring-2 ring-white shadow-medium"
                                         src="<?php echo BASE_URL . '/' . $current_user['profile_picture']; ?>"
                                         alt="Profile">
                                <?php else: ?>
                                    <div class="h-10 w-10 rounded-xl bg-gradient-primary flex items-center justify-center ring-2 ring-white shadow-medium">
                                        <span class="text-white font-bold text-sm">
                                            <?php echo strtoupper(substr($current_user['full_name'], 0, 2)); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <div class="status-indicator status-online absolute -bottom-1 -right-1"></div>
                            </div>

                            <!-- User Info -->
                            <div class="hidden md:block text-left">
                                <div class="text-sm font-semibold text-gray-900 group-hover:text-primary-600 transition-colors">
                                    <?php echo htmlspecialchars($current_user['full_name']); ?>
                                </div>
                                <div class="text-xs text-gray-500 capitalize font-medium">
                                    <?php echo htmlspecialchars($current_user['role']); ?>
                                </div>
                            </div>

                            <!-- Chevron -->
                            <i class="fas fa-chevron-down text-xs text-gray-400 group-hover:text-primary-500 transition-all duration-300 group-hover:rotate-180"></i>
                        </button>

                        <!-- Dropdown Menu -->
                        <div class="absolute right-0 mt-3 w-64 bg-white rounded-2xl shadow-large border border-gray-100 hidden overflow-hidden" id="user-dropdown">
                            <!-- Profile Header -->
                            <div class="px-6 py-4 bg-gradient-to-r from-primary-50 to-blue-50 border-b border-gray-100">
                                <div class="flex items-center space-x-3">
                                    <?php if ($current_user['profile_picture']): ?>
                                        <img class="h-12 w-12 rounded-xl object-cover" src="<?php echo BASE_URL . '/' . $current_user['profile_picture']; ?>" alt="Profile">
                                    <?php else: ?>
                                        <div class="h-12 w-12 rounded-xl bg-gradient-primary flex items-center justify-center">
                                            <span class="text-white font-bold">
                                                <?php echo strtoupper(substr($current_user['full_name'], 0, 2)); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($current_user['full_name']); ?></div>
                                        <div class="text-sm text-gray-500 capitalize"><?php echo htmlspecialchars($current_user['role']); ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Menu Items -->
                            <div class="py-2">
                                <a href="<?php echo BASE_URL; ?>/profile.php"
                                   class="flex items-center px-6 py-3 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary-600 transition-all duration-200">
                                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-user text-blue-600 text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium">Profil Saya</div>
                                        <div class="text-xs text-gray-500">Kelola informasi akun</div>
                                    </div>
                                </a>

                                <a href="<?php echo BASE_URL; ?>/settings.php"
                                   class="flex items-center px-6 py-3 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary-600 transition-all duration-200">
                                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-cog text-purple-600 text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium">Pengaturan</div>
                                        <div class="text-xs text-gray-500">Konfigurasi aplikasi</div>
                                    </div>
                                </a>

                                <div class="border-t border-gray-100 my-2"></div>

                                <button onclick="confirmLogout()"
                                        class="w-full flex items-center px-6 py-3 text-sm text-red-600 hover:bg-red-50 transition-all duration-200">
                                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-sign-out-alt text-red-600 text-sm"></i>
                                    </div>
                                    <div class="text-left">
                                        <div class="font-medium">Keluar</div>
                                        <div class="text-xs text-red-500">Logout dari sistem</div>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="lg:hidden">
                        <button class="p-2.5 text-gray-600 hover:text-primary-600 hover:bg-white/50 rounded-xl transition-all duration-300 focus-ring" id="mobile-menu-btn">
                            <i class="fas fa-bars text-lg"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div class="lg:hidden mobile-menu fixed inset-y-0 left-0 w-80 bg-white/95 backdrop-blur-xl shadow-2xl z-50" id="mobile-menu">
            <!-- Mobile Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-100">
                <div class="flex items-center space-x-3">
                    <div class="bg-gradient-primary p-2 rounded-xl">
                        <i class="fas fa-qrcode text-white"></i>
                    </div>
                    <div>
                        <div class="font-bold text-gray-900">Sistem Absensi</div>
                        <div class="text-xs text-gray-500">Mobile Menu</div>
                    </div>
                </div>
                <button class="p-2 text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-100 transition-colors" id="close-mobile-menu">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Mobile User Info -->
            <div class="p-6 bg-gradient-to-r from-primary-50 to-blue-50 border-b border-gray-100">
                <div class="flex items-center space-x-4">
                    <?php if ($current_user['profile_picture']): ?>
                        <img class="h-16 w-16 rounded-2xl object-cover" src="<?php echo BASE_URL . '/' . $current_user['profile_picture']; ?>" alt="Profile">
                    <?php else: ?>
                        <div class="h-16 w-16 rounded-2xl bg-gradient-primary flex items-center justify-center">
                            <span class="text-white font-bold text-xl">
                                <?php echo strtoupper(substr($current_user['full_name'], 0, 2)); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <div class="flex-1">
                        <div class="font-bold text-gray-900"><?php echo htmlspecialchars($current_user['full_name']); ?></div>
                        <div class="text-sm text-gray-600 capitalize"><?php echo htmlspecialchars($current_user['role']); ?></div>
                        <div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($current_user['email']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Mobile Navigation Items -->
            <nav class="flex-1 py-4 overflow-y-auto">
                <?php foreach ($nav_items as $item):
                    $is_active = ($current_page === $item['page']);
                ?>
                <a href="<?php echo BASE_URL . $item['href']; ?>"
                   class="<?php echo $is_active ? 'bg-primary-50 border-r-4 border-primary-500 text-primary-700' : 'text-gray-700 hover:bg-gray-50'; ?>
                          flex items-center px-6 py-4 text-base font-medium transition-all duration-200">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-100 to-indigo-100 rounded-xl flex items-center justify-center mr-4">
                        <i class="<?php echo $item['icon']; ?> text-primary-600"></i>
                    </div>
                    <div>
                        <div class="font-medium"><?php echo $item['text']; ?></div>
                        <div class="text-xs text-gray-500 mt-1">Menu utama</div>
                    </div>
                </a>
                <?php endforeach; ?>

                <!-- Mobile Menu Footer -->
                <div class="border-t border-gray-200 mt-6 pt-6 px-6 space-y-1">
                    <a href="<?php echo BASE_URL; ?>/profile.php" class="flex items-center py-3 text-gray-700 hover:text-primary-600 transition-colors">
                        <div class="w-10 h-10 bg-gradient-to-r from-green-100 to-emerald-100 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-user text-green-600"></i>
                        </div>
                        <div>
                            <div class="font-medium">Profil Saya</div>
                            <div class="text-xs text-gray-500">Kelola akun</div>
                        </div>
                    </a>

                    <button onclick="confirmLogout()" class="w-full flex items-center py-3 text-red-600 hover:text-red-700 transition-colors">
                        <div class="w-10 h-10 bg-gradient-to-r from-red-100 to-pink-100 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-sign-out-alt text-red-600"></i>
                        </div>
                        <div class="text-left">
                            <div class="font-medium">Keluar</div>
                            <div class="text-xs text-red-500">Logout sistem</div>
                        </div>
                    </button>
                </div>
            </nav>
        </div>

        <!-- Mobile menu overlay -->
        <div class="lg:hidden fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-40" id="mobile-menu-overlay"></div>
    </header>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="<?php echo isLoggedIn() ? 'min-h-screen' : ''; ?>">
        <!-- Success Message -->
        <?php if (isset($_SESSION['success_message'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: '<?php echo addslashes($_SESSION['success_message']); ?>',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true,
                    background: 'linear-gradient(135deg, #10b981, #059669)',
                    color: 'white'
                });
            });
        </script>
        <?php unset($_SESSION['success_message']); endif; ?>

        <!-- Error Message -->
        <?php if (isset($_SESSION['error_message'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops!',
                    text: '<?php echo addslashes($_SESSION['error_message']); ?>',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true,
                    background: 'linear-gradient(135deg, #ef4444, #dc2626)',
                    color: 'white'
                });
            });
        </script>
        <?php unset($_SESSION['error_message']); endif; ?>

    <script>
        // Initialize AOS with custom settings
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100,
            easing: 'cubic-bezier(0.175, 0.885, 0.32, 1.275)'
        });

        // Enhanced Mobile menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const closeMobileMenu = document.getElementById('close-mobile-menu');
            const mobileMenu = document.getElementById('mobile-menu');
            const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');

            function openMobileMenu() {
                mobileMenu.classList.add('open');
                mobileMenuOverlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }

            function closeMobileMenuFunc() {
                mobileMenu.classList.remove('open');
                mobileMenuOverlay.classList.add('hidden');
                document.body.style.overflow = '';
            }

            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', openMobileMenu);
            }

            if (closeMobileMenu) {
                closeMobileMenu.addEventListener('click', closeMobileMenuFunc);
            }

            if (mobileMenuOverlay) {
                mobileMenuOverlay.addEventListener('click', closeMobileMenuFunc);
            }

            // Close mobile menu with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !mobileMenu.classList.contains('hidden')) {
                    closeMobileMenuFunc();
                }
            });

            // Enhanced User dropdown functionality
            const userMenuBtn = document.getElementById('user-menu-btn');
            const userDropdown = document.getElementById('user-dropdown');

            if (userMenuBtn) {
                userMenuBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    userDropdown.classList.toggle('hidden');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(event) {
                    if (!document.getElementById('user-menu').contains(event.target)) {
                        userDropdown.classList.add('hidden');
                    }
                });
            }

            // Notification button functionality
            const notificationBtn = document.getElementById('notification-btn');
            if (notificationBtn) {
                notificationBtn.addEventListener('click', function() {
                    // Show notifications modal
                    showNotifications();
                });
            }
        });

        // Enhanced Logout confirmation with custom styling
        function confirmLogout() {
            Swal.fire({
                title: 'Konfirmasi Keluar',
                text: 'Apakah Anda yakin ingin keluar dari sistem?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: 'linear-gradient(135deg, #ef4444, #dc2626)',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '<i class="fas fa-sign-out-alt mr-2"></i>Ya, Keluar',
                cancelButtonText: '<i class="fas fa-times mr-2"></i>Batal',
                reverseButtons: true,
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-lg font-medium',
                    cancelButton: 'rounded-lg font-medium'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoading();
                    // Add fade out animation
                    document.body.style.opacity = '0.5';
                    setTimeout(() => {
                        window.location.href = '<?php echo BASE_URL; ?>/logout.php';
                    }, 500);
                }
            });
        }

        // Enhanced Loading overlay functions
        function showLoading(message = 'Memuat...') {
            const overlay = document.getElementById('loading-overlay');
            const text = overlay.querySelector('p');
            text.textContent = message;
            overlay.classList.remove('hidden');
            overlay.classList.add('animate-fade-in');
        }

        function hideLoading() {
            const overlay = document.getElementById('loading-overlay');
            overlay.classList.add('animate-fade-out');
            setTimeout(() => {
                overlay.classList.add('hidden');
                overlay.classList.remove('animate-fade-in', 'animate-fade-out');
            }, 300);
        }

        // Enhanced AJAX error handler
        function handleAjaxError(xhr, status, error) {
            hideLoading();
            console.error('AJAX Error:', error);

            let message = 'Terjadi kesalahan. Silakan coba lagi.';
            let title = 'Error!';

            if (xhr.responseJSON) {
                message = xhr.responseJSON.message || message;
                title = xhr.responseJSON.title || title;
            }

            Swal.fire({
                icon: 'error',
                title: title,
                text: message,
                confirmButtonColor: '#0ea5e9',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-lg font-medium'
                }
            });
        }

        // Enhanced toast notifications
        function showToast(type, message, title = null) {
            const config = {
                icon: type,
                text: message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: type === 'error' ? 5000 : 3000,
                timerProgressBar: true,
                customClass: {
                    popup: 'rounded-2xl shadow-2xl'
                }
            };

            if (title) {
                config.title = title;
            }

            // Custom backgrounds based on type
            switch (type) {
                case 'success':
                    config.background = 'linear-gradient(135deg, #10b981, #059669)';
                    config.color = 'white';
                    break;
                case 'error':
                    config.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
                    config.color = 'white';
                    break;
                case 'warning':
                    config.background = 'linear-gradient(135deg, #f59e0b, #d97706)';
                    config.color = 'white';
                    break;
                case 'info':
                    config.background = 'linear-gradient(135deg, #0ea5e9, #0284c7)';
                    config.color = 'white';
                    break;
            }

            Swal.fire(config);
        }

        // Notification system
        function showNotifications() {
            Swal.fire({
                title: 'Notifikasi',
                html: `
                    <div class="text-left space-y-3">
                        <div class="flex items-center p-3 bg-blue-50 rounded-lg">
                            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-info text-white text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">Sistem Update</div>
                                <div class="text-sm text-gray-600">Sistem telah diperbarui ke versi terbaru</div>
                                <div class="text-xs text-gray-500 mt-1">2 jam yang lalu</div>
                            </div>
                        </div>

                        <div class="flex items-center p-3 bg-green-50 rounded-lg">
                            <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-check text-white text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">Absensi Berhasil</div>
                                <div class="text-sm text-gray-600">Absensi hari ini telah tercatat</div>
                                <div class="text-xs text-gray-500 mt-1">5 jam yang lalu</div>
                            </div>
                        </div>

                        <div class="flex items-center p-3 bg-purple-50 rounded-lg">
                            <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-calendar text-white text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">Pengingat</div>
                                <div class="text-sm text-gray-600">Jangan lupa absen besok pagi</div>
                                <div class="text-xs text-gray-500 mt-1">1 hari yang lalu</div>
                            </div>
                        </div>
                    </div>
                `,
                showCloseButton: true,
                showConfirmButton: false,
                customClass: {
                    popup: 'rounded-2xl max-w-md',
                    closeButton: 'rounded-full'
                },
                width: '28rem'
            });
        }

        // Real-time clock update
        function updateClock() {
            const now = new Date();
            const options = {
                timeZone: 'Asia/Jakarta',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            };

            const clockElements = document.querySelectorAll('.live-clock');
            clockElements.forEach(element => {
                element.textContent = now.toLocaleTimeString('id-ID', options);
            });
        }

        // Update clock every second with smooth transition
        setInterval(updateClock, 1000);
        updateClock(); // Initial call

        // Enhanced keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + S to save (prevent default save)
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                const saveBtn = document.querySelector('[data-save-shortcut]');
                if (saveBtn && !saveBtn.disabled) {
                    saveBtn.click();
                    showToast('info', 'Shortcut: Ctrl+S untuk menyimpan');
                }
            }

            // Alt + D for dashboard
            if (e.altKey && e.key === 'd') {
                e.preventDefault();
                const dashboardLink = document.querySelector('a[href*="dashboard"]');
                if (dashboardLink) dashboardLink.click();
            }

            // Alt + P for profile
            if (e.altKey && e.key === 'p') {
                e.preventDefault();
                const profileLink = document.querySelector('a[href*="profile"]');
                if (profileLink) profileLink.click();
            }
        });

        // Performance monitoring with better UX feedback
        window.addEventListener('load', function() {
            const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;

            if (loadTime > 3000) {
                console.warn('Page load time is slow:', loadTime + 'ms');
                // Show subtle performance warning
                setTimeout(() => {
                    showToast('warning', 'Koneksi lambat terdeteksi', 'Performa');
                }, 2000);
            } else if (loadTime < 1000) {
                // Show performance success for fast loads
                console.log('Fast page load:', loadTime + 'ms');
            }
        });

        // Enhanced console branding
        console.log('%c🎓 Sistem Absensi QR Code', 'color: #0ea5e9; font-size: 20px; font-weight: bold; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);');
        console.log('%cDeveloped with ❤️ using Modern PHP & JavaScript', 'color: #6b7280; font-size: 14px; font-style: italic;');
        console.log('%cVersion 1.0.0 - Performance Optimized', 'color: #10b981; font-size: 12px;');

        // Smooth scroll behavior for anchor links
        document.addEventListener('click', function(e) {
            if (e.target.matches('a[href^="#"]')) {
                e.preventDefault();
                const target = document.querySelector(e.target.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });

        // Add loading states to all buttons
        function setButtonLoading(button, loading = true) {
            if (loading) {
                button.disabled = true;
                button.dataset.originalContent = button.innerHTML;
                button.innerHTML = `
                    <div class="flex items-center justify-center">
                        <div class="loading-spinner mr-2"></div>
                        <span>Memproses...</span>
                    </div>
                `;
                button.classList.add('opacity-75');
            } else {
                button.disabled = false;
                button.innerHTML = button.dataset.originalContent || button.innerHTML;
                button.classList.remove('opacity-75');
            }
        }

        // Auto-hide alerts after user interaction
        let alertTimeout;
        document.addEventListener('click', function() {
            if (alertTimeout) {
                clearTimeout(alertTimeout);
            }
            alertTimeout = setTimeout(() => {
                const alerts = document.querySelectorAll('.swal2-container');
                alerts.forEach(alert => {
                    if (alert.style.display !== 'none') {
                        Swal.close();
                    }
                });
            }, 10000); // Auto-hide after 10 seconds of inactivity
        });
    </script>

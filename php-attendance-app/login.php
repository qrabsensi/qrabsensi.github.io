<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? 'student';
    header('Location: /' . $role . '/dashboard.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_message = 'Email dan password harus diisi!';
    } else {
        try {
            $conn = getDbConnection();
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_active = TRUE");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && verifyPassword($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Log login activity
                logActivity($user['id'], 'login', 'User logged in successfully');

                // Redirect based on role
                $redirect_url = '/' . $user['role'] . '/dashboard.php';
                header('Location: ' . $redirect_url);
                exit();
            } else {
                $error_message = 'Email atau password salah!';
            }
        } catch (Exception $e) {
            $error_message = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}

$page_title = 'Login';
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Absensi QR Code - Login untuk mengakses dashboard">
    <title>Login - Sistem Absensi QR Code</title>

    <!-- Preload Critical Resources -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" as="style">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif'],
                        'display': ['Outfit', 'system-ui', 'sans-serif'],
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
                        dark: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                            950: '#020617'
                        }
                    },
                    animation: {
                        'gradient-xy': 'gradient-xy 15s ease infinite',
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 4s ease-in-out infinite',
                        'slide-up': 'slide-up 0.8s cubic-bezier(0.16, 1, 0.3, 1)',
                        'slide-in': 'slide-in 0.6s cubic-bezier(0.16, 1, 0.3, 1)',
                        'fade-in': 'fade-in 0.8s ease-out',
                        'scale-in': 'scale-in 0.5s cubic-bezier(0.16, 1, 0.3, 1)',
                        'bounce-gentle': 'bounce-gentle 2s ease-in-out infinite',
                        'shimmer': 'shimmer 2s linear infinite',
                        'glow': 'glow 2s ease-in-out infinite alternate',
                    },
                    keyframes: {
                        'gradient-xy': {
                            '0%, 100%': {
                                'background-size': '400% 400%',
                                'background-position': 'left center'
                            },
                            '50%': {
                                'background-size': '200% 200%',
                                'background-position': 'right center'
                            }
                        },
                        'float': {
                            '0%, 100%': { transform: 'translateY(0px) rotate(0deg)' },
                            '33%': { transform: 'translateY(-30px) rotate(120deg)' },
                            '66%': { transform: 'translateY(-20px) rotate(240deg)' }
                        },
                        'slide-up': {
                            '0%': { opacity: '0', transform: 'translateY(100px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        'slide-in': {
                            '0%': { opacity: '0', transform: 'translateX(-50px)' },
                            '100%': { opacity: '1', transform: 'translateX(0)' }
                        },
                        'fade-in': {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        },
                        'scale-in': {
                            '0%': { opacity: '0', transform: 'scale(0.9)' },
                            '100%': { opacity: '1', transform: 'scale(1)' }
                        },
                        'bounce-gentle': {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' }
                        },
                        'shimmer': {
                            '0%': { transform: 'translateX(-100%)' },
                            '100%': { transform: 'translateX(100%)' }
                        },
                        'glow': {
                            '0%': { box-shadow: '0 0 20px rgba(14, 165, 233, 0.5)' },
                            '100%': { box-shadow: '0 0 40px rgba(14, 165, 233, 0.8)' }
                        }
                    },
                    backgroundImage: {
                        'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
                        'gradient-conic': 'conic-gradient(from 180deg at 50% 50%, var(--tw-gradient-stops))',
                        'mesh-gradient': 'linear-gradient(135deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #f5576c 75%, #4facfe 100%)'
                    },
                    backdropBlur: {
                        'xs': '2px',
                        'xl': '24px',
                        '2xl': '40px',
                        '3xl': '64px'
                    }
                }
            }
        }
    </script>

    <style>
        /* Custom Styles */
        .gradient-bg {
            background: linear-gradient(135deg, #1e40af 0%, #3730a3 25%, #7c3aed 50%, #db2777 75%, #dc2626 100%);
            background-size: 400% 400%;
            animation: gradient-xy 15s ease infinite;
        }

        .glass-morphism {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(40px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .input-glow:focus {
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1), 0 0 20px rgba(14, 165, 233, 0.2);
        }

        .btn-gradient {
            background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 50%, #6366f1 100%);
            background-size: 200% 200%;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .btn-gradient:hover {
            background-position: right center;
            transform: translateY(-2px);
            box-shadow: 0 20px 40px -10px rgba(14, 165, 233, 0.4);
        }

        .floating-orb {
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .text-gradient {
            background: linear-gradient(135deg, #ffffff 0%, #e0f2fe 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .demo-card:hover {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(30px);
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        /* Loading Animation */
        .loading-dots {
            display: inline-block;
            position: relative;
            width: 20px;
            height: 20px;
        }

        .loading-dots div {
            position: absolute;
            top: 8px;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: currentColor;
            animation-timing-function: cubic-bezier(0, 1, 1, 0);
        }

        .loading-dots div:nth-child(1) {
            left: 2px;
            animation: loading1 0.6s infinite;
        }

        .loading-dots div:nth-child(2) {
            left: 2px;
            animation: loading2 0.6s infinite;
        }

        .loading-dots div:nth-child(3) {
            left: 8px;
            animation: loading2 0.6s infinite;
        }

        .loading-dots div:nth-child(4) {
            left: 14px;
            animation: loading3 0.6s infinite;
        }

        @keyframes loading1 {
            0% { transform: scale(0); }
            100% { transform: scale(1); }
        }

        @keyframes loading3 {
            0% { transform: scale(1); }
            100% { transform: scale(0); }
        }

        @keyframes loading2 {
            0% { transform: translate(0, 0); }
            100% { transform: translate(6px, 0); }
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }
    </style>
</head>

<body class="min-h-screen gradient-bg overflow-x-hidden">

    <!-- Animated Background Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <!-- Floating Orbs -->
        <div class="floating-orb absolute w-96 h-96 rounded-full -top-48 -left-48 animate-float opacity-20"></div>
        <div class="floating-orb absolute w-80 h-80 rounded-full top-1/4 -right-40 animate-float opacity-30" style="animation-delay: 2s;"></div>
        <div class="floating-orb absolute w-64 h-64 rounded-full bottom-1/4 left-1/4 animate-float opacity-25" style="animation-delay: 4s;"></div>
        <div class="floating-orb absolute w-72 h-72 rounded-full -bottom-36 -right-36 animate-float opacity-20" style="animation-delay: 1s;"></div>

        <!-- Gradient Mesh -->
        <div class="absolute inset-0 bg-gradient-to-br from-blue-600/10 via-purple-600/5 to-pink-600/10"></div>

        <!-- Animated Grid -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.03"%3E%3Ccircle cx="7" cy="7" r="1"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-50"></div>
    </div>

    <!-- Main Container -->
    <div class="relative z-10 min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-6xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-12 items-center">

                <!-- Left Side - Branding & Info -->
                <div class="space-y-8 text-center lg:text-left" data-aos="fade-right">
                    <!-- Logo & Title -->
                    <div class="space-y-6">
                        <div class="inline-flex items-center space-x-4 mb-8">
                            <div class="relative">
                                <div class="w-20 h-20 bg-white/20 rounded-3xl flex items-center justify-center glass-morphism animate-bounce-gentle">
                                    <i class="fas fa-qrcode text-white text-3xl"></i>
                                </div>
                                <div class="absolute -top-1 -right-1 w-6 h-6 bg-green-400 rounded-full flex items-center justify-center">
                                    <i class="fas fa-check text-green-800 text-xs"></i>
                                </div>
                            </div>
                            <div>
                                <h1 class="text-5xl lg:text-6xl font-display font-bold text-gradient leading-tight">
                                    QR Attendance
                                </h1>
                                <p class="text-xl text-white/80 font-medium mt-2">Smart School System</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h2 class="text-3xl lg:text-4xl font-display font-bold text-white leading-tight">
                                Sistem Absensi
                                <span class="text-yellow-300">Modern</span>
                            </h2>
                            <p class="text-xl text-white/90 leading-relaxed max-w-lg mx-auto lg:mx-0">
                                Kelola absensi siswa dengan teknologi QR Code dan sistem surat ijin digital yang terintegrasi.
                            </p>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="glass-morphism rounded-2xl p-6 transform hover:scale-105 transition-all duration-300 group">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center group-hover:bg-blue-500/30 transition-colors">
                                    <i class="fas fa-qrcode text-blue-300 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-white text-lg">QR Scanner</h3>
                                    <p class="text-white/70 text-sm">Absensi cepat & akurat</p>
                                </div>
                            </div>
                        </div>

                        <div class="glass-morphism rounded-2xl p-6 transform hover:scale-105 transition-all duration-300 group">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center group-hover:bg-green-500/30 transition-colors">
                                    <i class="fas fa-file-medical text-green-300 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-white text-lg">Digital Forms</h3>
                                    <p class="text-white/70 text-sm">Surat ijin paperless</p>
                                </div>
                            </div>
                        </div>

                        <div class="glass-morphism rounded-2xl p-6 transform hover:scale-105 transition-all duration-300 group">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center group-hover:bg-purple-500/30 transition-colors">
                                    <i class="fas fa-chart-line text-purple-300 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-white text-lg">Analytics</h3>
                                    <p class="text-white/70 text-sm">Laporan real-time</p>
                                </div>
                            </div>
                        </div>

                        <div class="glass-morphism rounded-2xl p-6 transform hover:scale-105 transition-all duration-300 group">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-orange-500/20 rounded-xl flex items-center justify-center group-hover:bg-orange-500/30 transition-colors">
                                    <i class="fas fa-mobile-alt text-orange-300 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-white text-lg">Mobile Ready</h3>
                                    <p class="text-white/70 text-sm">Responsive design</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="glass-morphism rounded-2xl p-6 animate-slide-in">
                        <div class="grid grid-cols-3 gap-6 text-center">
                            <div>
                                <div class="text-3xl font-bold text-white mb-1">99%</div>
                                <div class="text-white/70 text-sm">Akurasi</div>
                            </div>
                            <div>
                                <div class="text-3xl font-bold text-white mb-1">24/7</div>
                                <div class="text-white/70 text-sm">Available</div>
                            </div>
                            <div>
                                <div class="text-3xl font-bold text-white mb-1">∞</div>
                                <div class="text-white/70 text-sm">Scalable</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side - Login Form -->
                <div class="w-full max-w-md mx-auto" data-aos="fade-left">
                    <div class="glass-card rounded-3xl p-8 shadow-2xl animate-scale-in">

                        <!-- Form Header -->
                        <div class="text-center mb-8">
                            <div class="w-16 h-16 bg-gradient-to-br from-primary-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg animate-glow">
                                <i class="fas fa-lock text-white text-xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">Welcome Back!</h3>
                            <p class="text-gray-600">Silakan login untuk mengakses dashboard</p>
                        </div>

                        <!-- Error/Success Messages -->
                        <?php if (!empty($error_message)): ?>
                        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-xl animate-slide-up">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-red-800 text-sm">Login Failed</h4>
                                    <p class="text-red-700 text-sm"><?php echo htmlspecialchars($error_message); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($success_message)): ?>
                        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-xl animate-slide-up">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-check-circle text-green-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-green-800 text-sm">Success</h4>
                                    <p class="text-green-700 text-sm"><?php echo htmlspecialchars($success_message); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Login Form -->
                        <form method="POST" id="loginForm" class="space-y-6">
                            <!-- Email Field -->
                            <div class="space-y-2">
                                <label for="email" class="block text-sm font-semibold text-gray-700">
                                    Email Address
                                </label>
                                <div class="relative">
                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        required
                                        class="input-glow w-full px-4 py-4 pl-12 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-primary-500 transition-all duration-300 text-gray-900 placeholder-gray-400"
                                        placeholder="Enter your email"
                                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                    >
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fas fa-envelope text-gray-400"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Password Field -->
                            <div class="space-y-2">
                                <label for="password" class="block text-sm font-semibold text-gray-700">
                                    Password
                                </label>
                                <div class="relative">
                                    <input
                                        type="password"
                                        id="password"
                                        name="password"
                                        required
                                        class="input-glow w-full px-4 py-4 pl-12 pr-12 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-primary-500 transition-all duration-300 text-gray-900 placeholder-gray-400"
                                        placeholder="Enter your password"
                                    >
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i class="fas fa-key text-gray-400"></i>
                                    </div>
                                    <button
                                        type="button"
                                        class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-primary-600 focus:outline-none transition-colors"
                                        onclick="togglePassword()"
                                        id="togglePasswordBtn"
                                    >
                                        <i class="fas fa-eye" id="passwordIcon"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Remember Me & Forgot Password -->
                            <div class="flex items-center justify-between">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only" id="rememberMe">
                                    <div class="relative">
                                        <div class="w-5 h-5 bg-gray-200 rounded border-2 border-gray-300 transition-all duration-200 checkbox-bg"></div>
                                        <div class="absolute inset-0 flex items-center justify-center opacity-0 checkbox-check transition-opacity duration-200">
                                            <i class="fas fa-check text-white text-xs"></i>
                                        </div>
                                    </div>
                                    <span class="ml-3 text-sm font-medium text-gray-700">Remember me</span>
                                </label>
                                <a href="#" class="text-sm font-semibold text-primary-600 hover:text-primary-700 transition-colors">
                                    Forgot password?
                                </a>
                            </div>

                            <!-- Login Button -->
                            <button
                                type="submit"
                                name="login"
                                id="loginBtn"
                                class="btn-gradient w-full py-4 px-6 rounded-xl font-bold text-white text-lg shadow-lg focus:outline-none focus:ring-4 focus:ring-primary-200 disabled:opacity-70 disabled:cursor-not-allowed"
                            >
                                <span class="flex items-center justify-center" id="loginBtnContent">
                                    <i class="fas fa-sign-in-alt mr-3"></i>
                                    <span>Sign In</span>
                                </span>
                            </button>
                        </form>

                        <!-- Demo Accounts -->
                        <div class="mt-8 border-t border-gray-200 pt-6">
                            <div class="text-center mb-4">
                                <p class="text-sm font-semibold text-gray-700 mb-2">🚀 Quick Demo Access</p>
                                <p class="text-xs text-gray-500">Click any card below to login instantly</p>
                            </div>

                            <div class="space-y-3">
                                <div class="demo-card bg-gradient-to-r from-red-50 to-red-100 p-4 rounded-xl border border-red-200 cursor-pointer transition-all duration-300 hover:shadow-lg" onclick="quickLogin('admin@school.com', 'password')">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 bg-red-500 rounded-xl flex items-center justify-center">
                                                <i class="fas fa-crown text-white text-sm"></i>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-red-800 text-sm">Administrator</div>
                                                <div class="text-red-600 text-xs">Full system access</div>
                                            </div>
                                        </div>
                                        <i class="fas fa-arrow-right text-red-400"></i>
                                    </div>
                                </div>

                                <div class="demo-card bg-gradient-to-r from-blue-50 to-blue-100 p-4 rounded-xl border border-blue-200 cursor-pointer transition-all duration-300 hover:shadow-lg" onclick="quickLogin('teacher1@school.com', 'password')">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 bg-blue-500 rounded-xl flex items-center justify-center">
                                                <i class="fas fa-chalkboard-teacher text-white text-sm"></i>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-blue-800 text-sm">Teacher</div>
                                                <div class="text-blue-600 text-xs">Manage classes</div>
                                            </div>
                                        </div>
                                        <i class="fas fa-arrow-right text-blue-400"></i>
                                    </div>
                                </div>

                                <div class="demo-card bg-gradient-to-r from-green-50 to-green-100 p-4 rounded-xl border border-green-200 cursor-pointer transition-all duration-300 hover:shadow-lg" onclick="quickLogin('student1@school.com', 'password')">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 bg-green-500 rounded-xl flex items-center justify-center">
                                                <i class="fas fa-user-graduate text-white text-sm"></i>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-green-800 text-sm">Student</div>
                                                <div class="text-green-600 text-xs">Daily attendance</div>
                                            </div>
                                        </div>
                                        <i class="fas fa-arrow-right text-green-400"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="text-center mt-6">
                            <p class="text-xs text-gray-500">
                                Secure login • Protected by SSL encryption
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Initialize animations
        document.addEventListener('DOMContentLoaded', function() {
            // Add entrance animations
            setTimeout(() => {
                const elements = document.querySelectorAll('[data-aos]');
                elements.forEach((el, index) => {
                    setTimeout(() => {
                        el.style.opacity = '1';
                        el.style.transform = 'translateY(0) translateX(0) scale(1)';
                    }, index * 100);
                });
            }, 100);

            // Initialize checkbox animation
            const checkbox = document.getElementById('rememberMe');
            const checkboxBg = document.querySelector('.checkbox-bg');
            const checkboxCheck = document.querySelector('.checkbox-check');

            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    checkboxBg.style.background = 'linear-gradient(135deg, #0ea5e9, #3b82f6)';
                    checkboxBg.style.borderColor = '#0ea5e9';
                    checkboxCheck.style.opacity = '1';
                } else {
                    checkboxBg.style.background = '#e5e7eb';
                    checkboxBg.style.borderColor = '#d1d5db';
                    checkboxCheck.style.opacity = '0';
                }
            });
        });

        // Password toggle functionality
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('passwordIcon');

            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Quick login functionality with typing animation
        function quickLogin(email, password) {
            const emailField = document.getElementById('email');
            const passwordField = document.getElementById('password');

            // Clear fields
            emailField.value = '';
            passwordField.value = '';

            // Add focus effect
            emailField.focus();

            // Type email with animation
            let emailIndex = 0;
            const typeEmail = setInterval(() => {
                if (emailIndex < email.length) {
                    emailField.value += email[emailIndex];
                    emailIndex++;
                } else {
                    clearInterval(typeEmail);

                    // Focus password field
                    passwordField.focus();

                    // Type password with animation
                    let passwordIndex = 0;
                    const typePassword = setInterval(() => {
                        if (passwordIndex < password.length) {
                            passwordField.value += password[passwordIndex];
                            passwordIndex++;
                        } else {
                            clearInterval(typePassword);

                            // Add glow effect
                            emailField.style.boxShadow = '0 0 20px rgba(14, 165, 233, 0.3)';
                            passwordField.style.boxShadow = '0 0 20px rgba(14, 165, 233, 0.3)';

                            setTimeout(() => {
                                emailField.style.boxShadow = '';
                                passwordField.style.boxShadow = '';
                            }, 1000);
                        }
                    }, 60);
                }
            }, 80);
        }

        // Enhanced form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const content = document.getElementById('loginBtnContent');

            btn.disabled = true;
            btn.classList.add('opacity-75');

            content.innerHTML = `
                <div class="flex items-center justify-center">
                    <div class="loading-dots mr-3">
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                    </div>
                    <span>Signing in...</span>
                </div>
            `;

            // Add loading animation to form
            this.style.filter = 'blur(1px)';

            // Reset after 3 seconds if no redirect
            setTimeout(() => {
                btn.disabled = false;
                btn.classList.remove('opacity-75');
                this.style.filter = '';
                content.innerHTML = `
                    <i class="fas fa-sign-in-alt mr-3"></i>
                    <span>Sign In</span>
                `;
            }, 3000);
        });

        // Enhanced input validation
        const inputs = document.querySelectorAll('input[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.style.borderColor = '#ef4444';
                    this.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';

                    // Shake animation
                    this.style.animation = 'shake 0.5s ease-in-out';
                    setTimeout(() => {
                        this.style.animation = '';
                    }, 500);
                } else {
                    this.style.borderColor = '#10b981';
                    this.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.1)';
                }
            });

            input.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    this.style.borderColor = '#d1d5db';
                    this.style.boxShadow = '';
                }
            });
        });

        // Mouse interaction effects
        document.addEventListener('mousemove', function(e) {
            const orbs = document.querySelectorAll('.floating-orb');
            const mouseX = e.clientX;
            const mouseY = e.clientY;

            orbs.forEach((orb, index) => {
                const speed = (index + 1) * 0.00003;
                const x = mouseX * speed;
                const y = mouseY * speed;

                orb.style.transform = `translate(${x}px, ${y}px)`;
            });
        });

        // Add shake keyframe
        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(style);

        // Welcome message
        setTimeout(() => {
            console.log('%c🚀 QR Attendance System', 'color: #0ea5e9; font-size: 24px; font-weight: bold; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);');
            console.log('%cModern • Secure • Fast', 'color: #6366f1; font-size: 14px; font-style: italic;');
        }, 1000);

        <?php if (!empty($error_message)): ?>
        // Show error notification
        setTimeout(() => {
            Swal.fire({
                icon: 'error',
                title: 'Login Failed',
                text: '<?php echo addslashes($error_message); ?>',
                confirmButtonColor: '#0ea5e9',
                customClass: {
                    popup: 'rounded-3xl shadow-2xl',
                    confirmButton: 'rounded-xl font-semibold px-6 py-3'
                },
                showClass: {
                    popup: 'animate-scale-in'
                }
            });
        }, 300);
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
        // Show success notification
        setTimeout(() => {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?php echo addslashes($success_message); ?>',
                confirmButtonColor: '#10b981',
                timer: 3000,
                customClass: {
                    popup: 'rounded-3xl shadow-2xl',
                    confirmButton: 'rounded-xl font-semibold px-6 py-3'
                }
            });
        }, 300);
        <?php endif; ?>
    </script>
</body>
</html>

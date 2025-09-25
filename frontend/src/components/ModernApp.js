import React, { useState, useEffect } from "react";
import axios from "axios";
import { QrReader } from "react-qr-reader";
import {
  showSuccessToast,
  showErrorToast,
  showConfirmDialog,
} from "../utils/swal";
import {
  Clock,
  FileText,
  Settings,
  LogOut,
  Users,
  CheckCircle,
  XCircle,
  AlertCircle,
  Camera,
  Upload,
  Download,
  Eye,
  Edit,
  Trash2,
  QrCode,
} from "lucide-react";

// Import the modern theme
import "../styles/modern-theme.css";

// API Service
const API_BASE_URL = process.env.REACT_APP_API_URL || "http://localhost:5000/api";

const api = axios.create({
  baseURL: API_BASE_URL,
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem("token");
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Auth Service
const authService = {
  login: async (email, password) => {
    const response = await api.post("/auth/login", { email, password });
    if (response.data.token) {
      localStorage.setItem("token", response.data.token);
      localStorage.setItem("user", JSON.stringify(response.data.user));
    }
    return response.data;
  },

  register: async (userData) => {
    const response = await api.post("/auth/register", userData);
    if (response.data.token) {
      localStorage.setItem("token", response.data.token);
      localStorage.setItem("user", JSON.stringify(response.data.user));
    }
    return response.data;
  },

  logout: () => {
    localStorage.removeItem("token");
    localStorage.removeItem("user");
  },

  getCurrentUser: () => {
    const user = localStorage.getItem("user");
    return user ? JSON.parse(user) : null;
  },

  getQRCode: async () => {
    const response = await api.get("/auth/qr-code");
    return response.data;
  },
};

// Attendance Service
const attendanceService = {
  clockIn: async (qrCodeData, location) => {
    const response = await api.post("/attendance/clock-in", {
      qrCodeData,
      location,
    });
    return response.data;
  },

  clockOut: async () => {
    const response = await api.post("/attendance/clock-out");
    return response.data;
  },

  getHistory: async (params) => {
    const response = await api.get("/attendance/history", { params });
    return response.data;
  },

  getTodayStatus: async () => {
    const response = await api.get("/attendance/today");
    return response.data;
  },
};

// Permission Service
const permissionService = {
  create: async (formData) => {
    const response = await api.post("/permission", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    return response.data;
  },

  getAll: async (params) => {
    const response = await api.get("/permission", { params });
    return response.data;
  },

  getById: async (id) => {
    const response = await api.get(`/permission/${id}`);
    return response.data;
  },

  update: async (id, formData) => {
    const response = await api.put(`/permission/${id}`, formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    return response.data;
  },

  delete: async (id) => {
    const response = await api.delete(`/permission/${id}`);
    return response.data;
  },
};

// Admin Service
const adminService = {
  getStats: async () => {
    const response = await api.get("/admin/stats");
    return response.data;
  },

  getAllUsers: async (params) => {
    const response = await api.get("/admin/users", { params });
    return response.data;
  },

  getAllAttendance: async (params) => {
    const response = await api.get("/admin/attendance", { params });
    return response.data;
  },

  getAllPermissions: async (params) => {
    const response = await api.get("/admin/permissions", { params });
    return response.data;
  },

  approvePermission: async (id, status, notes) => {
    const response = await api.put(`/admin/permissions/${id}`, {
      status,
      approvalNotes: notes,
    });
    return response.data;
  },

  toggleUser: async (id) => {
    const response = await api.put(`/admin/users/${id}/toggle`);
    return response.data;
  },
};

// Modern Login Form Component
const LoginForm = ({ onLogin, onToggleForm }) => {
  const [formData, setFormData] = useState({ email: "", password: "" });
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [errors, setErrors] = useState({});

  const validateForm = () => {
    const newErrors = {};
    
    if (!formData.email) {
      newErrors.email = "Email wajib diisi";
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = "Format email tidak valid";
    }
    
    if (!formData.password) {
      newErrors.password = "Password wajib diisi";
    } else if (formData.password.length < 6) {
      newErrors.password = "Password minimal 6 karakter";
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }
    
    setLoading(true);
    setErrors({});

    try {
      const response = await authService.login(
        formData.email,
        formData.password,
      );
      showSuccessToast("Login berhasil!");
      onLogin(response.user);
    } catch (error) {
      const errorMessage = (error.response && error.response.data && error.response.data.msg) || "Login gagal";
      showErrorToast(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
      <div className="absolute inset-0 overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-blue-50 to-indigo-100"></div>
        <div className="absolute top-0 left-0 w-full h-full bg-[linear-gradient(45deg,transparent_25%,rgba(59,130,246,0.02)_25%,rgba(59,130,246,0.02)_50%,transparent_50%,transparent_75%,rgba(59,130,246,0.02)_75%)] bg-[length:60px_60px]"></div>
      </div>

      <div className="relative z-10 w-full max-w-md">
        <div className="modern-card animate-slideUp">
          <div className="modern-card-header text-center">
            <div className="w-20 h-20 mx-auto mb-6 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg">
              <QrCode className="w-10 h-10 text-white" />
            </div>
            <h1 className="text-3xl font-bold text-gradient mb-2">Absensi QR</h1>
            <p className="text-gray-600">Sistem Kehadiran Digital</p>
          </div>

          <div className="modern-card-body">
            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="form-group">
                <label className="form-label">Email Address</label>
                <div className="input-group">
                  <input
                    type="email"
                    required
                    className={`form-input ${errors.email ? 'border-red-500' : ''}`}
                    value={formData.email}
                    onChange={(e) => {
                      setFormData({ ...formData, email: e.target.value });
                      if (errors.email) setErrors({ ...errors, email: null });
                    }}
                    placeholder="Enter your email"
                  />
                  <div className="input-icon">
                    <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                    </svg>
                  </div>
                </div>
                {errors.email && (
                  <p className="mt-2 text-sm text-red-600 flex items-center">
                    <AlertCircle className="w-4 h-4 mr-1" />
                    {errors.email}
                  </p>
                )}
              </div>

              <div className="form-group">
                <label className="form-label">Password</label>
                <div className="input-group">
                  <input
                    type={showPassword ? "text" : "password"}
                    required
                    className={`form-input ${errors.password ? 'border-red-500' : ''}`}
                    value={formData.password}
                    onChange={(e) => {
                      setFormData({ ...formData, password: e.target.value });
                      if (errors.password) setErrors({ ...errors, password: null });
                    }}
                    placeholder="Enter your password"
                  />
                  <div className="input-icon">
                    <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                  </div>
                  <button
                    type="button"
                    className="absolute inset-y-0 right-0 pr-4 flex items-center"
                    onClick={() => setShowPassword(!showPassword)}
                  >
                    <svg className="h-5 w-5 text-gray-400 hover:text-gray-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      {showPassword ? (
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L8.464 8.464m1.414 1.414L8.464 8.464m5.656 5.656l1.414 1.414m-1.414-1.414l1.414 1.414M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      ) : (
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      )}
                    </svg>
                  </button>
                </div>
                {errors.password && (
                  <p className="mt-2 text-sm text-red-600 flex items-center">
                    <AlertCircle className="w-4 h-4 mr-1" />
                    {errors.password}
                  </p>
                )}
              </div>

              <button
                type="submit"
                disabled={loading}
                className="btn btn-primary btn-lg w-full"
              >
                {loading ? (
                  <>
                    <div className="loading-spinner"></div>
                    Processing...
                  </>
                ) : (
                  <>
                    <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    Sign In
                  </>
                )}
              </button>

              <div className="text-center">
                <p className="text-gray-600 text-sm">
                  Belum punya akun?{" "}
                  <button
                    type="button"
                    onClick={onToggleForm}
                    className="text-blue-600 hover:text-blue-700 font-semibold transition-colors hover:underline"
                  >
                    Daftar sekarang
                  </button>
                </p>
              </div>
            </form>
          </div>
        </div>

        <div className="text-center mt-6 animate-fadeIn">
          <p className="text-white/70 text-sm">
            © 2024 Absensi QR. Sistem absensi modern & terpercaya.
          </p>
        </div>
      </div>
    </div>
  );
};

const RegisterForm = ({ onRegister, onToggleForm }) => {
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    password: "",
    employeeId: "",
    department: "",
  });
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const response = await authService.register(formData);
      showSuccessToast("Registrasi berhasil!");
      onRegister(response.user);
    } catch (error) {
      showErrorToast(
        (error.response && error.response.data && error.response.data.msg) ||
          "Registrasi gagal",
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
      <div className="absolute inset-0 overflow-hidden">
        <div className="absolute -top-40 -right-40 w-80 h-80 bg-emerald-500 rounded-full opacity-20 animate-pulse"></div>
        <div className="absolute -bottom-40 -left-40 w-96 h-96 bg-green-500 rounded-full opacity-15 animate-pulse"></div>
        <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-72 h-72 bg-teal-500 rounded-full opacity-10 animate-pulse"></div>
      </div>

      <div className="relative z-10 w-full max-w-md">
        <div className="modern-card animate-slideUp">
          <div className="modern-card-header text-center">
            <div className="w-20 h-20 mx-auto mb-6 bg-gradient-to-r from-emerald-500 to-green-500 rounded-2xl flex items-center justify-center shadow-lg">
              <Users className="w-10 h-10 text-white" />
            </div>
            <h1 className="text-3xl font-bold text-gradient mb-2">Daftar Akun</h1>
            <p className="text-gray-600">Bergabung dengan sistem absensi modern</p>
          </div>

          <div className="modern-card-body">
            <form onSubmit={handleSubmit} className="space-y-5">
              <div className="form-group">
                <label className="form-label">Nama Lengkap</label>
                <div className="input-group">
                  <input
                    type="text"
                    required
                    className="form-input"
                    value={formData.name}
                    onChange={(e) =>
                      setFormData({ ...formData, name: e.target.value })
                    }
                    placeholder="John Doe"
                  />
                  <div className="input-icon">
                    <Users className="h-5 w-5" />
                  </div>
                </div>
              </div>

              <div className="form-group">
                <label className="form-label">Email Address</label>
                <div className="input-group">
                  <input
                    type="email"
                    required
                    className="form-input"
                    value={formData.email}
                    onChange={(e) =>
                      setFormData({ ...formData, email: e.target.value })
                    }
                    placeholder="john@email.com"
                  />
                  <div className="input-icon">
                    <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                    </svg>
                  </div>
                </div>
              </div>

              <div className="form-group">
                <label className="form-label">Password</label>
                <div className="input-group">
                  <input
                    type={showPassword ? "text" : "password"}
                    required
                    minLength={6}
                    className="form-input"
                    value={formData.password}
                    onChange={(e) =>
                      setFormData({ ...formData, password: e.target.value })
                    }
                    placeholder="Minimal 6 karakter"
                  />
                  <div className="input-icon">
                    <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                  </div>
                  <button
                    type="button"
                    className="absolute inset-y-0 right-0 pr-4 flex items-center"
                    onClick={() => setShowPassword(!showPassword)}
                  >
                    <svg className="h-5 w-5 text-gray-400 hover:text-gray-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      {showPassword ? (
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L8.464 8.464m1.414 1.414L8.464 8.464m5.656 5.656l1.414 1.414m-1.414-1.414l1.414 1.414M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      ) : (
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      )}
                    </svg>
                  </button>
                </div>
              </div>

              <div className="form-group">
                <label className="form-label">ID Karyawan</label>
                <div className="input-group">
                  <input
                    type="text"
                    required
                    className="form-input"
                    value={formData.employeeId}
                    onChange={(e) =>
                      setFormData({
                        ...formData,
                        employeeId: e.target.value.toUpperCase(),
                      })
                    }
                    placeholder="EMP001"
                  />
                  <div className="input-icon">
                    <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                    </svg>
                  </div>
                </div>
              </div>

              <div className="form-group">
                <label className="form-label">Departemen</label>
                <div className="input-group">
                  <input
                    type="text"
                    className="form-input"
                    value={formData.department}
                    onChange={(e) =>
                      setFormData({ ...formData, department: e.target.value })
                    }
                    placeholder="IT / HR / Finance (opsional)"
                  />
                  <div className="input-icon">
                    <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                  </div>
                </div>
              </div>

              <button
                type="submit"
                disabled={loading}
                className="btn btn-success btn-lg w-full"
              >
                {loading ? (
                  <>
                    <div className="loading-spinner"></div>
                    Memproses...
                  </>
                ) : (
                  "Buat Akun Baru"
                )}
              </button>

              <div className="text-center">
                <p className="text-gray-600 text-sm">
                  Sudah punya akun?{" "}
                  <button
                    type="button"
                    onClick={onToggleForm}
                    className="text-emerald-600 hover:text-emerald-700 font-semibold transition-colors hover:underline"
                  >
                    Login sekarang
                  </button>
                </p>
              </div>
            </form>
          </div>
        </div>

        <div className="text-center mt-6 animate-fadeIn">
          <p className="text-white/70 text-sm">
            © 2024 Absensi QR. Sistem absensi modern & terpercaya.
          </p>
        </div>
      </div>
    </div>
  );
};

const QRScanner = ({ onScan, onClose }) => {
  const [error, setError] = useState("");

  const handleScan = (result, error) => {
    if (result) {
      onScan(result.text);
    }
    if (error) {
      setError("Error scanning QR code");
    }
  };

  return (
    <div className="modern-modal-overlay">
      <div className="modern-modal">
        <div className="modern-modal-header">
          <h3 className="modern-modal-title">Scan QR Code</h3>
          <button
            onClick={onClose}
            className="modern-modal-close"
          >
            <XCircle size={24} />
          </button>
        </div>

        <div className="modern-modal-body">
          <div className="mb-4">
            <QrReader
              onResult={handleScan}
              constraints={{ facingMode: "environment" }}
              videoStyle={{ width: "100%" }}
            />
          </div>

          {error && (
            <div className="text-red-600 text-sm text-center">{error}</div>
          )}

          <div className="text-center text-sm text-gray-600 mt-4">
            Arahkan kamera ke QR Code untuk melakukan absensi
          </div>
        </div>
      </div>
    </div>
  );
};

const AttendanceCard = ({ user, onRefresh }) => {
  const [todayAttendance, setTodayAttendance] = useState(null);
  const [qrCode, setQrCode] = useState("");
  const [showScanner, setShowScanner] = useState(false);
  const [showQR, setShowQR] = useState(false);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    loadTodayAttendance();
    loadQRCode();
  }, []);

  const loadTodayAttendance = async () => {
    try {
      const response = await attendanceService.getTodayStatus();
      setTodayAttendance(response.attendance);
    } catch (error) {
      console.error("Error loading attendance:", error);
    }
  };

  const loadQRCode = async () => {
    try {
      const response = await authService.getQRCode();
      setQrCode(response.qrCode);
    } catch (error) {
      console.error("Error loading QR code:", error);
    }
  };

  const handleScan = async (qrData) => {
    setLoading(true);
    setShowScanner(false);

    try {
      const response = await attendanceService.clockIn(qrData);
      showSuccessToast(response.msg);
      loadTodayAttendance();
      onRefresh();
    } catch (error) {
      showErrorToast(
        (error.response && error.response.data && error.response.data.msg) ||
          "Absensi gagal",
      );
    } finally {
      setLoading(false);
    }
  };

  const handleClockOut = async () => {
    setLoading(true);

    try {
      const response = await attendanceService.clockOut();
      showSuccessToast(response.msg);
      loadTodayAttendance();
      onRefresh();
    } catch (error) {
      showErrorToast(
        (error.response && error.response.data && error.response.data.msg) ||
          "Clock out gagal",
      );
    } finally {
      setLoading(false);
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case "present":
        return "status-badge status-present";
      case "late":
        return "status-badge status-late";
      case "absent":
        return "status-badge status-absent";
      default:
        return "status-badge status-pending";
    }
  };

  const formatTime = (dateString) => {
    return new Date(dateString).toLocaleTimeString("id-ID", {
      hour: "2-digit",
      minute: "2-digit",
    });
  };

  return (
    <>
      <div className="modern-card animate-slideUp">
        <div className="modern-card-header">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-2xl font-bold text-gray-900">
                Absensi Hari Ini
              </h2>
              <p className="text-gray-600">
                {new Date().toLocaleDateString("id-ID", {
                  weekday: "long",
                  year: "numeric",
                  month: "long",
                  day: "numeric",
                })}
              </p>
            </div>
            <Clock className="text-blue-600" size={32} />
          </div>
        </div>

        <div className="modern-card-body">
          {todayAttendance ? (
            <div className="space-y-4">
              <div className="flex items-center justify-between p-4 glass-effect rounded-xl">
                <div>
                  <p className="text-sm text-gray-600">Status</p>
                  <span className={getStatusColor(todayAttendance.status)}>
                    {todayAttendance.status === "present"
                      ? "Hadir"
                      : todayAttendance.status === "late"
                        ? "Terlambat"
                        : "Tidak Hadir"}
                  </span>
                </div>
                <div className="text-right">
                  <p className="text-sm text-gray-600">Clock In</p>
                  <p className="text-lg font-semibold">
                    {formatTime(todayAttendance.clockIn)}
                  </p>
                </div>
              </div>

              {todayAttendance.clockOut ? (
                <div className="p-4 glass-effect rounded-xl border-l-4 border-green-500">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm text-green-600">Clock Out</p>
                      <p className="text-lg font-semibold text-green-800">
                        {formatTime(todayAttendance.clockOut)}
                      </p>
                    </div>
                    <CheckCircle className="text-green-600" size={24} />
                  </div>
                </div>
              ) : (
                <button
                  onClick={handleClockOut}
                  disabled={loading}
                  className="btn btn-error w-full"
                >
                  {loading ? (
                    <>
                      <div className="loading-spinner"></div>
                      Processing...
                    </>
                  ) : (
                    "Clock Out"
                  )}
                </button>
              )}
            </div>
          ) : (
            <div className="space-y-4">
              <div className="text-center py-8">
                <AlertCircle className="mx-auto text-gray-400 mb-4" size={48} />
                <p className="text-gray-600 mb-6">
                  Belum melakukan absensi hari ini
                </p>

                <div className="flex space-x-3">
                  <button
                    onClick={() => setShowScanner(true)}
                    disabled={loading}
                    className="btn btn-primary flex-1"
                  >
                    <Camera className="mr-2" size={20} />
                    {loading ? "Processing..." : "Scan QR Code"}
                  </button>

                  <button
                    onClick={() => setShowQR(!showQR)}
                    className="btn btn-outline"
                  >
                    <Eye size={20} />
                  </button>
                </div>
              </div>

              {showQR && qrCode && (
                <div className="text-center p-4 glass-effect rounded-xl">
                  <p className="text-sm text-gray-600 mb-4">QR Code Anda:</p>
                  <div className="flex justify-center">
                    <img
                      src={qrCode}
                      alt="QR Code untuk absensi"
                      className="w-48 h-48 rounded-xl shadow-lg"
                    />
                  </div>
                  <p className="text-xs text-gray-500 mt-2">
                    Tunjukkan QR Code ini untuk diabsen oleh admin
                  </p>
                </div>
              )}
            </div>
          )}
        </div>
      </div>

      {showScanner && (
        <QRScanner onScan={handleScan} onClose={() => setShowScanner(false)} />
      )}
    </>
  );
};

// Permission Form Content Component for Global Modal
const PermissionFormContent = ({ onSuccess, onCancel, editData }) => {
  const [formData, setFormData] = useState({
    type: editData?.type || "ijin",
    startDate: editData?.startDate
      ? new Date(editData.startDate).toISOString().split("T")[0]
      : "",
    endDate: editData?.endDate
      ? new Date(editData.endDate).toISOString().split("T")[0]
      : "",
    reason: editData?.reason || "",
  });
  const [file, setFile] = useState(null);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const formDataObj = new FormData();
      formDataObj.append("type", formData.type);
      formDataObj.append("startDate", formData.startDate);
      formDataObj.append("endDate", formData.endDate);
      formDataObj.append("reason", formData.reason);

      if (file) {
        formDataObj.append("document", file);
      }

      if (editData) {
        await permissionService.update(editData._id, formDataObj);
        showSuccessToast("Permohonan berhasil diupdate");
      } else {
        await permissionService.create(formDataObj);
        showSuccessToast("Permohonan berhasil diajukan");
      }

      onSuccess();
    } catch (error) {
      showErrorToast(
        (error.response && error.response.data && error.response.data.msg) ||
          "Terjadi kesalahan",
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <div className="modern-modal-header">
        <h3 className="modern-modal-title">
          {editData ? "Edit" : "Ajukan"} Surat Izin/Sakit
        </h3>
        <button
          onClick={onCancel}
          className="modern-modal-close"
        >
          <XCircle size={24} />
        </button>
      </div>

      <div className="modern-modal-body">
        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="form-grid">
            <div className="form-group">
              <label className="form-label">Jenis Permohonan</label>
              <select
                value={formData.type}
                onChange={(e) =>
                  setFormData({ ...formData, type: e.target.value })
                }
                className="form-input"
                required
              >
                <option value="ijin">Izin</option>
                <option value="sakit">Sakit</option>
                <option value="cuti">Cuti</option>
              </select>
            </div>

            <div className="form-group">
              <label className="form-label">Tanggal Mulai</label>
              <input
                type="date"
                value={formData.startDate}
                onChange={(e) =>
                  setFormData({ ...formData, startDate: e.target.value })
                }
                className="form-input"
                required
              />
            </div>

            <div className="form-group">
              <label className="form-label">Tanggal Selesai</label>
              <input
                type="date"
                value={formData.endDate}
                onChange={(e) =>
                  setFormData({ ...formData, endDate: e.target.value })
                }
                className="form-input"
                required
              />
            </div>
          </div>

          <div className="form-group">
            <label className="form-label">Alasan Permohonan</label>
            <textarea
              value={formData.reason}
              onChange={(e) =>
                setFormData({ ...formData, reason: e.target.value })
              }
              className="form-input"
              rows={4}
              placeholder="Jelaskan alasan permohonan secara detail..."
              required
            />
          </div>

          <div className="form-group">
            <label className="form-label">Dokumen Pendukung (Opsional)</label>
            <div className="glass-effect rounded-xl p-4 border border-white/20">
              <div className="flex items-center space-x-3 mb-2">
                <Upload size={24} className="text-blue-500" />
                <div className="flex-1">
                  <input
                    type="file"
                    onChange={(e) => setFile(e.target.files?.[0] || null)}
                    accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                    className="form-input"
                  />
                </div>
              </div>
              <div className="text-xs text-gray-600 mt-2">
                <p className="font-medium mb-1">Format yang didukung:</p>
                <p>• Gambar: JPG, JPEG, PNG</p>
                <p>• Dokumen: PDF, DOC, DOCX</p>
                <p>• Ukuran maksimal: 5MB</p>
              </div>
              {file && (
                <div className="mt-3 p-2 bg-green-50 rounded-lg border border-green-200">
                  <p className="text-sm text-green-700 flex items-center">
                    <CheckCircle size={16} className="mr-2" />
                    File terpilih: {file.name}
                  </p>
                </div>
              )}
            </div>
          </div>
        </form>
      </div>

      <div className="modern-modal-footer">
        <button
          type="button"
          onClick={onCancel}
          className="btn btn-ghost"
        >
          <XCircle size={16} className="mr-2" />
          Batal
        </button>
        <button
          type="submit"
          disabled={loading}
          className="btn btn-primary"
          onClick={handleSubmit}
        >
          {loading ? (
            <>
              <div className="loading-spinner"></div>
              Menyimpan...
            </>
          ) : (
            <>
              <CheckCircle size={16} className="mr-2" />
              {editData ? "Update Permohonan" : "Ajukan Permohonan"}
            </>
          )}
        </button>
      </div>
    </>
  );
};

const PermissionList = ({ showGlobalModal }) => {
  const [permissions, setPermissions] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadPermissions();
  }, []);

  const loadPermissions = async () => {
    try {
      const response = await permissionService.getAll();
      setPermissions(response.permissions);
    } catch (error) {
      showErrorToast("Gagal memuat data permohonan");
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id) => {
    const result = await showConfirmDialog({
      title: "Yakin ingin menghapus permohonan ini?",
    });
    if (!result.isConfirmed) return;

    try {
      await permissionService.delete(id);
      showSuccessToast("Permohonan berhasil dihapus");
      loadPermissions();
    } catch (error) {
      showErrorToast(
        (error.response && error.response.data && error.response.data.msg) ||
          "Gagal menghapus permohonan",
      );
    }
  };

  const handleEdit = (permission) => {
    showPermissionForm(permission);
  };

  const showPermissionForm = (editData = null) => {
    const modalContent = (
      <PermissionFormContent
        onSuccess={() => {
          showGlobalModal(null);
          loadPermissions();
        }}
        onCancel={() => {
          showGlobalModal(null);
        }}
        editData={editData}
      />
    );
    showGlobalModal(modalContent);
  };

  const getStatusColor = (status) => {
    switch (status) {
      case "approved":
        return "status-badge status-approved";
      case "rejected":
        return "status-badge status-rejected";
      default:
        return "status-badge status-pending";
    }
  };

  const getStatusText = (status) => {
    switch (status) {
      case "approved":
        return "Disetujui";
      case "rejected":
        return "Ditolak";
      default:
        return "Menunggu";
    }
  };

  const getTypeText = (type) => {
    switch (type) {
      case "sakit":
        return "Sakit";
      case "cuti":
        return "Cuti";
      default:
        return "Izin";
    }
  };

  if (loading) {
    return (
      <div className="modern-card">
        <div className="modern-card-body text-center py-8">
          <div className="loading-skeleton h-4 w-1/4 mx-auto mb-4"></div>
          <div className="loading-skeleton h-4 w-1/2 mx-auto"></div>
        </div>
      </div>
    );
  }

  return (
    <>
      <div className="modern-card animate-slideUp">
        <div className="modern-card-header">
          <div className="flex justify-between items-center">
            <div>
              <h2 className="text-2xl font-bold text-gray-900">
                Surat Izin/Sakit
              </h2>
              <p className="text-gray-600">
                Kelola permohonan izin dan sakit Anda
              </p>
            </div>
            <button
              onClick={() => showPermissionForm()}
              className="btn btn-primary"
            >
              <FileText className="mr-2" size={20} />
              <span className="hide-on-mobile">Ajukan Baru</span>
              <span className="show-on-mobile">Baru</span>
            </button>
          </div>
        </div>

        <div className="modern-card-body">
          {permissions.length === 0 ? (
            <div className="text-center py-8">
              <FileText className="mx-auto text-gray-400 mb-4" size={48} />
              <p className="text-gray-600">Belum ada permohonan izin/sakit</p>
              <button
                onClick={() => showPermissionForm()}
                className="btn btn-primary mt-4"
              >
                <FileText className="mr-2" size={20} />
                Ajukan Permohonan Pertama
              </button>
            </div>
          ) : (
            <div className="scrollable-section">
              <div className="space-y-4">
                {permissions.map((permission) => (
                  <div
                    key={permission._id}
                    className="glass-effect rounded-xl p-4 border border-white/20 interactive-element"
                  >
                    <div className="flex justify-between items-start mb-3">
                      <div className="flex-1">
                        <div className="flex items-center space-x-2 mb-2">
                          <span className="font-medium text-gray-900">
                            {getTypeText(permission.type)}
                          </span>
                          <span className={getStatusColor(permission.status)}>
                            {getStatusText(permission.status)}
                          </span>
                        </div>
                        <p className="text-sm text-gray-600">
                          {new Date(permission.startDate).toLocaleDateString()} -{" "}
                          {new Date(permission.endDate).toLocaleDateString()}
                        </p>
                        <p className="text-sm text-gray-800 mt-1">
                          {permission.reason}
                        </p>
                        {permission.document && (
                          <a
                            href={`http://localhost:5000/${permission.document.path}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-blue-500 hover:underline text-sm flex items-center mt-2"
                          >
                            <Download size={16} className="mr-1" />
                            Lihat Dokumen
                          </a>
                        )}
                      </div>
                      {permission.status === "pending" && (
                        <div className="ml-4">
                          <div className="hide-on-mobile flex space-x-2">
                            <button
                              onClick={() => handleEdit(permission)}
                              className="btn btn-sm btn-ghost"
                              title="Edit permohonan"
                            >
                              <Edit size={16} />
                            </button>
                            <button
                              onClick={() => handleDelete(permission._id)}
                              className="btn btn-sm btn-error"
                              title="Hapus permohonan"
                            >
                              <Trash2 size={16} />
                            </button>
                          </div>
                          <div className="show-on-mobile">
                            <ActionMenu trigger={<Settings size={16} />}>
                              <button
                                className="action-menu-item"
                                onClick={() => handleEdit(permission)}
                              >
                                <Edit size={16} />
                                Edit
                              </button>
                              <button
                                className="action-menu-item"
                                onClick={() => handleDelete(permission._id)}
                              >
                                <Trash2 size={16} />
                                Hapus
                              </button>
                            </ActionMenu>
                          </div>
                        </div>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>

      <div className="fab-container show-on-mobile">
        <button
          onClick={() => showPermissionForm()}
          className="fab"
          title="Ajukan permohonan baru"
        >
          <FileText size={24} />
        </button>
      </div>
    </>
  );
};

// Action Menu Component for Better Mobile UX
const ActionMenu = ({ children, trigger }) => {
  const [isOpen, setIsOpen] = useState(false);

  return (
    <div className="action-menu">
      <button
        className="action-menu-toggle"
        onClick={() => setIsOpen(!isOpen)}
      >
        {trigger}
      </button>
      <div className={`action-menu-dropdown ${isOpen ? 'open' : ''}`}>
        {children}
      </div>
    </div>
  );
};

const AdminDashboard = () => {
  const [stats, setStats] = useState(null);
  const [users, setUsers] = useState([]);
  const [attendance, setAttendance] = useState([]);
  const [permissions, setPermissions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [expandedSections, setExpandedSections] = useState({
    users: true,
    permissions: false,
    attendance: false,
  });

  useEffect(() => {
    loadAdminData();
  }, []);

  const loadAdminData = async () => {
    try {
      const [statsRes, usersRes, attendanceRes, permissionsRes] =
        await Promise.all([
          adminService.getStats(),
          adminService.getAllUsers(),
          adminService.getAllAttendance(),
          adminService.getAllPermissions(),
        ]);
      setStats(statsRes);
      setUsers(usersRes.users);
      setAttendance(attendanceRes.attendances);
      setPermissions(permissionsRes.permissions);
    } catch (error) {
      showErrorToast("Gagal memuat data admin");
    } finally {
      setLoading(false);
    }
  };

  const handleApproveReject = async (id, status) => {
    const result = await showConfirmDialog({
      title: `Yakin ingin ${status === "approved" ? "menyetujui" : "menolak"} permohonan ini?`,
    });
    if (!result.isConfirmed) return;
    try {
      await adminService.approvePermission(id, status);
      showSuccessToast(
        `Permohonan berhasil di${status === "approved" ? "setujui" : "tolak"}`,
      );
      loadAdminData();
    } catch (error) {
      showErrorToast(
        (error.response && error.response.data && error.response.data.msg) ||
          "Gagal memproses permohonan",
      );
    }
  };

  const handleToggleUser = async (id) => {
    const result = await showConfirmDialog({
      title: "Yakin ingin mengubah status user ini?",
    });
    if (!result.isConfirmed) return;
    try {
      await adminService.toggleUser(id);
      showSuccessToast("Status user berhasil diubah");
      loadAdminData();
    } catch (error) {
      showErrorToast(
        (error.response && error.response.data && error.response.data.msg) ||
          "Gagal mengubah status user",
      );
    }
  };

  const toggleSection = (section) => {
    setExpandedSections(prev => ({
      ...prev,
      [section]: !prev[section]
    }));
  };

  if (loading) {
    return (
      <div className="p-6 text-center">
        <div className="loading-spinner mx-auto mb-4"></div>
        <p>Loading Admin Dashboard...</p>
      </div>
    );
  }

  return (
    <div className="content-area content-spacing">
      <div className="animate-slideDown">
        <h1 className="text-3xl font-bold text-gray-900 mb-2">Admin Dashboard</h1>
        <p className="text-gray-600">Kelola sistem absensi dan pengguna</p>
      </div>

      {stats && (
        <div className="stats-grid animate-slideUp">
          <div className="stats-card">
            <div className="stats-card-header">
              <div className="stats-card-title">Total Pengguna</div>
              <div className="stats-card-icon">
                <Users size={24} />
              </div>
            </div>
            <div className="stats-card-value">{stats.totalUsers}</div>
          </div>
          <div className="stats-card">
            <div className="stats-card-header">
              <div className="stats-card-title">Absensi Hari Ini</div>
              <div className="stats-card-icon">
                <CheckCircle size={24} />
              </div>
            </div>
            <div className="stats-card-value">{stats.todayAttendance}</div>
          </div>
          <div className="stats-card">
            <div className="stats-card-header">
              <div className="stats-card-title">Izin Menunggu</div>
              <div className="stats-card-icon">
                <AlertCircle size={24} />
              </div>
            </div>
            <div className="stats-card-value">{stats.pendingPermissions}</div>
          </div>
          <div className="stats-card">
            <div className="stats-card-header">
              <div className="stats-card-title">Total Izin</div>
              <div className="stats-card-icon">
                <FileText size={24} />
              </div>
            </div>
            <div className="stats-card-value">{stats.totalPermissions}</div>
          </div>
        </div>
      )}

      <div className="collapsible-section animate-slideUp">
        <div 
          className="collapsible-header"
          onClick={() => toggleSection('users')}
        >
          <h2 className="text-2xl font-bold text-gray-900">Manajemen Pengguna</h2>
          <div className="flex items-center gap-2">
            <span className="text-sm text-gray-500">{users.length} pengguna</span>
            {expandedSections.users ? <XCircle size={20} /> : <Eye size={20} />}
          </div>
        </div>
        <div className={`collapsible-content ${expandedSections.users ? 'expanded' : ''}`}>
          <div className="responsive-table-container">
            <div className="overflow-scroll">
              <table className="responsive-table">
                <thead className="sticky-header">
                  <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>ID Karyawan</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  {users.map((user) => (
                    <tr key={user._id}>
                      <td data-label="Nama" className="font-medium text-gray-900">{user.name}</td>
                      <td data-label="Email" className="text-gray-500">{user.email}</td>
                      <td data-label="ID Karyawan" className="text-gray-500">{user.employeeId}</td>
                      <td data-label="Role" className="text-gray-500">{user.role}</td>
                      <td data-label="Status">
                        <span className={`status-badge ${user.isActive ? 'status-approved' : 'status-rejected'}`}>
                          {user.isActive ? "Aktif" : "Nonaktif"}
                        </span>
                      </td>
                      <td data-label="Aksi">
                        {user.role !== "admin" && (
                          <div className="button-group">
                            <button
                              onClick={() => handleToggleUser(user._id)}
                              className={`btn btn-sm ${user.isActive ? 'btn-error' : 'btn-success'}`}
                            >
                              {user.isActive ? "Nonaktifkan" : "Aktifkan"}
                            </button>
                          </div>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div className="collapsible-section animate-slideUp">
        <div 
          className="collapsible-header"
          onClick={() => toggleSection('permissions')}
        >
          <h2 className="text-2xl font-bold text-gray-900">Permohonan Izin/Sakit</h2>
          <div className="flex items-center gap-2">
            <span className="text-sm text-gray-500">{permissions.length} permohonan</span>
            {expandedSections.permissions ? <XCircle size={20} /> : <Eye size={20} />}
          </div>
        </div>
        <div className={`collapsible-content ${expandedSections.permissions ? 'expanded' : ''}`}>
          <div className="responsive-table-container">
            <div className="overflow-scroll">
              <table className="responsive-table">
                <thead className="sticky-header">
                  <tr>
                    <th>Karyawan</th>
                    <th>Tipe</th>
                    <th>Tanggal</th>
                    <th>Alasan</th>
                    <th>Dokumen</th>
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  {permissions.map((permission) => (
                    <tr key={permission._id}>
                      <td data-label="Karyawan" className="font-medium text-gray-900">
                        {permission.user?.name} ({permission.user?.employeeId})
                      </td>
                      <td data-label="Tipe" className="text-gray-500">
                        {permission.type === "sakit" ? "Sakit" : permission.type === "cuti" ? "Cuti" : "Izin"}
                      </td>
                      <td data-label="Tanggal" className="text-gray-500">
                        {new Date(permission.startDate).toLocaleDateString()} -{" "}
                        {new Date(permission.endDate).toLocaleDateString()}
                      </td>
                      <td data-label="Alasan" className="text-gray-500 max-w-xs truncate">
                        {permission.reason}
                      </td>
                      <td data-label="Dokumen">
                        {permission.document && (
                          <a
                            href={`http://localhost:5000/${permission.document.path}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="btn btn-sm btn-ghost"
                          >
                            <Eye size={16} />
                            <span className="hide-on-mobile">Lihat</span>
                          </a>
                        )}
                      </td>
                      <td data-label="Status">
                        <span className={`status-badge ${
                          permission.status === "approved" ? "status-approved" :
                          permission.status === "rejected" ? "status-rejected" : "status-pending"
                        }`}>
                          {permission.status === "approved" ? "Disetujui" :
                           permission.status === "rejected" ? "Ditolak" : "Menunggu"}
                        </span>
                      </td>
                      <td data-label="Aksi">
                        {permission.status === "pending" && (
                          <div className="button-group">
                            <div className="hide-on-mobile flex space-x-2">
                              <button
                                onClick={() => handleApproveReject(permission._id, "approved")}
                                className="btn btn-sm btn-success"
                              >
                                <CheckCircle size={16} />
                                Setujui
                              </button>
                              <button
                                onClick={() => handleApproveReject(permission._id, "rejected")}
                                className="btn btn-sm btn-error"
                              >
                                <XCircle size={16} />
                                Tolak
                              </button>
                            </div>
                            <div className="show-on-mobile">
                              <ActionMenu trigger={<Settings size={16} />}>
                                <button
                                  className="action-menu-item"
                                  onClick={() => handleApproveReject(permission._id, "approved")}
                                >
                                  <CheckCircle size={16} />
                                  Setujui
                                </button>
                                <button
                                  className="action-menu-item"
                                  onClick={() => handleApproveReject(permission._id, "rejected")}
                                >
                                  <XCircle size={16} />
                                  Tolak
                                </button>
                              </ActionMenu>
                            </div>
                          </div>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div className="collapsible-section animate-slideUp">
        <div 
          className="collapsible-header"
          onClick={() => toggleSection('attendance')}
        >
          <h2 className="text-2xl font-bold text-gray-900">Riwayat Absensi</h2>
          <div className="flex items-center gap-2">
            <span className="text-sm text-gray-500">{attendance.length} record</span>
            {expandedSections.attendance ? <XCircle size={20} /> : <Eye size={20} />}
          </div>
        </div>
        <div className={`collapsible-content ${expandedSections.attendance ? 'expanded' : ''}`}>
          <div className="responsive-table-container">
            <div className="scrollable-section">
              <table className="responsive-table">
                <thead className="sticky-header">
                  <tr>
                    <th>Karyawan</th>
                    <th>Tanggal</th>
                    <th>Clock In</th>
                    <th>Clock Out</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  {attendance.map((att) => (
                    <tr key={att._id}>
                      <td data-label="Karyawan" className="font-medium text-gray-900">
                        {att.user?.name} ({att.user?.employeeId})
                      </td>
                      <td data-label="Tanggal" className="text-gray-500">
                        {new Date(att.date).toLocaleDateString()}
                      </td>
                      <td data-label="Clock In" className="text-gray-500">
                        {new Date(att.clockIn).toLocaleTimeString()}
                      </td>
                      <td data-label="Clock Out" className="text-gray-500">
                        {att.clockOut
                          ? new Date(att.clockOut).toLocaleTimeString()
                          : "-"}
                      </td>
                      <td data-label="Status">
                        <span className={`status-badge ${
                          att.status === "present" ? "status-approved" :
                          att.status === "late" ? "status-pending" : "status-rejected"
                        }`}>
                          {att.status === "present"
                            ? "Hadir"
                            : att.status === "late"
                              ? "Terlambat"
                              : "Tidak Hadir"}
                        </span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

const UserDashboard = ({ user, onLogout }) => {
  const [activeTab, setActiveTab] = useState("attendance");
  const [refreshKey, setRefreshKey] = useState(0);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [globalModal, setGlobalModal] = useState(null);

  const handleRefresh = () => {
    setRefreshKey((prev) => prev + 1);
  };

  const showGlobalModal = (modalContent) => {
    setGlobalModal(modalContent);
  };

  const hideGlobalModal = () => {
    setGlobalModal(null);
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
      <nav className="modern-nav">
        <div className="modern-nav-content">
          <div className="modern-nav-brand">
            <div className="modern-nav-brand-icon">
              <QrCode size={20} />
            </div>
            <span>Absensi QR</span>
          </div>
          
          <button
            className="mobile-nav-toggle"
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
          >
            <Settings size={20} />
          </button>

          <div className={`modern-nav-user ${mobileMenuOpen ? 'mobile-open' : ''}`}>
            <div className="text-right">
              <p className="text-sm font-medium text-gray-900">{user.name}</p>
              <p className="text-xs text-gray-500">{user.role} • {user.employeeId}</p>
            </div>
            <div className="modern-nav-avatar">
              {user.name.charAt(0).toUpperCase()}
            </div>
            <button
              onClick={onLogout}
              className="btn btn-error btn-sm"
            >
              <LogOut size={16} />
              <span className="hide-on-mobile">Logout</span>
            </button>
          </div>
        </div>
      </nav>

      <div className="container mx-auto p-6 max-w-7xl">
        <div className="modern-card mb-6">
          <div className="modern-card-body">
            <div className="tab-navigation">
              <button
                onClick={() => setActiveTab("attendance")}
                className={`tab-button ${activeTab === "attendance" ? 'active' : ''}`}
              >
                <Clock size={20} />
                <span className="hide-on-mobile">Absensi</span>
              </button>
              <button
                onClick={() => setActiveTab("permission")}
                className={`tab-button ${activeTab === "permission" ? 'active' : ''}`}
              >
                <FileText size={20} />
                <span className="hide-on-mobile">Izin/Sakit</span>
              </button>
              {user.role === "admin" && (
                <button
                  onClick={() => setActiveTab("admin")}
                  className={`tab-button ${activeTab === "admin" ? 'active' : ''}`}
                >
                  <Settings size={20} />
                  <span className="hide-on-mobile">Admin</span>
                </button>
              )}
            </div>

            <div className="content-section">
              {activeTab === "attendance" && (
                <AttendanceCard
                  user={user}
                  onRefresh={handleRefresh}
                  key={`attendance-${refreshKey}`}
                />
              )}
              {activeTab === "permission" && (
                <PermissionList 
                  showGlobalModal={showGlobalModal}
                  key={`permission-${refreshKey}`} 
                />
              )}
              {activeTab === "admin" && user.role === "admin" && (
                <AdminDashboard key={`admin-${refreshKey}`} />
              )}
            </div>
          </div>
        </div>
      </div>

      {globalModal && (
        <div className="modern-modal-overlay" onClick={hideGlobalModal}>
          <div className="modern-modal large" onClick={(e) => e.stopPropagation()}>
            {globalModal}
          </div>
        </div>
      )}
    </div>
  );
};

// Main Modern App Component
const ModernApp = () => {
  const [user, setUser] = useState(authService.getCurrentUser());
  const [showRegister, setShowRegister] = useState(false);

  useEffect(() => {
    const currentUser = authService.getCurrentUser();
    if (currentUser) {
      setUser(currentUser);
    }
  }, []);

  const handleLogin = (loggedInUser) => {
    setUser(loggedInUser);
    setShowRegister(false);
  };

  const handleRegister = (registeredUser) => {
    setUser(registeredUser);
    setShowRegister(false);
  };

  const handleLogout = () => {
    authService.logout();
    setUser(null);
    setShowRegister(false);
  };

  if (user) {
    return <UserDashboard user={user} onLogout={handleLogout} />;
  }

  return showRegister ? (
    <RegisterForm
      onRegister={handleRegister}
      onToggleForm={() => setShowRegister(false)}
    />
  ) : (
    <LoginForm
      onLogin={handleLogin}
      onToggleForm={() => setShowRegister(true)}
    />
  );
};

export default ModernApp;

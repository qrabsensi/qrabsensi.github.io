# Sistem Absensi QR Code & Surat Ijin Sakit Digital

Sebuah sistem absensi modern berbasis web yang menggunakan teknologi QR Code dan digitalisasi surat ijin sakit untuk sekolah/institusi pendidikan.

## 🌟 Fitur Utama

### 👨‍🎓 Untuk Siswa
- **QR Code Absensi**: Scan QR Code untuk absensi masuk dan keluar
- **QR Code Personal**: Generate QR Code pribadi untuk absensi
- **Surat Ijin Sakit Digital**: Buat dan kirim surat ijin sakit secara online
- **Dashboard Interaktif**: Lihat statistik absensi dan status terkini
- **Riwayat Absensi**: Akses riwayat kehadiran lengkap
- **Notifikasi Real-time**: Pemberitahuan status absensi

### 👨‍🏫 Untuk Guru
- **Monitoring Kelas**: Pantau absensi siswa dalam kelas
- **Persetujuan Surat**: Review dan setujui surat ijin sakit
- **Laporan Absensi**: Generate laporan kehadiran siswa
- **Manajemen Siswa**: Kelola data siswa dalam kelas

### 👨‍💼 Untuk Administrator
- **Dashboard Admin**: Statistik lengkap sistem
- **Manajemen User**: Kelola data siswa, guru, dan admin
- **Manajemen Kelas**: Atur kelas dan pembagian siswa
- **Pengaturan Sistem**: Konfigurasi jam absensi dan toleransi
- **Laporan Komprehensif**: Export data dalam berbagai format

## 🛠 Technology Stack

### Backend
- **PHP 8.0+** - Server-side scripting
- **MySQL 8.0+** - Database management
- **PDO** - Database connectivity
- **PHPQRCode** - QR Code generation
- **JWT** - Session management (optional)

### Frontend
- **HTML5** - Modern web structure
- **Tailwind CSS** - Utility-first CSS framework
- **JavaScript ES6+** - Client-side scripting
- **SweetAlert2** - Beautiful alerts and modals
- **AOS (Animate On Scroll)** - Smooth animations
- **Chart.js** - Data visualization
- **HTML5-QRCode** - QR Code scanning

## 📋 Persyaratan Sistem

### Server Requirements
- PHP >= 8.0
- MySQL >= 8.0 atau MariaDB >= 10.4
- Apache/Nginx Web Server
- GD Extension (untuk QR Code)
- PDO MySQL Extension
- JSON Extension
- Minimum 256MB RAM
- 1GB Storage space

### Browser Requirements
- Chrome 80+ (Recommended)
- Firefox 75+
- Safari 13+
- Edge 80+
- Camera access untuk QR scanning

## 🚀 Instalasi

### 1. Download dan Extract
```bash
# Clone repository
git clone https://github.com/yourusername/php-attendance-app.git
cd php-attendance-app

# Atau extract ZIP file
unzip php-attendance-app.zip
cd php-attendance-app
```

### 2. Setup Database
```sql
-- Buat database baru
CREATE DATABASE attendance_qr_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import schema
mysql -u username -p attendance_qr_system < database/schema.sql
```

### 3. Konfigurasi Database
Edit file `config/database.php`:
```php
private $host = 'localhost';
private $db_name = 'attendance_qr_system';
private $username = 'your_username';
private $password = 'your_password';
```

### 4. Setup Permissions
```bash
# Set permissions untuk upload directories
chmod 755 uploads/
chmod 755 uploads/qr_codes/
chmod 755 uploads/sick_letters/

# Pastikan web server bisa write
chown -R www-data:www-data uploads/
```

### 5. Virtual Host (Apache)
```apache
<VirtualHost *:80>
    ServerName attendance.local
    DocumentRoot /path/to/php-attendance-app
    
    <Directory "/path/to/php-attendance-app">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 6. .htaccess (jika diperlukan)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([^/]+)/?$ $1.php [L,QSA]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

## 🔧 Konfigurasi

### Pengaturan Waktu Absensi
Akses `admin/settings.php` untuk mengatur:
- Jam mulai absensi (default: 07:00)
- Jam berakhir absensi (default: 07:30)
- Toleransi keterlambatan (default: 10 menit)
- Masa berlaku QR Code (default: 24 jam)

### Email Notifications (Opsional)
Edit `config/mail.php`:
```php
$mail_config = [
    'host' => 'smtp.gmail.com',
    'username' => 'your-email@gmail.com',
    'password' => 'your-app-password',
    'port' => 587,
    'encryption' => 'tls'
];
```

## 👤 Akun Default

### Administrator
- **Email**: admin@school.com
- **Password**: password

### Guru (Demo)
- **Email**: teacher1@school.com
- **Password**: password

### Siswa (Demo)
- **Email**: student1@school.com
- **Password**: password

> ⚠️ **Penting**: Ganti password default setelah instalasi pertama!

## 📱 Penggunaan

### Untuk Siswa

#### 1. Login ke Sistem
1. Akses URL aplikasi
2. Masukkan email dan password
3. Klik "Masuk ke Sistem"

#### 2. Absensi dengan QR Code
1. Pilih menu "Absensi"
2. Klik "Mulai Scanner"
3. Izinkan akses kamera
4. Arahkan ke QR Code absensi
5. Tunggu konfirmasi berhasil

#### 3. Generate QR Code Personal
1. Pilih menu "QR Code Saya"
2. QR Code akan otomatis diload
3. Download/print untuk keperluan absensi

#### 4. Buat Surat Ijin Sakit
1. Pilih menu "Surat Sakit"
2. Isi form lengkap
3. Upload lampiran (opsional)
4. Submit untuk review guru/admin

### Untuk Guru

#### 1. Monitor Absensi Kelas
1. Dashboard menampilkan statistik real-time
2. Lihat daftar siswa yang hadir/tidak hadir
3. Export laporan per periode

#### 2. Review Surat Ijin Sakit
1. Menu "Surat Sakit" > "Review"
2. Baca detail surat dan lampiran
3. Approve atau reject dengan alasan

### Untuk Administrator

#### 1. Manajemen User
- Tambah/edit/hapus siswa, guru, admin
- Reset password user
- Aktifkan/nonaktifkan akun

#### 2. Pengaturan Sistem
- Atur jam operasional absensi
- Konfigurasi toleransi keterlambatan
- Setup notifikasi email

## 🔒 Keamanan

### Fitur Keamanan
- **Password Hashing**: Menggunakan bcrypt
- **Session Management**: Secure session handling
- **SQL Injection Protection**: Prepared statements
- **XSS Protection**: Input sanitization
- **CSRF Protection**: Token validation
- **File Upload Security**: Type validation dan sanitization

### Best Practices
1. Ganti password default
2. Update software secara berkala
3. Backup database rutin
4. Monitor log aktivitas
5. Gunakan HTTPS di production
6. Batasi akses file sensitive

## 📊 Database Schema

### Tabel Utama
- `users` - Data pengguna (admin, guru, siswa)
- `students` - Detail informasi siswa
- `teachers` - Detail informasi guru
- `classes` - Data kelas
- `attendance` - Record absensi harian
- `sick_letters` - Surat ijin sakit
- `qr_codes` - QR Code yang digunakan
- `settings` - Pengaturan sistem
- `activity_logs` - Log aktivitas sistem

### Relasi Database
```
users (1) -> (1) students -> (N) attendance
users (1) -> (1) teachers -> (1) classes
classes (1) -> (N) students
students (1) -> (N) sick_letters
users (1) -> (N) qr_codes
```

## 🚀 Deployment

### Shared Hosting
1. Upload semua files ke public_html
2. Import database via cPanel/phpMyAdmin
3. Update database config
4. Set folder permissions
5. Test semua fungsi

### VPS/Dedicated Server
```bash
# Clone repository
git clone https://github.com/yourusername/php-attendance-app.git

# Setup virtual host
sudo cp attendance.conf /etc/apache2/sites-available/
sudo a2ensite attendance.conf
sudo systemctl reload apache2

# Setup database
mysql -u root -p < database/schema.sql

# Set permissions
sudo chown -R www-data:www-data uploads/
sudo chmod 755 uploads/
```

### Docker (Opsional)
```dockerfile
FROM php:8.0-apache

RUN docker-php-ext-install pdo pdo_mysql gd

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html/uploads

EXPOSE 80
```

## 🧪 Testing

### Unit Testing (Manual)
1. Test login dengan berbagai role
2. Test QR Code generation dan scanning
3. Test absensi process
4. Test surat ijin sakit workflow
5. Test laporan dan export

### Browser Testing
- Chrome (Desktop & Mobile)
- Firefox (Desktop & Mobile)
- Safari (Desktop & Mobile)
- Edge (Desktop)

### Performance Testing
- Load testing dengan banyak user concurrent
- QR Code scanning performance
- Database query optimization

## 🐛 Troubleshooting

### Masalah Umum

#### 1. QR Code tidak terbaca
**Solusi:**
- Pastikan kamera berfungsi dengan baik
- Coba browser yang berbeda
- Gunakan input manual sebagai alternatif
- Periksa pencahayaan ruangan

#### 2. Database connection error
**Solusi:**
- Periksa credentials database
- Pastikan MySQL service running
- Check firewall settings
- Verify database exists

#### 3. Upload file gagal
**Solusi:**
```bash
# Check permissions
ls -la uploads/

# Fix permissions
chmod 755 uploads/
chown -R www-data:www-data uploads/

# Check PHP limits
php -i | grep -E "(upload_max_filesize|post_max_size|max_execution_time)"
```

#### 4. Session timeout
**Solusi:**
- Increase session timeout di php.ini
- Check session save path
- Clear browser cookies

### Debug Mode
Enable debug mode dengan menambahkan di `config/database.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 🔄 Maintenance

### Backup Rutin
```bash
#!/bin/bash
# Daily backup script
mysqldump -u username -p attendance_qr_system > backup/db_$(date +%Y%m%d).sql
tar -czf backup/files_$(date +%Y%m%d).tar.gz uploads/

# Keep only 30 days backup
find backup/ -name "*.sql" -mtime +30 -delete
find backup/ -name "*.tar.gz" -mtime +30 -delete
```

### Update System
1. Backup database dan files
2. Download versi terbaru
3. Update files (kecuali config)
4. Run database migrations (jika ada)
5. Test functionality

### Monitoring
- Monitor disk space (uploads folder)
- Check error logs regularly
- Monitor database performance
- Track user activity logs

## 📈 Performance Optimization

### Database
- Index frequently queried columns
- Optimize slow queries
- Regular database maintenance
- Use query caching

### Frontend
- Minify CSS/JS files
- Optimize images
- Use CDN for static assets
- Enable gzip compression

### Server
- Use opcache for PHP
- Tune Apache/Nginx settings
- Monitor memory usage
- Regular security updates

## 🤝 Contributing

### Development Setup
```bash
git clone https://github.com/yourusername/php-attendance-app.git
cd php-attendance-app

# Create development branch
git checkout -b feature/your-feature-name

# Make changes and commit
git add .
git commit -m "Add your feature description"
git push origin feature/your-feature-name
```

### Coding Standards
- PSR-4 autoloading
- PSR-12 coding style
- PHPDoc comments
- Meaningful commit messages

### Pull Request Process
1. Fork repository
2. Create feature branch
3. Add tests untuk fitur baru
4. Update documentation
5. Submit pull request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**Your Name**
- Website: [yourwebsite.com](https://yourwebsite.com)
- Email: your.email@example.com
- LinkedIn: [Your LinkedIn](https://linkedin.com/in/yourprofile)
- GitHub: [@yourusername](https://github.com/yourusername)

## 🙏 Acknowledgments

- [Tailwind CSS](https://tailwindcss.com/) for the amazing utility-first CSS framework
- [SweetAlert2](https://sweetalert2.github.io/) for beautiful alerts
- [Chart.js](https://www.chartjs.org/) for data visualization
- [HTML5-QRCode](https://github.com/mebjas/html5-qrcode) for QR code scanning
- [Font Awesome](https://fontawesome.com/) for icons
- [AOS](https://michalsnik.github.io/aos/) for animations

## 📞 Support

Jika Anda mengalami masalah atau memiliki pertanyaan:

1. **Documentation**: Baca dokumentasi lengkap
2. **Issues**: Buat issue di GitHub repository
3. **Email**: Kirim email ke support@yourcompany.com
4. **FAQ**: Cek FAQ section di website

---

## 🎯 Roadmap

### Version 2.0 (Coming Soon)
- [ ] Mobile app (Flutter)
- [ ] Advanced reporting dengan grafik
- [ ] Integration dengan sistem akademik
- [ ] Facial recognition attendance
- [ ] Multi-language support
- [ ] WhatsApp notification
- [ ] API documentation
- [ ] Automated testing

### Version 2.1
- [ ] Geolocation-based attendance
- [ ] Bulk operations
- [ ] Advanced permissions system
- [ ] Custom themes
- [ ] Plugin system

---

**Happy Coding! 🎉**

Dibuat dengan ❤️ untuk kemajuan pendidikan di Indonesia
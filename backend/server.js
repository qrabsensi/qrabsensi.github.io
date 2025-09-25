const express = require('express');
const mongoose = require('mongoose');
const cors = require('cors');
const path = require('path');

// Import routes
const authRoutes = require('./routes/auth');
const attendanceRoutes = require('./routes/attendance');
const permissionRoutes = require('./routes/permission');
const adminRoutes = require('./routes/admin');

const app = express();

// Middleware
app.use(cors());
app.use(express.json());
app.use('/uploads', express.static(path.join(__dirname, 'uploads')));

// MongoDB Connection
mongoose.connect('mongodb://localhost:27017/absensi_qr', {
  useNewUrlParser: true,
  useUnifiedTopology: true,
})
.then(() => {
  console.log('✅ MongoDB connected successfully');
  
  // Create default admin user
  const User = require('./models/User');
  const bcrypt = require('bcryptjs');
  
  User.findOne({ email: 'admin@admin.com' })
    .then(admin => {
      if (!admin) {
        const hashedPassword = bcrypt.hashSync('admin123', 10);
        const adminUser = new User({
          name: 'Administrator',
          email: 'admin@admin.com',
          password: hashedPassword,
          role: 'admin',
          employeeId: 'ADM001'
        });
        
        adminUser.save()
          .then(() => console.log('✅ Default admin user created: admin@admin.com / admin123'))
          .catch(err => console.log('❌ Error creating admin:', err));
      }
    });
})
.catch(err => {
  console.log('❌ MongoDB connection error:', err);
  process.exit(1);
});

// Routes
app.use('/api/auth', authRoutes);
app.use('/api/attendance', attendanceRoutes);
app.use('/api/permission', permissionRoutes);
app.use('/api/admin', adminRoutes);

// Health check
app.get('/api/health', (req, res) => {
  res.json({ 
    status: 'OK', 
    timestamp: new Date().toISOString(),
    uptime: process.uptime()
  });
});

const PORT = process.env.PORT || 5000;
app.listen(PORT, '0.0.0.0', () => {
  console.log(`🚀 Server running on http://0.0.0.0:${PORT}`);
  console.log(`📊 Health check: http://0.0.0.0:${PORT}/api/health`);
});

// Handle unhandled promise rejections
process.on('unhandledRejection', (err) => {
  console.log('❌ Unhandled Promise Rejection:', err);
  process.exit(1);
});


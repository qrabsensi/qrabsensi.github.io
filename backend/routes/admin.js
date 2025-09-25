const express = require("express");
const Attendance = require("../models/Attendance");
const Permission = require("../models/Permission");
const User = require("../models/User");
const { auth, adminAuth } = require("../middleware/auth");

const router = express.Router();

// Apply auth middleware to all admin routes
router.use(auth);
router.use(adminAuth);

// Get all users
router.get("/users", async (req, res) => {
  try {
    const { page = 1, limit = 10, search } = req.query;
    
    const query = {};
    if (search) {
      query.$or = [
        { name: { $regex: search, $options: "i" } },
        { email: { $regex: search, $options: "i" } },
        { employeeId: { $regex: search, $options: "i" } }
      ];
    }

    const users = await User.find(query)
      .select("-password")
      .sort({ createdAt: -1 })
      .limit(limit * 1)
      .skip((page - 1) * limit);

    const total = await User.countDocuments(query);

    res.json({
      users,
      totalPages: Math.ceil(total / limit),
      currentPage: page,
      total
    });
  } catch (error) {
    console.error(error);
    res.status(500).json({ msg: "Server error" });
  }
});

// Get all attendance records
router.get("/attendance", async (req, res) => {
  try {
    const { page = 1, limit = 10, date, status, userId } = req.query;
    
    const query = {};
    
    if (date) {
      const startDate = new Date(date);
      startDate.setHours(0, 0, 0, 0);
      const endDate = new Date(date);
      endDate.setHours(23, 59, 59, 999);
      query.date = { $gte: startDate, $lte: endDate };
    }
    
    if (status) query.status = status;
    if (userId) query.user = userId;

    const attendances = await Attendance.find(query)
      .populate("user", "name employeeId department")
      .sort({ date: -1 })
      .limit(limit * 1)
      .skip((page - 1) * limit);

    const total = await Attendance.countDocuments(query);

    res.json({
      attendances,
      totalPages: Math.ceil(total / limit),
      currentPage: page,
      total
    });
  } catch (error) {
    console.error(error);
    res.status(500).json({ msg: "Server error" });
  }
});

// Get all permission requests
router.get("/permissions", async (req, res) => {
  try {
    const { page = 1, limit = 10, status, type, userId } = req.query;
    
    const query = {};
    if (status) query.status = status;
    if (type) query.type = type;
    if (userId) query.user = userId;

    const permissions = await Permission.find(query)
      .populate("user", "name employeeId department")
      .populate("approvedBy", "name")
      .sort({ createdAt: -1 })
      .limit(limit * 1)
      .skip((page - 1) * limit);

    const total = await Permission.countDocuments(query);

    res.json({
      permissions,
      totalPages: Math.ceil(total / limit),
      currentPage: page,
      total
    });
  } catch (error) {
    console.error(error);
    res.status(500).json({ msg: "Server error" });
  }
});

// Approve/Reject permission request
router.put("/permissions/:id", async (req, res) => {
  try {
    const { status, approvalNotes } = req.body;

    if (!["approved", "rejected"].includes(status)) {
      return res.status(400).json({ msg: "Invalid status" });
    }

    const permission = await Permission.findById(req.params.id);
    
    if (!permission) {
      return res.status(404).json({ msg: "Permission request not found" });
    }

    if (permission.status !== "pending") {
      return res.status(400).json({ msg: "Permission request already processed" });
    }

    permission.status = status;
    permission.approvedBy = req.user.id;
    permission.approvalDate = new Date();
    if (approvalNotes) permission.approvalNotes = approvalNotes;

    await permission.save();

    res.json({ 
      msg: `Permission request ${status} successfully`,
      permission
    });
  } catch (error) {
    console.error(error);
    res.status(500).json({ msg: "Server error" });
  }
});

// Get dashboard statistics
router.get("/stats", async (req, res) => {
  try {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);

    const [
      totalUsers,
      todayAttendance,
      pendingPermissions,
      totalPermissions
    ] = await Promise.all([
      User.countDocuments({ role: "user" }),
      Attendance.countDocuments({
        date: { $gte: today, $lt: tomorrow }
      }),
      Permission.countDocuments({ status: "pending" }),
      Permission.countDocuments()
    ]);

    // Get attendance by status today
    const attendanceStats = await Attendance.aggregate([
      {
        $match: {
          date: { $gte: today, $lt: tomorrow }
        }
      },
      {
        $group: {
          _id: "$status",
          count: { $sum: 1 }
        }
      }
    ]);

    res.json({
      totalUsers,
      todayAttendance,
      pendingPermissions,
      totalPermissions,
      attendanceStats: attendanceStats.reduce((acc, item) => {
        acc[item._id] = item.count;
        return acc;
      }, {})
    });
  } catch (error) {
    console.error(error);
    res.status(500).json({ msg: "Server error" });
  }
});

// Toggle user active status
router.put("/users/:id/toggle", async (req, res) => {
  try {
    const user = await User.findById(req.params.id);
    
    if (!user) {
      return res.status(404).json({ msg: "User not found" });
    }

    if (user.role === "admin") {
      return res.status(400).json({ msg: "Cannot deactivate admin user" });
    }

    user.isActive = !user.isActive;
    await user.save();

    res.json({ 
      msg: `User ${user.isActive ? "activated" : "deactivated"} successfully`,
      user: {
        id: user.id,
        name: user.name,
        isActive: user.isActive
      }
    });
  } catch (error) {
    console.error(error);
    res.status(500).json({ msg: "Server error" });
  }
});

module.exports = router;


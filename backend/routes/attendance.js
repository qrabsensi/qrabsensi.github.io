const express = require("express");
const Attendance = require("../models/Attendance");
const User = require("../models/User");
const { auth } = require("../middleware/auth");

const router = express.Router();

// Clock in with QR scan
router.post("/clock-in", auth, async (req, res) => {
  try {
    const { qrCodeData, location } = req.body;

    // Parse QR code data
    let parsedData;
    try {
      parsedData = JSON.parse(qrCodeData);
    } catch {
      return res.status(400).json({ msg: "Invalid QR code format" });
    }

    // Validate QR code
    if (parsedData.userId !== req.user.id) {
      return res.status(400).json({ msg: "QR code tidak valid untuk user ini" });
    }

    // Check if already clocked in today
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);

    const existingAttendance = await Attendance.findOne({
      user: req.user.id,
      date: { $gte: today, $lt: tomorrow }
    });

    if (existingAttendance) {
      return res.status(400).json({ msg: "Sudah absen hari ini" });
    }

    // Determine status (late if after 09:00)
    const clockInTime = new Date();
    const nineAM = new Date();
    nineAM.setHours(9, 0, 0, 0);
    
    const status = clockInTime > nineAM ? "late" : "present";

    // Create attendance record
    const attendance = new Attendance({
      user: req.user.id,
      date: today,
      clockIn: clockInTime,
      status,
      qrCodeData,
      location
    });

    await attendance.save();

    res.json({ 
      msg: "Clock in berhasil",
      attendance: {
        id: attendance.id,
        clockIn: attendance.clockIn,
        status: attendance.status
      }
    });
  } catch (error) {
    console.error(error);
    res.status(500).json({ msg: "Server error" });
  }
});

// Clock out
router.post("/clock-out", auth, async (req, res) => {
  try {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);

    const attendance = await Attendance.findOne({
      user: req.user.id,
      date: { $gte: today, $lt: tomorrow }
    });

    if (!attendance) {
      return res.status(400).json({ msg: "Belum clock in hari ini" });
    }

    if (attendance.clockOut) {
      return res.status(400).json({ msg: "Sudah clock out hari ini" });
    }

    attendance.clockOut = new Date();
    await attendance.save();

    res.json({ 
      msg: "Clock out berhasil",
      attendance: {
        id: attendance.id,
        clockIn: attendance.clockIn,
        clockOut: attendance.clockOut,
        status: attendance.status
      }
    });
  } catch (error) {
    console.error(error);
    res.status(500).json({ msg: "Server error" });
  }
});

// Get user attendance history
router.get("/history", auth, async (req, res) => {
  try {
    const { page = 1, limit = 10, month, year } = req.query;
    
    const query = { user: req.user.id };
    
    // Filter by month/year if provided
    if (month && year) {
      const startDate = new Date(year, month - 1, 1);
      const endDate = new Date(year, month, 0);
      query.date = { $gte: startDate, $lte: endDate };
    }

    const attendances = await Attendance.find(query)
      .sort({ date: -1 })
      .limit(limit * 1)
      .skip((page - 1) * limit)
      .populate("user", "name employeeId");

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

// Get today's attendance status
router.get("/today", auth, async (req, res) => {
  try {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);

    const attendance = await Attendance.findOne({
      user: req.user.id,
      date: { $gte: today, $lt: tomorrow }
    });

    res.json({ attendance });
  } catch (error) {
    console.error(error);
    res.status(500).json({ msg: "Server error" });
  }
});

module.exports = router;


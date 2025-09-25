const express = require("express");
const multer = require("multer");
const path = require("path");
const Permission = require("../models/Permission");
const { auth } = require("../middleware/auth");

const router = express.Router();

// Configure multer for file uploads
const storage = multer.diskStorage({
  destination: function (req, file, cb) {
    cb(null, "uploads/");
  },
  filename: function (req, file, cb) {
    const uniqueSuffix = Date.now() + "-" + Math.round(Math.random() * 1E9);
    cb(null, file.fieldname + "-" + uniqueSuffix + path.extname(file.originalname));
  }
});

const upload = multer({ 
  storage: storage,
  limits: {
    fileSize: 5 * 1024 * 1024 // 5MB limit
  },
  fileFilter: function (req, file, cb) {
    const allowedTypes = /jpeg|jpg|png|pdf|doc|docx/;
    const extname = allowedTypes.test(path.extname(file.originalname).toLowerCase());
    const mimetype = allowedTypes.test(file.mimetype);
    
    if (mimetype && extname) {
      return cb(null, true);
    } else {
      cb(new Error("Only images and documents are allowed"));
    }
  }
});

// Create permission request
router.post("/", auth, upload.single("document"), async (req, res) => {
  try {
    const { type, startDate, endDate, reason } = req.body;

    // Validate dates
    const start = new Date(startDate);
    const end = new Date(endDate);
    
    if (start > end) {
      return res.status(400).json({ msg: "Start date cannot be after end date" });
    }

    if (start < new Date()) {
      return res.status(400).json({ msg: "Start date cannot be in the past" });
    }

    // Check for overlapping permissions
    const overlapping = await Permission.findOne({
      user: req.user.id,
      status: { $in: ["pending", "approved"] },
      $or: [
        { startDate: { $lte: end }, endDate: { $gte: start } }
      ]
    });

    if (overlapping) {
      return res.status(400).json({ msg: "You already have a permission request for this period" });
    }

    const permission = new Permission({
      user: req.user.id,
      type,
      startDate: start,
      endDate: end,
      reason
    });

    // Add document if uploaded
    if (req.file) {
      permission.document = {
        filename: req.file.filename,
        originalName: req.file.originalname,
        path: req.file.path,
        size: req.file.size
      };
    }

    await permission.save();

    res.json({ 
      msg: "Permission request submitted successfully",
      permission: {
        id: permission.id,
        type: permission.type,
        startDate: permission.startDate,
        endDate: permission.endDate,
        status: permission.status
      }
    });
  } catch (error) {
    console.error(error);
    res.status(500).json({ msg: "Server error" });
  }
});

// Get user's permission requests
router.get("/", auth, async (req, res) => {
  try {
    const { page = 1, limit = 10, status } = req.query;
    
    const query = { user: req.user.id };
    if (status) {
      query.status = status;
    }

    const permissions = await Permission.find(query)
      .sort({ createdAt: -1 })
      .limit(limit * 1)
      .skip((page - 1) * limit)
      .populate("user", "name employeeId")
      .populate("approvedBy", "name");

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

// Get single permission request
router.get("/:id", auth, async (req, res) => {
  try {
    const permission = await Permission.findOne({
      _id: req.params.id,
      user: req.user.id
    }).populate("user", "name employeeId")
      .populate("approvedBy", "name");

    if (!permission) {
      return res.status(404).json({ msg: "Permission request not found" });
    }

    res.json(permission);
  } catch (error) {
    console.error(error);
    res.status(500).json({ msg: "Server error" });
  }
});

// Update permission request (only if pending)
router.put("/:id", auth, upload.single("document"), async (req, res) => {
  try {
    const permission = await Permission.findOne({
      _id: req.params.id,
      user: req.user.id
    });

    if (!permission) {
      return res.status(404).json({ msg: "Permission request not found" });
    }

    if (permission.status !== "pending") {
      return res.status(400).json({ msg: "Cannot update non-pending permission request" });
    }

    const { type, startDate, endDate, reason } = req.body;

    if (type) permission.type = type;
    if (startDate) permission.startDate = new Date(startDate);
    if (endDate) permission.endDate = new Date(endDate);
    if (reason) permission.reason = reason;

    // Update document if uploaded
    if (req.file) {
      permission.document = {
        filename: req.file.filename,
        originalName: req.file.originalname,
        path: req.file.path,
        size: req.file.size
      };
    }

    await permission.save();

    res.json({ msg: "Permission request updated successfully" });
  } catch (error) {
    console.error(error);
    res.status(500).json({ msg: "Server error" });
  }
});

// Delete permission request (only if pending)
router.delete("/:id", auth, async (req, res) => {
  try {
    const permission = await Permission.findOne({
      _id: req.params.id,
      user: req.user.id
    });

    if (!permission) {
      return res.status(404).json({ msg: "Permission request not found" });
    }

    if (permission.status !== "pending") {
      return res.status(400).json({ msg: "Cannot delete non-pending permission request" });
    }

    await Permission.findByIdAndDelete(req.params.id);

    res.json({ msg: "Permission request deleted successfully" });
  } catch (error) {
    console.error(error);
    res.status(500).json({ msg: "Server error" });
  }
});

module.exports = router;


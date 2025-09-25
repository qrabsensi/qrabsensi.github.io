const mongoose = require("mongoose");

const AttendanceSchema = new mongoose.Schema({
  user: {
    type: mongoose.Schema.Types.ObjectId,
    ref: "User",
    required: true
  },
  date: {
    type: Date,
    default: Date.now
  },
  clockIn: {
    type: Date,
    default: Date.now
  },
  clockOut: {
    type: Date
  },
  status: {
    type: String,
    enum: ["present", "absent", "late"],
    default: "present"
  },
  qrCodeData: {
    type: String,
    required: true
  },
  location: {
    latitude: Number,
    longitude: Number
  },
  notes: String
}, {
  timestamps: true
});

// Index untuk mencegah duplikasi absensi pada hari yang sama
AttendanceSchema.index({ user: 1, date: 1 }, { 
  unique: true,
  partialFilterExpression: { date: { $type: "date" } }
});

module.exports = mongoose.model("Attendance", AttendanceSchema);


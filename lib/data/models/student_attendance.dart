class StudentAttendanceModel {
  final int studentId;
  final String rollNo;
  final String name;
  final String admissionNo;
  final String? gender;
  String attendanceStatus; // "Present", "Absent", "Late", "Half Day", "Leave", "Holiday"
  String remarks;
  final bool isHoliday;

  StudentAttendanceModel({
    required this.studentId,
    required this.rollNo,
    required this.name,
    required this.admissionNo,
    this.gender,
    required this.attendanceStatus,
    required this.remarks,
    required this.isHoliday,
  });

  factory StudentAttendanceModel.fromJson(Map<String, dynamic> json) {
    return StudentAttendanceModel(
      studentId: json['student_id'] ?? 0,
      rollNo: json['roll_no']?.toString() ?? '',
      name: json['name'] ?? '',
      admissionNo: json['admission_no'] ?? '',
      gender: json['gender'],
      attendanceStatus: json['attendance_status'] ?? 'Present',
      remarks: json['remarks'] ?? '',
      isHoliday: json['is_holiday'] == true || json['is_holiday'] == 1,
    );
  }

  Map<String, dynamic> toSubmitJson() {
    return {
      'student_id': studentId,
      'status': attendanceStatus,
      'remarks': remarks,
    };
  }
}

class AttendanceHistoryRecord {
  final int id;
  final int studentId;
  final String attendanceDate; // YYYY-MM-DD
  final String status;
  final String? remarks;

  AttendanceHistoryRecord({
    required this.id,
    required this.studentId,
    required this.attendanceDate,
    required this.status,
    this.remarks,
  });

  factory AttendanceHistoryRecord.fromJson(Map<String, dynamic> json) {
    return AttendanceHistoryRecord(
      id: json['id'] ?? 0,
      studentId: json['student_id'] ?? 0,
      attendanceDate: json['attendance_date'] ?? '',
      status: json['status'] ?? 'Present',
      remarks: json['remarks'],
    );
  }
}

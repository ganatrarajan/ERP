class ExamScheduleModel {
  final int id;
  final double maxMarks;
  final double minMarks;

  ExamScheduleModel({
    required this.id,
    required this.maxMarks,
    required this.minMarks,
  });

  factory ExamScheduleModel.fromJson(Map<String, dynamic> json) {
    return ExamScheduleModel(
      id: json['id'] ?? 0,
      maxMarks: double.tryParse(json['max_marks']?.toString() ?? '100') ?? 100.0,
      minMarks: double.tryParse(json['min_marks']?.toString() ?? '35') ?? 35.0,
    );
  }
}

class GradeScaleModel {
  final int id;
  final String gradeName;
  final double minPercentage;

  GradeScaleModel({
    required this.id,
    required this.gradeName,
    required this.minPercentage,
  });

  factory GradeScaleModel.fromJson(Map<String, dynamic> json) {
    return GradeScaleModel(
      id: json['id'] ?? 0,
      gradeName: json['grade_name'] ?? json['name'] ?? '',
      minPercentage: double.tryParse(json['min_percentage']?.toString() ?? '0') ?? 0.0,
    );
  }
}

class StudentExamMarkModel {
  final int studentId;
  final String rollNo;
  final String name;
  final String admissionNo;
  double? marksObtained;
  bool isAbsent;
  int? gradeId;
  String remarks;

  StudentExamMarkModel({
    required this.studentId,
    required this.rollNo,
    required this.name,
    required this.admissionNo,
    this.marksObtained,
    required this.isAbsent,
    this.gradeId,
    required this.remarks,
  });

  factory StudentExamMarkModel.fromJson(Map<String, dynamic> json) {
    return StudentExamMarkModel(
      studentId: json['student_id'] ?? 0,
      rollNo: json['roll_no']?.toString() ?? '',
      name: json['name'] ?? '',
      admissionNo: json['admission_no'] ?? '',
      marksObtained: json['marks_obtained'] != null ? double.tryParse(json['marks_obtained'].toString()) : null,
      isAbsent: json['is_absent'] == true || json['is_absent'] == 1 || json['is_absent'] == '1',
      gradeId: json['grade_id'],
      remarks: json['remarks'] ?? '',
    );
  }

  Map<String, dynamic> toSubmitJson() {
    return {
      'student_id': studentId,
      'marks_obtained': isAbsent ? null : marksObtained,
      'is_absent': isAbsent,
      'grade_id': gradeId,
      'remarks': remarks,
    };
  }
}

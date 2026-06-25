import 'student_model.dart';

class DashboardData {
  final StudentModel student;
  final double attendancePercentage;
  final int pendingHomeworkCount;
  final int latestNoticeCount;
  final double pendingFeeAmount;
  final LatestExamResult? latestExamResult;
  final String mobileAcademicYear;

  DashboardData({
    required this.student,
    required this.attendancePercentage,
    required this.pendingHomeworkCount,
    required this.latestNoticeCount,
    required this.pendingFeeAmount,
    this.latestExamResult,
    required this.mobileAcademicYear,
  });

  factory DashboardData.fromJson(Map<String, dynamic> json) {
    return DashboardData(
      student: StudentModel.fromJson(json['student'] ?? {}),
      attendancePercentage: (json['attendance_percentage'] as num?)?.toDouble() ?? 0.0,
      pendingHomeworkCount: json['pending_homework_count'] ?? 0,
      latestNoticeCount: json['latest_notice_count'] ?? 0,
      pendingFeeAmount: (json['pending_fee_amount'] as num?)?.toDouble() ?? 0.0,
      latestExamResult: json['latest_exam_result'] != null
          ? LatestExamResult.fromJson(json['latest_exam_result'])
          : null,
      mobileAcademicYear: json['mobile_academic_year'] ?? 'N/A',
    );
  }
}

class LatestExamResult {
  final int examId;
  final String examName;
  final double totalMaxMarks;
  final double totalObtainedMarks;
  final double percentage;
  final String grade;
  final String result;
  final int? rank;

  LatestExamResult({
    required this.examId,
    required this.examName,
    required this.totalMaxMarks,
    required this.totalObtainedMarks,
    required this.percentage,
    required this.grade,
    required this.result,
    this.rank,
  });

  factory LatestExamResult.fromJson(Map<String, dynamic> json) {
    return LatestExamResult(
      examId: json['exam_id'] ?? 0,
      examName: json['exam_name'] ?? '',
      totalMaxMarks: (json['total_max_marks'] as num?)?.toDouble() ?? 0.0,
      totalObtainedMarks: (json['total_obtained_marks'] as num?)?.toDouble() ?? 0.0,
      percentage: (json['percentage'] as num?)?.toDouble() ?? 0.0,
      grade: json['grade'] ?? '',
      result: json['result'] ?? '',
      rank: json['rank'],
    );
  }
}

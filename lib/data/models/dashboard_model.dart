import 'package:intl/intl.dart';
import 'student_model.dart';

class DashboardData {
  final StudentModel student;
  final double attendancePercentage;
  final int pendingHomeworkCount;
  final int latestNoticeCount;
  final double pendingFeeAmount;
  final LatestExamResult? latestExamResult;
  final String mobileAcademicYear;
  final bool onlinePaymentEnabled;
  final String? pendingFeeDueDate;
  final int pendingInstallmentsCount;

  DashboardData({
    required this.student,
    required this.attendancePercentage,
    required this.pendingHomeworkCount,
    required this.latestNoticeCount,
    required this.pendingFeeAmount,
    this.latestExamResult,
    required this.mobileAcademicYear,
    required this.onlinePaymentEnabled,
    this.pendingFeeDueDate,
    required this.pendingInstallmentsCount,
  });

  /// Checks if the pending fee due date is within the last 5 days before due date (or overdue).
  bool get isDueDateWithin5DaysOrOverdue {
    if (pendingFeeDueDate == null || pendingFeeDueDate!.trim().isEmpty) {
      return true;
    }

    final dateStr = pendingFeeDueDate!.trim();
    DateTime? parsedDate = DateTime.tryParse(dateStr);

    if (parsedDate == null) {
      final formats = [
        'yyyy-MM-dd',
        'dd-MM-yyyy',
        'dd/MM/yyyy',
        'dd MMM yyyy',
        'yyyy/MM/dd',
        'd MMM yyyy',
      ];
      for (final fmt in formats) {
        try {
          parsedDate = DateFormat(fmt).parse(dateStr);
          break;
        } catch (_) {}
      }
    }

    if (parsedDate == null) {
      return true;
    }

    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final dueDay = DateTime(parsedDate.year, parsedDate.month, parsedDate.day);

    final daysRemaining = dueDay.difference(today).inDays;
    // Show on dashboard ONLY if due in 5 days or less (daysRemaining <= 5) or if overdue (daysRemaining < 0)
    return daysRemaining <= 5;
  }

  factory DashboardData.fromJson(Map<String, dynamic> json) {
    final rawOnline = json['online_payment_enabled'];
    final isOnlineEnabled = rawOnline == true || 
                            rawOnline == 1 || 
                            rawOnline == '1' || 
                            rawOnline == 'true' || 
                            rawOnline == null; // Default to true if not specified

    final pendingAmt = (json['pending_fee_amount'] as num?)?.toDouble() ?? 
                       (json['pending_fees'] as num?)?.toDouble() ?? 
                       (json['pending_amount'] as num?)?.toDouble() ?? 
                       0.0;

    return DashboardData(
      student: StudentModel.fromJson(json['student'] ?? {}),
      attendancePercentage: (json['attendance_percentage'] as num?)?.toDouble() ?? 0.0,
      pendingHomeworkCount: json['pending_homework_count'] ?? 0,
      latestNoticeCount: json['latest_notice_count'] ?? 0,
      pendingFeeAmount: pendingAmt,
      latestExamResult: json['latest_exam_result'] != null
          ? LatestExamResult.fromJson(json['latest_exam_result'])
          : null,
      mobileAcademicYear: json['mobile_academic_year'] ?? 'N/A',
      onlinePaymentEnabled: isOnlineEnabled,
      pendingFeeDueDate: json['pending_fee_due_date'],
      pendingInstallmentsCount: json['pending_installments_count'] ?? 0,
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

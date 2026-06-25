class ExamResultModel {
  final int examId;
  final String examName;
  final String startDate;
  final String endDate;
  final double totalMaxMarks;
  final double totalObtainedMarks;
  final double percentage;
  final String grade;
  final String result; // Pass / Fail
  final int? rank;
  final List<SubjectResultDetail> subjectDetails;

  ExamResultModel({
    required this.examId,
    required this.examName,
    required this.startDate,
    required this.endDate,
    required this.totalMaxMarks,
    required this.totalObtainedMarks,
    required this.percentage,
    required this.grade,
    required this.result,
    this.rank,
    required this.subjectDetails,
  });

  factory ExamResultModel.fromJson(Map<String, dynamic> json) {
    final list = json['subject_details'] as List? ?? [];
    return ExamResultModel(
      examId: int.tryParse(json['exam_id']?.toString() ?? '0') ?? 0,
      examName: json['exam_name']?.toString() ?? '',
      startDate: json['start_date']?.toString() ?? '',
      endDate: json['end_date']?.toString() ?? '',
      totalMaxMarks: double.tryParse(json['total_max_marks']?.toString() ?? '0') ?? 0.0,
      totalObtainedMarks: double.tryParse(json['total_obtained_marks']?.toString() ?? '0') ?? 0.0,
      percentage: double.tryParse(json['percentage']?.toString() ?? '0') ?? 0.0,
      grade: json['grade']?.toString() ?? '',
      result: json['result']?.toString() ?? '',
      rank: json['rank'] != null ? int.tryParse(json['rank'].toString()) : null,
      subjectDetails: list.map((e) => SubjectResultDetail.fromJson(e as Map<String, dynamic>)).toList(),
    );
  }
}

class SubjectResultDetail {
  final int subjectId;
  final String subjectName;
  final String subjectCode;
  final String evaluationType;
  final String visibility;
  final double maxMarks;
  final double obtainedMarks;
  final double passingMarks;
  final String grade;
  final bool isPass;
  final String remarks;

  SubjectResultDetail({
    required this.subjectId,
    required this.subjectName,
    required this.subjectCode,
    required this.evaluationType,
    required this.visibility,
    required this.maxMarks,
    required this.obtainedMarks,
    required this.passingMarks,
    required this.grade,
    required this.isPass,
    required this.remarks,
  });

  factory SubjectResultDetail.fromJson(Map<String, dynamic> json) {
    bool parseBool(dynamic value) {
      if (value is bool) return value;
      if (value is int) return value == 1;
      if (value is String) return value.toLowerCase() == 'true' || value == '1';
      return false;
    }

    return SubjectResultDetail(
      subjectId: int.tryParse(json['subject_id']?.toString() ?? '0') ?? 0,
      subjectName: json['subject_name']?.toString() ?? '',
      subjectCode: json['subject_code']?.toString() ?? '',
      evaluationType: json['evaluation_type']?.toString() ?? 'marks',
      visibility: json['visibility']?.toString() ?? 'included_in_result',
      maxMarks: double.tryParse(json['max_marks']?.toString() ?? '0') ?? 0.0,
      obtainedMarks: double.tryParse(json['obtained_marks']?.toString() ?? '0') ?? 0.0,
      passingMarks: double.tryParse(json['passing_marks']?.toString() ?? '0') ?? 0.0,
      grade: json['grade']?.toString() ?? '',
      isPass: parseBool(json['is_pass'] ?? true),
      remarks: json['remarks']?.toString() ?? '',
    );
  }
}

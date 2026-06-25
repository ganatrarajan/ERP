import '../../core/constants/api_endpoints.dart';
import '../../core/network/dio_client.dart';
import '../models/student_model.dart';
import '../models/dashboard_model.dart';
import '../models/attendance_model.dart';
import '../models/homework_model.dart';
import '../models/notice_model.dart';
import '../models/exam_result_model.dart';
import '../models/report_card_model.dart';
import '../models/fee_model.dart';
import '../models/receipt_model.dart';

class StudentRepository {
  final DioClient _dioClient = DioClient();

  Future<StudentModel> getProfile() async {
    final response = await _dioClient.get(ApiEndpoints.profile);
    final data = response.data;
    if (data is Map && data['success'] == true) {
      return StudentModel.fromJson(Map<String, dynamic>.from(data['student']));
    }
    throw Exception(data['message'] ?? 'Failed to load profile');
  }

  Future<DashboardData> getDashboardData() async {
    final response = await _dioClient.get(ApiEndpoints.dashboard);
    final data = response.data;
    if (data is Map && data['success'] == true) {
      return DashboardData.fromJson(Map<String, dynamic>.from(data));
    }
    throw Exception(data['message'] ?? 'Failed to load dashboard data');
  }

  Future<AttendanceResponse> getAttendance({String? month, int? academicYearId}) async {
    final queryParams = <String, dynamic>{};
    if (month != null) queryParams['month'] = month;
    if (academicYearId != null) queryParams['academic_year_id'] = academicYearId;

    final response = await _dioClient.get(ApiEndpoints.attendance, queryParameters: queryParams);
    final data = response.data;
    if (data is Map && data['success'] == true) {
      return AttendanceResponse.fromJson(Map<String, dynamic>.from(data));
    }
    throw Exception(data['message'] ?? 'Failed to load attendance');
  }

  Future<List<HomeworkModel>> getHomework({int? subjectId, String? date}) async {
    final queryParams = <String, dynamic>{};
    if (subjectId != null) queryParams['subject_id'] = subjectId;
    if (date != null) queryParams['date'] = date;

    final response = await _dioClient.get(ApiEndpoints.homework, queryParameters: queryParams);
    final data = response.data;
    if (data is Map && data['success'] == true) {
      final list = data['homeworks'] as List? ?? [];
      return list.map((e) => HomeworkModel.fromJson(e)).toList();
    }
    throw Exception(data['message'] ?? 'Failed to load homework feed');
  }

  Future<List<NoticeModel>> getNotices({String? type}) async {
    final queryParams = <String, dynamic>{};
    if (type != null) queryParams['type'] = type;

    final response = await _dioClient.get(ApiEndpoints.notices, queryParameters: queryParams);
    final data = response.data;
    if (data is Map && data['success'] == true) {
      final list = data['notices'] as List? ?? [];
      return list.map((e) => NoticeModel.fromJson(e)).toList();
    }
    throw Exception(data['message'] ?? 'Failed to load notices feed');
  }

  Future<List<ExamResultModel>> getExamResults() async {
    final response = await _dioClient.get(ApiEndpoints.results);
    final data = response.data;
    if (data is Map && data['success'] == true) {
      final list = data['results'] as List? ?? [];
      return list.map((e) => ExamResultModel.fromJson(e)).toList();
    }
    throw Exception(data['message'] ?? 'Failed to load exam results');
  }

  Future<List<ReportCardModel>> getReportCards() async {
    final response = await _dioClient.get(ApiEndpoints.reportCards);
    final data = response.data;
    if (data is Map && data['success'] == true) {
      final list = data['report_cards'] as List? ?? [];
      return list.map((e) => ReportCardModel.fromJson(e)).toList();
    }
    throw Exception(data['message'] ?? 'Failed to load report cards');
  }

  Future<String> getReportCardPdfUrl(int examId) async {
    final response = await _dioClient.get(ApiEndpoints.reportCardPdfUrl(examId));
    final data = response.data;
    if (data is Map && data['success'] == true) {
      return data['pdf_url'] ?? '';
    }
    throw Exception(data['message'] ?? 'Failed to retrieve report card download link');
  }

  Future<FeeResponse> getFees() async {
    final response = await _dioClient.get(ApiEndpoints.fees);
    final data = response.data;
    if (data is Map && data['success'] == true) {
      return FeeResponse.fromJson(Map<String, dynamic>.from(data));
    }
    throw Exception(data['message'] ?? 'Failed to load fees summary');
  }

  Future<List<ReceiptModel>> getReceipts() async {
    final response = await _dioClient.get(ApiEndpoints.receipts);
    final data = response.data;
    if (data is Map && data['success'] == true) {
      final list = data['receipts'] as List? ?? [];
      return list.map((e) => ReceiptModel.fromJson(e)).toList();
    }
    throw Exception(data['message'] ?? 'Failed to load payment receipts');
  }

  Future<String> getReceiptPdfUrl(int receiptId) async {
    final response = await _dioClient.get(ApiEndpoints.receiptPdfUrl(receiptId));
    final data = response.data;
    if (data is Map && data['success'] == true) {
      return data['pdf_url'] ?? '';
    }
    throw Exception(data['message'] ?? 'Failed to retrieve payment receipt download link');
  }
}

import '../../core/constants/api_endpoints.dart';
import '../../core/network/dio_client.dart';
import '../models/assignment.dart';
import '../models/student_attendance.dart';

class AttendanceRepository {
  final DioClient _dioClient = DioClient();

  Future<List<AssignmentModel>> getClasses() async {
    final response = await _dioClient.get(ApiEndpoints.attendanceClasses);
    final data = response.data;
    if (data is Map<String, dynamic> && data['success'] == true) {
      final list = data['classes'] as List? ?? [];
      return list.map((e) => AssignmentModel.fromJson(e)).toList();
    }
    throw Exception('Failed to load classes for attendance');
  }

  Future<List<StudentAttendanceModel>> getStudents({
    required int classId,
    required int sectionId,
    required String date,
  }) async {
    final response = await _dioClient.get(
      ApiEndpoints.attendanceStudents,
      queryParameters: {
        'class_id': classId,
        'section_id': sectionId,
        'attendance_date': date,
      },
    );
    final data = response.data;
    if (data is Map<String, dynamic> && data['success'] == true) {
      final list = data['students'] as List? ?? [];
      return list.map((e) => StudentAttendanceModel.fromJson(e)).toList();
    }
    throw Exception('Failed to load students for attendance');
  }

  Future<bool> saveAttendance({
    required int classId,
    required int sectionId,
    required String date,
    required List<StudentAttendanceModel> students,
  }) async {
    final payload = {
      'class_id': classId,
      'section_id': sectionId,
      'attendance_date': date,
      'students': students.map((e) => e.toSubmitJson()).toList(),
    };

    final response = await _dioClient.post(
      ApiEndpoints.submitAttendance,
      data: payload,
    );
    final data = response.data;
    return data is Map && data['success'] == true;
  }

  Future<List<AttendanceHistoryRecord>> getHistory({
    required int classId,
    required int sectionId,
    required String startDate,
    required String endDate,
  }) async {
    final response = await _dioClient.get(
      ApiEndpoints.attendanceHistory,
      queryParameters: {
        'class_id': classId,
        'section_id': sectionId,
        'start_date': startDate,
        'end_date': endDate,
      },
    );
    final data = response.data;
    if (data is Map<String, dynamic> && data['success'] == true) {
      final list = data['history'] as List? ?? [];
      return list.map((e) => AttendanceHistoryRecord.fromJson(e)).toList();
    }
    throw Exception('Failed to load attendance history');
  }

  Future<Map<String, dynamic>> getMonthlyGrid({
    required int classId,
    required int sectionId,
    required String month,
  }) async {
    final response = await _dioClient.get(
      ApiEndpoints.monthlyAttendance,
      queryParameters: {
        'class_id': classId,
        'section_id': sectionId,
        'month': month,
      },
    );
    final data = response.data;
    if (data is Map<String, dynamic> && data['success'] == true) {
      return data;
    }
    throw Exception('Failed to load monthly attendance grid');
  }
}

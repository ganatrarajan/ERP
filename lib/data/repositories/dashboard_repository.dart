import '../../core/constants/api_endpoints.dart';
import '../../core/network/dio_client.dart';
import '../models/assignment.dart';
import '../models/notice.dart';

class DashboardStats {
  final int assignedClassesCount;
  final int todayAttendanceMarkedSections;
  final int pendingHomeworkCount;
  final List<NoticeModel> recentNotices;

  DashboardStats({
    required this.assignedClassesCount,
    required this.todayAttendanceMarkedSections,
    required this.pendingHomeworkCount,
    required this.recentNotices,
  });

  factory DashboardStats.fromJson(Map<String, dynamic> json) {
    final list = json['recent_notices'] as List? ?? [];
    return DashboardStats(
      assignedClassesCount: json['assigned_classes_count'] ?? 0,
      todayAttendanceMarkedSections: json['today_attendance_marked_sections'] ?? 0,
      pendingHomeworkCount: json['pending_homework_count'] ?? 0,
      recentNotices: list.map((e) => NoticeModel.fromJson(e)).toList(),
    );
  }
}

class DashboardRepository {
  final DioClient _dioClient = DioClient();

  Future<DashboardStats> getStats() async {
    final response = await _dioClient.get(ApiEndpoints.dashboard);
    final data = response.data;
    if (data is Map<String, dynamic> && data['success'] == true) {
      return DashboardStats.fromJson(data);
    }
    throw Exception('Failed to load dashboard summary');
  }

  Future<List<AssignmentModel>> getAssignments() async {
    final response = await _dioClient.get(ApiEndpoints.assignments);
    final data = response.data;
    if (data is Map<String, dynamic> && data['success'] == true) {
      final list = data['assignments'] as List? ?? [];
      return list.map((e) => AssignmentModel.fromJson(e)).toList();
    }
    throw Exception('Failed to load assignments');
  }
}

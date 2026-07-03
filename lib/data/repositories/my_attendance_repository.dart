import '../../core/constants/api_endpoints.dart';
import '../../core/network/dio_client.dart';
import '../models/my_attendance_model.dart';

class MyAttendanceRepository {
  final DioClient _dioClient = DioClient();

  Future<MyAttendanceResponse> getMyAttendance({String? month}) async {
    final queryParams = <String, dynamic>{};
    if (month != null) {
      queryParams['month'] = month;
    }

    final response = await _dioClient.get(
      ApiEndpoints.myAttendance,
      queryParameters: queryParams,
    );
    final data = response.data;
    if (data is Map && data['success'] == true) {
      return MyAttendanceResponse.fromJson(Map<String, dynamic>.from(data));
    }
    throw Exception(data['message'] ?? 'Failed to load attendance');
  }
}

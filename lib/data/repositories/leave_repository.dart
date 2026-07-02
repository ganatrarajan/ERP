import '../../core/constants/api_endpoints.dart';
import '../../core/network/dio_client.dart';
import '../../providers/leave_provider.dart';

class LeaveRepository {
  final DioClient _dioClient = DioClient();

  Future<List<LeaveRequestModel>> getLeaves() async {
    final response = await _dioClient.get(ApiEndpoints.leaves);
    final data = response.data;
    if (data is Map<String, dynamic> && data['success'] == true) {
      final list = data['leaves'] as List;
      return list.map((e) => LeaveRequestModel.fromJson(e)).toList();
    }
    throw Exception('Failed to load leaves');
  }

  Future<LeaveRequestModel> applyLeave({
    required String leaveType,
    required String startDate,
    required String endDate,
    required String reason,
  }) async {
    final response = await _dioClient.post(
      ApiEndpoints.leaves,
      data: {
        'leave_type': leaveType,
        'start_date': startDate,
        'end_date': endDate,
        'reason': reason,
      },
    );
    final data = response.data;
    if (data is Map<String, dynamic> && data['success'] == true) {
      return LeaveRequestModel.fromJson(data['leave']);
    }
    throw Exception(data['message'] ?? 'Failed to apply leave');
  }
}

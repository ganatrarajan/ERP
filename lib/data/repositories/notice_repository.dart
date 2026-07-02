import '../../core/constants/api_endpoints.dart';
import '../../core/network/dio_client.dart';
import '../models/notice.dart';

class NoticeRepository {
  final DioClient _dioClient = DioClient();

  Future<Map<String, dynamic>> getNotices({int page = 1}) async {
    final response = await _dioClient.get(
      ApiEndpoints.notices,
      queryParameters: {'page': page},
    );
    final data = response.data;
    if (data is Map<String, dynamic> && data['success'] == true) {
      return data;
    }
    throw Exception('Failed to load notices');
  }

  Future<NoticeModel> getNoticeDetails(int id) async {
    final response = await _dioClient.get(ApiEndpoints.noticeDetails(id));
    final data = response.data;
    if (data is Map<String, dynamic> && data['success'] == true) {
      return NoticeModel.fromJson(data['notice']);
    }
    throw Exception('Failed to load notice details');
  }
}

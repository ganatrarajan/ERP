import '../../core/constants/api_endpoints.dart';
import '../../core/network/dio_client.dart';
import '../models/homework.dart';

class HomeworkRepository {
  final DioClient _dioClient = DioClient();

  Future<Map<String, dynamic>> getHomeworks({
    int? classId,
    int? sectionId,
    int page = 1,
  }) async {
    final Map<String, dynamic> query = {'page': page};
    if (classId != null) query['class_id'] = classId;
    if (sectionId != null) query['section_id'] = sectionId;

    final response = await _dioClient.get(
      ApiEndpoints.homeworks,
      queryParameters: query,
    );
    final data = response.data;
    if (data is Map<String, dynamic> && data['success'] == true) {
      return data;
    }
    throw Exception('Failed to load homework list');
  }

  Future<HomeworkModel> getHomeworkDetails(int id) async {
    final response = await _dioClient.get(ApiEndpoints.homeworkDetails(id));
    final data = response.data;
    if (data is Map<String, dynamic> && data['success'] == true) {
      return HomeworkModel.fromJson(data['homework']);
    }
    throw Exception('Failed to load homework details');
  }

  Future<HomeworkModel> createHomework(HomeworkModel homework) async {
    final response = await _dioClient.post(
      ApiEndpoints.homeworks,
      data: homework.toJson(),
    );
    final data = response.data;
    if (data is Map<String, dynamic> && data['success'] == true) {
      return HomeworkModel.fromJson(data['homework']);
    }
    throw Exception(data['message'] ?? 'Failed to create homework');
  }

  Future<HomeworkModel> updateHomework(
    int id, {
    String? title,
    String? description,
    String? submissionDate,
    double? maxMarks,
  }) async {
    final Map<String, dynamic> payload = {};
    if (title != null) payload['title'] = title;
    if (description != null) payload['description'] = description;
    if (submissionDate != null) payload['submission_date'] = submissionDate;
    if (maxMarks != null) payload['max_marks'] = maxMarks;

    final response = await _dioClient.put(
      ApiEndpoints.homeworkDetails(id),
      data: payload,
    );
    final data = response.data;
    if (data is Map<String, dynamic> && data['success'] == true) {
      return HomeworkModel.fromJson(data['homework']);
    }
    throw Exception('Failed to update homework');
  }

  Future<bool> deleteHomework(int id) async {
    final response = await _dioClient.delete(ApiEndpoints.homeworkDetails(id));
    final data = response.data;
    return data is Map && data['success'] == true;
  }
}

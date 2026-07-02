import '../../core/constants/api_endpoints.dart';
import '../../core/network/dio_client.dart';
import '../models/exam.dart';
import '../models/exam_schedule.dart';

class ExamRepository {
  final DioClient _dioClient = DioClient();

  Future<List<ExamModel>> getExams({
    int? classId,
    int? sectionId,
    int? subjectId,
  }) async {
    final Map<String, dynamic> params = {};
    if (classId != null) params['class_id'] = classId;
    if (sectionId != null) params['section_id'] = sectionId;
    if (subjectId != null) params['subject_id'] = subjectId;

    final response = await _dioClient.get(
      ApiEndpoints.exams,
      queryParameters: params,
    );
    final data = response.data;
    if (data is Map<String, dynamic> && data['success'] == true) {
      final list = data['exams'] as List? ?? [];
      return list.map((e) => ExamModel.fromJson(e)).toList();
    }
    throw Exception('Failed to load assigned exams');
  }

  Future<Map<String, dynamic>> getExamMarksSheet({
    required int examId,
    required int classId,
    required int sectionId,
    required int subjectId,
  }) async {
    final response = await _dioClient.get(
      ApiEndpoints.examMarks(examId),
      queryParameters: {
        'class_id': classId,
        'section_id': sectionId,
        'subject_id': subjectId,
      },
    );
    final data = response.data;
    if (data is Map<String, dynamic> && data['success'] == true) {
      return data;
    }
    throw Exception(data['message'] ?? 'Failed to load exam marks sheet');
  }

  Future<bool> saveExamMarks({
    required int examId,
    required int examScheduleId,
    required int subjectId,
    required List<StudentExamMarkModel> marks,
  }) async {
    final payload = {
      'exam_id': examId,
      'exam_schedule_id': examScheduleId,
      'subject_id': subjectId,
      'marks': marks.map((e) => e.toSubmitJson()).toList(),
    };

    final response = await _dioClient.post(
      ApiEndpoints.saveExamMarks,
      data: payload,
    );
    final data = response.data;
    return data is Map && data['success'] == true;
  }

  Future<List<Map<String, dynamic>>> getExamSubjects({
    required int examId,
    required int classId,
    required int sectionId,
  }) async {
    final response = await _dioClient.get(
      ApiEndpoints.examSubjects(examId),
      queryParameters: {
        'class_id': classId,
        'section_id': sectionId,
      },
    );
    final data = response.data;
    if (data is Map<String, dynamic> && data['success'] == true) {
      final list = data['subjects'] as List? ?? [];
      return list.map((e) => Map<String, dynamic>.from(e)).toList();
    }
    throw Exception('Failed to load exam subjects');
  }
}

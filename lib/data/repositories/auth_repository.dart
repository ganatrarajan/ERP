import '../../core/constants/api_endpoints.dart';
import '../../core/network/dio_client.dart';
import '../models/school.dart';
import '../models/academic_year.dart';
import '../models/teacher.dart';

class LoginResult {
  final bool success;
  final String? token;
  final TeacherModel? teacher;
  final String? message;

  LoginResult({
    required this.success,
    this.token,
    this.teacher,
    this.message,
  });
}

class AuthRepository {
  final DioClient _dioClient = DioClient();

  Future<SchoolModel> verifySchoolCode(String schoolCode) async {
    final response = await _dioClient.post(
      ApiEndpoints.verifySchool,
      data: {'school_code': schoolCode},
    );
    final data = response.data;
    if (data is Map<String, dynamic> && data['success'] == true) {
      return SchoolModel.fromJson(data, code: schoolCode);
    }
    throw Exception(data['message'] ?? 'Invalid School Code');
  }

  Future<List<AcademicYearModel>> getAcademicYears(String schoolCode) async {
    final response = await _dioClient.get(ApiEndpoints.getAcademicYears(schoolCode));
    final data = response.data;
    if (data is List) {
      return data.map((e) => AcademicYearModel.fromJson(e)).toList();
    }
    throw Exception('Failed to load academic years');
  }

  Future<LoginResult> login({
    required String schoolCode,
    required String email,
    required String password,
    required int academicYearId,
  }) async {
    try {
      final response = await _dioClient.post(
        ApiEndpoints.login,
        data: {
          'school_code': schoolCode,
          'email': email,
          'password': password,
          'academic_year_id': academicYearId,
        },
      );

      final data = response.data;

      if (data is Map<String, dynamic> && data['success'] == true) {
        final teacherData = data['teacher'];
        return LoginResult(
          success: true,
          token: data['token'],
          teacher: teacherData != null ? TeacherModel.fromJson(teacherData) : null,
          message: data['message'],
        );
      }
      
      return LoginResult(
        success: false, 
        message: data is Map ? (data['message'] ?? 'Failed to authenticate') : 'Unexpected response from server'
      );
    } catch (e) {
      return LoginResult(success: false, message: e.toString());
    }
  }

  Future<bool> logout() async {
    try {
      final response = await _dioClient.post(ApiEndpoints.logout);
      final data = response.data;
      return data is Map && data['success'] == true;
    } catch (_) {
      return false;
    }
  }
}

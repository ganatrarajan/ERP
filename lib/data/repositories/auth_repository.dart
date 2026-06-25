import '../../core/constants/api_endpoints.dart';
import '../../core/network/dio_client.dart';
import '../models/student_model.dart';

import '../models/school_model.dart';
import '../models/academic_year_model.dart';

class LoginResult {
  final bool success;
  final bool forcePasswordChange;
  final String? token;
  final StudentModel? student;
  final String? message;
  final int? studentId;

  LoginResult({
    required this.success,
    this.forcePasswordChange = false,
    this.token,
    this.student,
    this.message,
    this.studentId,
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
    required int academicYearId,
    required String admissionNo,
    required String password,
  }) async {
    try {
      final response = await _dioClient.post(
        ApiEndpoints.login,
        data: {
          'school_code': schoolCode,
          'academic_year_id': academicYearId,
          'admission_no': admissionNo,
          'password': password,
        },
      );

      final data = response.data;

      if (data is Map<String, dynamic>) {
        if (data['force_password_change'] == true) {
          int? extractedStudentId;
          if (data['student_id'] != null) {
            extractedStudentId = int.tryParse(data['student_id'].toString());
          } else if (data['student'] != null && data['student']['id'] != null) {
            extractedStudentId = int.tryParse(data['student']['id'].toString());
          }
          
          return LoginResult(
            success: true,
            forcePasswordChange: true,
            studentId: extractedStudentId,
          );
        } else if (data['success'] == true) {
          final studentData = data['student'];
          return LoginResult(
            success: true,
            token: data['token'],
            student: studentData != null ? StudentModel.fromJson(studentData) : null,
            message: data['message'],
          );
        }
      }
      
      return LoginResult(success: false, message: "Unexpected response from server");
    } catch (e) {
      return LoginResult(success: false, message: e.toString());
    }
  }

  Future<bool> changePassword({
    required int studentId,
    required String oldPassword,
    required String newPassword,
    required String confirmPassword,
  }) async {
    final response = await _dioClient.post(
      ApiEndpoints.changePassword,
      data: {
        'student_id': studentId,
        'old_password': oldPassword,
        'new_password': newPassword,
        'confirm_password': confirmPassword,
      },
    );

    final data = response.data;
    return data is Map && data['success'] == true;
  }
}

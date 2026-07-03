import '../../core/constants/api_endpoints.dart';
import '../../core/network/dio_client.dart';
import '../models/teacher.dart';

class TeacherDocumentModel {
  final int id;
  final String title;
  final String filePath;

  TeacherDocumentModel({
    required this.id,
    required this.title,
    required this.filePath,
  });

  factory TeacherDocumentModel.fromJson(Map<String, dynamic> json) {
    return TeacherDocumentModel(
      id: json['id'] ?? 0,
      title: json['title'] ?? json['document_name'] ?? '',
      filePath: json['file_path'] ?? json['document_path'] ?? '',
    );
  }
}

class ProfileRepository {
  final DioClient _dioClient = DioClient();

  Future<TeacherModel> getProfile() async {
    final response = await _dioClient.get(ApiEndpoints.profile);
    final data = response.data;
    if (data is Map<String, dynamic> && data['success'] == true) {
      return TeacherModel.fromJson(data['teacher']);
    }
    throw Exception('Failed to load profile details');
  }

  Future<TeacherModel> updateProfile({
    String? emergencyContactName,
    String? emergencyContactMobile,
    String? address,
    String? mobile,
  }) async {
    final Map<String, dynamic> payload = {};
    if (emergencyContactName != null) payload['emergency_contact_name'] = emergencyContactName;
    if (emergencyContactMobile != null) payload['emergency_contact_mobile'] = emergencyContactMobile;
    if (address != null) payload['address'] = address;
    if (mobile != null) payload['mobile'] = mobile;

    final response = await _dioClient.post(
      ApiEndpoints.updateProfile,
      data: payload,
    );
    final data = response.data;
    if (data is Map<String, dynamic> && data['success'] == true) {
      return TeacherModel.fromJson(data['teacher'] ?? data['data'] ?? payload);
    }
    throw Exception('Failed to update profile');
  }

  Future<bool> changePassword({
    required String oldPassword,
    required String newPassword,
    required String confirmPassword,
  }) async {
    final response = await _dioClient.post(
      ApiEndpoints.changePassword,
      data: {
        'old_password': oldPassword,
        'new_password': newPassword,
        'confirm_password': confirmPassword,
      },
    );
    final data = response.data;
    if (data is Map && data['success'] == true) {
      return true;
    }
    throw Exception(data is Map ? (data['message'] ?? 'Failed to change password') : 'Failed to change password');
  }

  Future<List<TeacherDocumentModel>> getDocuments() async {
    final response = await _dioClient.get(ApiEndpoints.documents);
    final data = response.data;
    if (data is Map<String, dynamic> && data['success'] == true) {
      final list = data['documents'] as List? ?? [];
      return list.map((e) => TeacherDocumentModel.fromJson(e)).toList();
    }
    throw Exception('Failed to load documents');
  }
}

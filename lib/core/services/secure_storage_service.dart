import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class SecureStorageService {
  static final SecureStorageService _instance = SecureStorageService._internal();
  factory SecureStorageService() => _instance;
  SecureStorageService._internal();

  final FlutterSecureStorage _storage = const FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
  );

  static const String _keyToken = 'auth_token';
  static const String _keyStudent = 'student_data';
  static const String _keySchoolCode = 'school_code';
  static const String _keySchoolData = 'school_data';
  static const String _keyAcademicYearId = 'academic_year_id';
  static const String _keyAcademicYearData = 'academic_year_data';

  Future<void> saveToken(String token) async {
    await _storage.write(key: _keyToken, value: token);
  }

  Future<String?> getToken() async {
    return await _storage.read(key: _keyToken);
  }

  Future<void> saveStudentData(Map<String, dynamic> studentData) async {
    final jsonStr = json.encode(studentData);
    await _storage.write(key: _keyStudent, value: jsonStr);
  }

  Future<Map<String, dynamic>?> getStudentData() async {
    final jsonStr = await _storage.read(key: _keyStudent);
    if (jsonStr == null) return null;
    try {
      return json.decode(jsonStr) as Map<String, dynamic>;
    } catch (_) {
      return null;
    }
  }

  // School Data
  Future<void> saveSchoolCode(String code) async {
    await _storage.write(key: _keySchoolCode, value: code);
  }

  Future<String?> getSchoolCode() async {
    return await _storage.read(key: _keySchoolCode);
  }

  Future<void> saveSchoolData(Map<String, dynamic> data) async {
    await _storage.write(key: _keySchoolData, value: json.encode(data));
  }

  Future<Map<String, dynamic>?> getSchoolData() async {
    final str = await _storage.read(key: _keySchoolData);
    if (str == null) return null;
    try {
      return json.decode(str) as Map<String, dynamic>;
    } catch (_) {
      return null;
    }
  }

  // Academic Year Data
  Future<void> saveAcademicYearId(int id) async {
    await _storage.write(key: _keyAcademicYearId, value: id.toString());
  }

  Future<int?> getAcademicYearId() async {
    final str = await _storage.read(key: _keyAcademicYearId);
    return str != null ? int.tryParse(str) : null;
  }

  Future<void> saveAcademicYearData(Map<String, dynamic> data) async {
    await _storage.write(key: _keyAcademicYearData, value: json.encode(data));
  }

  Future<Map<String, dynamic>?> getAcademicYearData() async {
    final str = await _storage.read(key: _keyAcademicYearData);
    if (str == null) return null;
    try {
      return json.decode(str) as Map<String, dynamic>;
    } catch (_) {
      return null;
    }
  }

  Future<void> clearAcademicYear() async {
    await _storage.delete(key: _keyAcademicYearId);
    await _storage.delete(key: _keyAcademicYearData);
  }

  Future<void> clearAuth() async {
    await _storage.delete(key: _keyToken);
    await _storage.delete(key: _keyStudent);
  }

  Future<void> clearAll() async {
    await _storage.deleteAll();
  }
}

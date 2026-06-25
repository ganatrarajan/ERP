import 'package:flutter/material.dart';
import '../core/services/secure_storage_service.dart';
import '../data/models/student_model.dart';
import '../data/repositories/auth_repository.dart';
import '../data/repositories/student_repository.dart';

enum AuthStatus { uninitialized, authenticated, unauthenticated, forcePasswordChange }

class AuthProvider extends ChangeNotifier {
  final AuthRepository _authRepository = AuthRepository();
  final SecureStorageService _secureStorage = SecureStorageService();

  AuthStatus _status = AuthStatus.uninitialized;
  StudentModel? _student;
  String? _errorMessage;
  bool _isLoading = false;
  int? _tempStudentId;

  AuthStatus get status => _status;
  StudentModel? get student => _student;
  String? get errorMessage => _errorMessage;
  bool get isLoading => _isLoading;
  int? get tempStudentId => _tempStudentId;

  AuthProvider() {
    loadSession();
  }

  // Attempt session recovery on boot
  Future<void> loadSession() async {
    final token = await _secureStorage.getToken();
    final studentData = await _secureStorage.getStudentData();

    if (token != null && studentData != null) {
      _student = StudentModel.fromJson(studentData);
      _status = AuthStatus.authenticated;
    } else {
      _status = AuthStatus.unauthenticated;
    }
    notifyListeners();
  }

  // Set loading state
  void _setLoading(bool loading) {
    _isLoading = loading;
    notifyListeners();
  }

  // Execute login
  Future<bool> login(String admissionNo, String password) async {
    _setLoading(true);
    _errorMessage = null;

    final schoolCode = await _secureStorage.getSchoolCode();
    final academicYearId = await _secureStorage.getAcademicYearId();

    if (schoolCode == null || academicYearId == null) {
      _errorMessage = "School configuration missing. Please verify school and academic year.";
      _setLoading(false);
      notifyListeners();
      return false;
    }

    final result = await _authRepository.login(
      schoolCode: schoolCode,
      academicYearId: academicYearId,
      admissionNo: admissionNo,
      password: password,
    );

    _setLoading(false);

    if (result.success) {
      if (result.forcePasswordChange) {
        _tempStudentId = result.studentId;
        _status = AuthStatus.forcePasswordChange;
        notifyListeners();
        return true;
      } else if (result.token != null && result.student != null) {
        _student = result.student;
        await _secureStorage.saveToken(result.token!);
        await _secureStorage.saveStudentData(result.student!.toJson());
        _status = AuthStatus.authenticated;
        notifyListeners();
        return true;
      }
    }

    _errorMessage = result.message ?? "Authentication failed";
    _status = AuthStatus.unauthenticated;
    notifyListeners();
    return false;
  }

  // Execute change password
  Future<bool> changePassword({
    required int studentId,
    required String oldPassword,
    required String newPassword,
    required String confirmPassword,
  }) async {
    _setLoading(true);
    _errorMessage = null;

    try {
      final success = await _authRepository.changePassword(
        studentId: studentId,
        oldPassword: oldPassword,
        newPassword: newPassword,
        confirmPassword: confirmPassword,
      );

      _setLoading(false);

      if (success) {
        // After successfully setting a new password, redirect to login
        _status = AuthStatus.unauthenticated;
        notifyListeners();
        return true;
      } else {
        _errorMessage = "Password change failed";
        return false;
      }
    } catch (e) {
      _setLoading(false);
      _errorMessage = e.toString();
      return false;
    }
  }

  // Clear session
  Future<void> logout() async {
    await _secureStorage.clearAuth();
    _student = null;
    _status = AuthStatus.unauthenticated;
    notifyListeners();
  }

  // Fetch student profile dynamically
  Future<void> fetchProfile() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final studentRepository = StudentRepository();
      final updatedStudent = await studentRepository.getProfile();
      _student = updatedStudent;
      await _secureStorage.saveStudentData(updatedStudent.toJson());
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
}

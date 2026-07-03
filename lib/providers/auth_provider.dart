import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../data/models/teacher.dart';
import '../data/repositories/auth_repository.dart';
import '../core/services/secure_storage.dart';
import '../core/services/notification_service.dart';

class AuthState {
  final bool isAuthenticated;
  final String? token;
  final TeacherModel? teacher;
  final bool isLoading;
  final String? errorMessage;

  AuthState({
    this.isAuthenticated = false,
    this.token,
    this.teacher,
    this.isLoading = false,
    this.errorMessage,
  });

  AuthState copyWith({
    bool? isAuthenticated,
    String? token,
    TeacherModel? teacher,
    bool? isLoading,
    String? errorMessage,
  }) {
    return AuthState(
      isAuthenticated: isAuthenticated ?? this.isAuthenticated,
      token: token ?? this.token,
      teacher: teacher ?? this.teacher,
      isLoading: isLoading ?? this.isLoading,
      errorMessage: errorMessage,
    );
  }
}

class AuthNotifier extends StateNotifier<AuthState> {
  final AuthRepository _authRepository = AuthRepository();
  final SecureStorageService _secureStorage = SecureStorageService();

  AuthNotifier() : super(AuthState()) {
    checkSavedSession();
  }

  Future<void> checkSavedSession() async {
    state = state.copyWith(isLoading: true);
    final token = await _secureStorage.getToken();
    final teacherData = await _secureStorage.getTeacherData();

    if (token != null && teacherData != null) {
      state = AuthState(
        isAuthenticated: true,
        token: token,
        teacher: TeacherModel.fromJson(teacherData),
      );
    } else {
      state = AuthState(isAuthenticated: false);
    }
  }

  Future<bool> login({
    required String schoolCode,
    required String email,
    required String password,
    required int academicYearId,
  }) async {
    state = state.copyWith(isLoading: true, errorMessage: null);

    final result = await _authRepository.login(
      schoolCode: schoolCode,
      email: email,
      password: password,
      academicYearId: academicYearId,
    );

    if (result.success && result.token != null && result.teacher != null) {
      await _secureStorage.saveToken(result.token!);
      await _secureStorage.saveTeacherData(result.teacher!.toJson());

      state = AuthState(
        isAuthenticated: true,
        token: result.token,
        teacher: result.teacher,
        isLoading: false,
      );
      // Sync device notification token
      NotificationService().updateTokenToServer();
      
      return true;
    } else {
      state = state.copyWith(
        isLoading: false,
        errorMessage: result.message ?? 'Authentication failed',
      );
      return false;
    }
  }

  Future<void> logout([BuildContext? context]) async {
    state = state.copyWith(isLoading: true);
    await _authRepository.logout(context);
    await _secureStorage.clearAuth();
    await NotificationService().clearAll();
    state = AuthState(isAuthenticated: false);
  }

  void updateTeacherProfile(TeacherModel updatedTeacher) async {
    await _secureStorage.saveTeacherData(updatedTeacher.toJson());
    state = state.copyWith(teacher: updatedTeacher);
  }
}

final authProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  return AuthNotifier();
});

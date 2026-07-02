import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../data/models/school.dart';
import '../data/models/academic_year.dart';
import '../data/repositories/auth_repository.dart';
import '../core/services/secure_storage.dart';

class OnboardingState {
  final SchoolModel? school;
  final AcademicYearModel? academicYear;
  final List<AcademicYearModel> academicYears;
  final bool isLoading;
  final String? errorMessage;

  OnboardingState({
    this.school,
    this.academicYear,
    this.academicYears = const [],
    this.isLoading = false,
    this.errorMessage,
  });

  OnboardingState copyWith({
    SchoolModel? school,
    AcademicYearModel? academicYear,
    List<AcademicYearModel>? academicYears,
    bool? isLoading,
    String? errorMessage,
    bool clearSchool = false,
    bool clearAcademicYear = false,
  }) {
    return OnboardingState(
      school: clearSchool ? null : (school ?? this.school),
      academicYear: clearAcademicYear ? null : (academicYear ?? this.academicYear),
      academicYears: academicYears ?? this.academicYears,
      isLoading: isLoading ?? this.isLoading,
      errorMessage: errorMessage,
    );
  }
}

class OnboardingNotifier extends StateNotifier<OnboardingState> {
  final AuthRepository _authRepository = AuthRepository();
  final SecureStorageService _secureStorage = SecureStorageService();

  OnboardingNotifier() : super(OnboardingState()) {
    loadSavedData();
  }

  Future<void> loadSavedData() async {
    state = state.copyWith(isLoading: true);
    
    final schoolData = await _secureStorage.getSchoolData();
    final schoolCode = await _secureStorage.getSchoolCode();
    SchoolModel? school;
    if (schoolData != null && schoolCode != null) {
      school = SchoolModel.fromJson(schoolData, code: schoolCode);
    }
    
    final ayData = await _secureStorage.getAcademicYearData();
    AcademicYearModel? ay;
    if (ayData != null) {
      ay = AcademicYearModel.fromJson(ayData);
    }
    
    state = state.copyWith(
      school: school,
      academicYear: ay,
      isLoading: false,
    );
  }

  Future<bool> verifySchoolCode(String code) async {
    state = state.copyWith(isLoading: true, errorMessage: null);

    try {
      final school = await _authRepository.verifySchoolCode(code);
      await _secureStorage.saveSchoolCode(code);
      await _secureStorage.saveSchoolData(school.toJson());

      AcademicYearModel? year;
      if (school.mobileAcademicYearId != null) {
        year = AcademicYearModel(
          id: school.mobileAcademicYearId!,
          name: school.mobileAcademicYearTitle ?? 'N/A',
          isCurrent: true,
        );
        await _secureStorage.saveAcademicYearId(year.id);
        await _secureStorage.saveAcademicYearData(year.toJson());
      }
      
      state = state.copyWith(
        school: school,
        academicYear: year,
        isLoading: false,
      );
      return true;
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
      return false;
    }
  }

  Future<bool> fetchAcademicYears() async {
    if (state.school == null) return false;
    
    state = state.copyWith(isLoading: true, errorMessage: null);

    try {
      final years = await _authRepository.getAcademicYears(state.school!.code);
      state = state.copyWith(
        academicYears: years,
        isLoading: false,
      );
      return true;
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
      return false;
    }
  }

  Future<void> selectAcademicYear(AcademicYearModel year) async {
    await _secureStorage.saveAcademicYearId(year.id);
    await _secureStorage.saveAcademicYearData(year.toJson());
    state = state.copyWith(academicYear: year);
  }

  Future<void> clearAcademicYear() async {
    await _secureStorage.clearAcademicYear();
    state = state.copyWith(clearAcademicYear: true);
  }

  Future<void> clearOnboarding() async {
    await _secureStorage.clearAll();
    state = OnboardingState();
  }
}

final onboardingProvider = StateNotifierProvider<OnboardingNotifier, OnboardingState>((ref) {
  return OnboardingNotifier();
});

import 'package:flutter/material.dart';
import '../data/models/school_model.dart';
import '../data/models/academic_year_model.dart';
import '../data/repositories/auth_repository.dart';
import '../core/services/secure_storage_service.dart';

class OnboardingProvider extends ChangeNotifier {
  final AuthRepository _authRepository = AuthRepository();
  final SecureStorageService _secureStorage = SecureStorageService();

  SchoolModel? _school;
  AcademicYearModel? _academicYear;
  List<AcademicYearModel> _academicYears = [];
  
  bool _isLoading = false;
  String? _errorMessage;

  SchoolModel? get school => _school;
  AcademicYearModel? get academicYear => _academicYear;
  List<AcademicYearModel> get academicYears => _academicYears;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  Future<void> loadSavedData() async {
    final schoolData = await _secureStorage.getSchoolData();
    if (schoolData != null) {
      _school = SchoolModel.fromJson(schoolData);
    }
    
    final ayData = await _secureStorage.getAcademicYearData();
    if (ayData != null) {
      _academicYear = AcademicYearModel.fromJson(ayData);
    }
    notifyListeners();
  }

  Future<bool> verifySchoolCode(String code) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final school = await _authRepository.verifySchoolCode(code);
      _school = school;
      await _secureStorage.saveSchoolCode(code);
      await _secureStorage.saveSchoolData(school.toJson());

      if (school.mobileAcademicYearId != null) {
        final year = AcademicYearModel(
          id: school.mobileAcademicYearId!,
          name: school.mobileAcademicYearTitle ?? 'N/A',
          isCurrent: true,
        );
        _academicYear = year;
        await _secureStorage.saveAcademicYearId(year.id);
        await _secureStorage.saveAcademicYearData(year.toJson());
      }
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> fetchAcademicYears() async {
    if (_school == null) return false;
    
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _academicYears = await _authRepository.getAcademicYears(_school!.code);
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> selectAcademicYear(AcademicYearModel year) async {
    _academicYear = year;
    await _secureStorage.saveAcademicYearId(year.id);
    await _secureStorage.saveAcademicYearData(year.toJson());
    notifyListeners();
  }

  Future<void> clearAcademicYear() async {
    _academicYear = null;
    await _secureStorage.clearAcademicYear();
    notifyListeners();
  }

  Future<void> clearOnboarding() async {
    _school = null;
    _academicYear = null;
    _academicYears = [];
    await _secureStorage.clearAll();
    notifyListeners();
  }
}

import 'package:flutter/material.dart';
import '../data/models/dashboard_model.dart';
import '../data/repositories/student_repository.dart';

class DashboardProvider extends ChangeNotifier {
  final StudentRepository _studentRepository = StudentRepository();

  DashboardData? _dashboardData;
  bool _isLoading = false;
  String? _errorMessage;

  DashboardData? get dashboardData => _dashboardData;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  Future<void> fetchDashboardData() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _dashboardData = await _studentRepository.getDashboardData();
    } catch (e) {
      _errorMessage = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
}

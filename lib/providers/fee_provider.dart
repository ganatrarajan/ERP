import 'package:flutter/material.dart';
import '../data/models/fee_model.dart';
import '../data/repositories/student_repository.dart';

class FeeProvider extends ChangeNotifier {
  final StudentRepository _studentRepository = StudentRepository();

  FeeResponse? _feeResponse;
  bool _isLoading = false;
  String? _errorMessage;

  FeeResponse? get feeResponse => _feeResponse;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  Future<void> fetchFees() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _feeResponse = await _studentRepository.getFees();
    } catch (e) {
      _errorMessage = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
}

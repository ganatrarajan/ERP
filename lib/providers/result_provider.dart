import 'package:flutter/material.dart';
import '../data/models/exam_result_model.dart';
import '../data/repositories/student_repository.dart';

class ResultProvider extends ChangeNotifier {
  final StudentRepository _studentRepository = StudentRepository();

  List<ExamResultModel> _results = [];
  bool _isLoading = false;
  String? _errorMessage;

  List<ExamResultModel> get results => _results;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  Future<void> fetchResults() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _results = await _studentRepository.getExamResults();
    } catch (e) {
      _errorMessage = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
}

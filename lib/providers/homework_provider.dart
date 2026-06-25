import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../data/models/homework_model.dart';
import '../data/repositories/student_repository.dart';

class HomeworkProvider extends ChangeNotifier {
  final StudentRepository _studentRepository = StudentRepository();

  List<HomeworkModel> _homeworks = [];
  bool _isLoading = false;
  String? _errorMessage;

  // Filters
  int? _selectedSubjectId;
  DateTime? _selectedDate;

  List<HomeworkModel> get homeworks => _homeworks;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  int? get selectedSubjectId => _selectedSubjectId;
  DateTime? get selectedDate => _selectedDate;

  String? get formattedDateFilter =>
      _selectedDate != null ? DateFormat('yyyy-MM-dd').format(_selectedDate!) : null;

  Future<void> fetchHomework() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _homeworks = await _studentRepository.getHomework(
        subjectId: _selectedSubjectId,
        date: formattedDateFilter,
      );
    } catch (e) {
      _errorMessage = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  void setSubjectFilter(int? subjectId) {
    _selectedSubjectId = subjectId;
    fetchHomework();
  }

  void setDateFilter(DateTime? date) {
    _selectedDate = date;
    fetchHomework();
  }

  void clearFilters() {
    _selectedSubjectId = null;
    _selectedDate = null;
    fetchHomework();
  }
}

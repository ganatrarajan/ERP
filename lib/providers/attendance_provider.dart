import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../data/models/attendance_model.dart';
import '../data/repositories/student_repository.dart';

class AttendanceProvider extends ChangeNotifier {
  final StudentRepository _studentRepository = StudentRepository();

  List<AttendanceRecord> _records = [];
  AttendanceStats? _stats;
  bool _isLoading = false;
  String? _errorMessage;
  DateTime _selectedMonth = DateTime.now();

  List<AttendanceRecord> get records => _records;
  AttendanceStats? get stats => _stats;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  DateTime get selectedMonth => _selectedMonth;

  String get formattedMonthFilter => DateFormat('yyyy-MM').format(_selectedMonth);

  Future<void> fetchAttendance({DateTime? month}) async {
    if (month != null) {
      _selectedMonth = month;
    }

    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _studentRepository.getAttendance(month: formattedMonthFilter);
      _records = response.records;
      _stats = response.stats;
    } catch (e) {
      _errorMessage = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  void changeMonth(DateTime newMonth) {
    fetchAttendance(month: newMonth);
  }
}

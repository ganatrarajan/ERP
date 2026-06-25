import 'package:flutter/material.dart';
import '../data/models/notice_model.dart';
import '../data/repositories/student_repository.dart';

class NoticeProvider extends ChangeNotifier {
  final StudentRepository _studentRepository = StudentRepository();

  List<NoticeModel> _notices = [];
  bool _isLoading = false;
  String? _errorMessage;
  String? _activeType; // 'school' or 'class'

  List<NoticeModel> get notices => _notices;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  String? get activeType => _activeType;

  Future<void> fetchNotices({String? type}) async {
    _activeType = type;
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _notices = await _studentRepository.getNotices(type: type);
    } catch (e) {
      _errorMessage = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  void changeType(String? type) {
    fetchNotices(type: type);
  }
}

import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../data/models/my_attendance_model.dart';
import '../data/repositories/my_attendance_repository.dart';

class MyAttendanceState {
  final List<MyAttendanceRecord> records;
  final MyAttendanceStats? stats;
  final bool isLoading;
  final String? errorMessage;
  final String? selectedMonth; // format: YYYY-MM

  MyAttendanceState({
    this.records = const [],
    this.stats,
    this.isLoading = false,
    this.errorMessage,
    this.selectedMonth,
  });

  MyAttendanceState copyWith({
    List<MyAttendanceRecord>? records,
    MyAttendanceStats? stats,
    bool? isLoading,
    String? errorMessage,
    String? selectedMonth,
  }) {
    return MyAttendanceState(
      records: records ?? this.records,
      stats: stats ?? this.stats,
      isLoading: isLoading ?? this.isLoading,
      errorMessage: errorMessage,
      selectedMonth: selectedMonth ?? this.selectedMonth,
    );
  }
}

class MyAttendanceNotifier extends StateNotifier<MyAttendanceState> {
  final MyAttendanceRepository _repository = MyAttendanceRepository();

  MyAttendanceNotifier() : super(MyAttendanceState()) {
    // Default to current month
    final now = DateTime.now();
    final monthStr = "${now.year}-${now.month.toString().padLeft(2, '0')}";
    fetchAttendance(monthStr);
  }

  Future<void> fetchAttendance(String month) async {
    state = state.copyWith(isLoading: true, errorMessage: null, selectedMonth: month);
    try {
      final response = await _repository.getMyAttendance(month: month);
      state = state.copyWith(
        records: response.records,
        stats: response.stats,
        isLoading: false,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
    }
  }
}

final myAttendanceProvider = StateNotifierProvider<MyAttendanceNotifier, MyAttendanceState>((ref) {
  return MyAttendanceNotifier();
});

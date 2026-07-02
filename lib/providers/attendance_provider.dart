import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../data/models/assignment.dart';
import '../data/models/student_attendance.dart';
import '../data/repositories/attendance_repository.dart';

class AttendanceState {
  final bool isLoading;
  final bool isSaving;
  final List<AssignmentModel> classes;
  final List<StudentAttendanceModel> students;
  final List<AttendanceHistoryRecord> history;
  final Map<String, dynamic>? monthlyGrid;
  final String selectedDate;
  final String? errorMessage;

  AttendanceState({
    this.isLoading = false,
    this.isSaving = false,
    this.classes = const [],
    this.students = const [],
    this.history = const [],
    this.monthlyGrid,
    required this.selectedDate,
    this.errorMessage,
  });

  AttendanceState copyWith({
    bool? isLoading,
    bool? isSaving,
    List<AssignmentModel>? classes,
    List<StudentAttendanceModel>? students,
    List<AttendanceHistoryRecord>? history,
    Map<String, dynamic>? monthlyGrid,
    String? selectedDate,
    String? errorMessage,
  }) {
    return AttendanceState(
      isLoading: isLoading ?? this.isLoading,
      isSaving: isSaving ?? this.isSaving,
      classes: classes ?? this.classes,
      students: students ?? this.students,
      history: history ?? this.history,
      monthlyGrid: monthlyGrid ?? this.monthlyGrid,
      selectedDate: selectedDate ?? this.selectedDate,
      errorMessage: errorMessage,
    );
  }
}

class AttendanceNotifier extends StateNotifier<AttendanceState> {
  final AttendanceRepository _repository = AttendanceRepository();

  AttendanceNotifier()
      : super(AttendanceState(
          selectedDate: DateFormat('yyyy-MM-dd').format(DateTime.now()),
        ));

  void updateDate(String date) {
    state = state.copyWith(selectedDate: date);
  }

  Future<void> fetchClasses() async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    try {
      final classes = await _repository.getClasses();
      state = state.copyWith(classes: classes, isLoading: false);
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
    }
  }

  Future<void> fetchStudents({required int classId, required int sectionId}) async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    try {
      final students = await _repository.getStudents(
        classId: classId,
        sectionId: sectionId,
        date: state.selectedDate,
      );
      state = state.copyWith(students: students, isLoading: false);
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
    }
  }

  void updateStudentStatus(int studentId, String status) {
    final updatedList = state.students.map((s) {
      if (s.studentId == studentId) {
        s.attendanceStatus = status;
      }
      return s;
    }).toList();
    state = state.copyWith(students: updatedList);
  }

  void updateStudentRemarks(int studentId, String remarks) {
    final updatedList = state.students.map((s) {
      if (s.studentId == studentId) {
        s.remarks = remarks;
      }
      return s;
    }).toList();
    state = state.copyWith(students: updatedList);
  }

  void markAllPresent() {
    final updatedList = state.students.map((s) {
      if (!s.isHoliday) {
        s.attendanceStatus = 'Present';
      }
      return s;
    }).toList();
    state = state.copyWith(students: updatedList);
  }

  Future<bool> saveAttendance({required int classId, required int sectionId}) async {
    state = state.copyWith(isSaving: true, errorMessage: null);
    try {
      final success = await _repository.saveAttendance(
        classId: classId,
        sectionId: sectionId,
        date: state.selectedDate,
        students: state.students,
      );
      state = state.copyWith(isSaving: false);
      return success;
    } catch (e) {
      state = state.copyWith(
        isSaving: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
      return false;
    }
  }

  Future<void> fetchHistory({
    required int classId,
    required int sectionId,
    required String startDate,
    required String endDate,
  }) async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    try {
      final history = await _repository.getHistory(
        classId: classId,
        sectionId: sectionId,
        startDate: startDate,
        endDate: endDate,
      );
      state = state.copyWith(history: history, isLoading: false);
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
    }
  }

  Future<void> fetchMonthlyGrid({
    required int classId,
    required int sectionId,
    required String month,
  }) async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    try {
      final grid = await _repository.getMonthlyGrid(
        classId: classId,
        sectionId: sectionId,
        month: month,
      );
      state = state.copyWith(monthlyGrid: grid, isLoading: false);
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
    }
  }
}

final attendanceProvider = StateNotifierProvider<AttendanceNotifier, AttendanceState>((ref) {
  return AttendanceNotifier();
});

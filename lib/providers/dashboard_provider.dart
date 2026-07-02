import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../data/repositories/dashboard_repository.dart';
import '../data/repositories/attendance_repository.dart';
import '../data/models/assignment.dart';

class DashboardState {
  final bool isLoading;
  final DashboardStats? stats;
  final List<AssignmentModel> assignments;
  final String? errorMessage;

  DashboardState({
    this.isLoading = false,
    this.stats,
    this.assignments = const [],
    this.errorMessage,
  });

  DashboardState copyWith({
    bool? isLoading,
    DashboardStats? stats,
    List<AssignmentModel>? assignments,
    String? errorMessage,
  }) {
    return DashboardState(
      isLoading: isLoading ?? this.isLoading,
      stats: stats ?? this.stats,
      assignments: assignments ?? this.assignments,
      errorMessage: errorMessage,
    );
  }
}

class DashboardNotifier extends StateNotifier<DashboardState> {
  final DashboardRepository _repository = DashboardRepository();
  final AttendanceRepository _attendanceRepository = AttendanceRepository();

  DashboardNotifier() : super(DashboardState());

  Future<void> fetchDashboardData() async {
    state = state.copyWith(isLoading: true, errorMessage: null);

    try {
      final stats = await _repository.getStats();
      final assignments = await _repository.getAssignments();

      state = DashboardState(
        stats: stats,
        assignments: assignments,
        isLoading: false,
      );

      // Proactively fetch student counts for assignments to show in "My Classes" screen
      _fetchStudentCounts(assignments);
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
    }
  }

  Future<void> _fetchStudentCounts(List<AssignmentModel> assignments) async {
    final todayStr = DateFormat('Y-MM-d').format(DateTime.now());
    
    // Process them to load student list count for each unique class-section combination
    final Map<String, int> counts = {};
    
    for (var i = 0; i < assignments.length; i++) {
      final assignment = assignments[i];
      final key = '${assignment.classId}-${assignment.sectionId}';
      
      if (counts.containsKey(key)) {
        assignments[i].totalStudents = counts[key];
        continue;
      }

      try {
        final students = await _attendanceRepository.getStudents(
          classId: assignment.classId,
          sectionId: assignment.sectionId,
          date: todayStr,
        );
        counts[key] = students.length;
        assignments[i].totalStudents = students.length;
        
        // Trigger a state update once a student count is loaded
        if (state.assignments == assignments) {
          state = state.copyWith(assignments: List.from(assignments));
        }
      } catch (_) {
        // Fallback to default or null
      }
    }
  }
}

final dashboardProvider = StateNotifierProvider<DashboardNotifier, DashboardState>((ref) {
  return DashboardNotifier();
});

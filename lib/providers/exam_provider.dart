import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../data/models/exam.dart';
import '../data/models/exam_schedule.dart';
import '../data/repositories/exam_repository.dart';

class ExamState {
  final bool isLoading;
  final bool isSaving;
  final List<ExamModel> exams;
  final List<Map<String, dynamic>> subjects;
  
  final ExamScheduleModel? schedule;
  final List<GradeScaleModel> grades;
  final List<StudentExamMarkModel> students;
  
  final String? errorMessage;

  ExamState({
    this.isLoading = false,
    this.isSaving = false,
    this.exams = const [],
    this.subjects = const [],
    this.schedule,
    this.grades = const [],
    this.students = const [],
    this.errorMessage,
  });

  ExamState copyWith({
    bool? isLoading,
    bool? isSaving,
    List<ExamModel>? exams,
    List<Map<String, dynamic>>? subjects,
    ExamScheduleModel? schedule,
    List<GradeScaleModel>? grades,
    List<StudentExamMarkModel>? students,
    String? errorMessage,
  }) {
    return ExamState(
      isLoading: isLoading ?? this.isLoading,
      isSaving: isSaving ?? this.isSaving,
      exams: exams ?? this.exams,
      subjects: subjects ?? this.subjects,
      schedule: schedule ?? this.schedule,
      grades: grades ?? this.grades,
      students: students ?? this.students,
      errorMessage: errorMessage,
    );
  }
}

class ExamNotifier extends StateNotifier<ExamState> {
  final ExamRepository _repository = ExamRepository();

  ExamNotifier() : super(ExamState());

  Future<void> fetchExams({
    int? classId,
    int? sectionId,
    int? subjectId,
  }) async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    try {
      final exams = await _repository.getExams(
        classId: classId,
        sectionId: sectionId,
        subjectId: subjectId,
      );
      state = state.copyWith(exams: exams, isLoading: false);
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
    }
  }

  Future<void> fetchExamSubjects({
    required int examId,
    required int classId,
    required int sectionId,
  }) async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    try {
      final subjects = await _repository.getExamSubjects(
        examId: examId,
        classId: classId,
        sectionId: sectionId,
      );
      state = state.copyWith(subjects: subjects, isLoading: false);
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
    }
  }

  Future<void> fetchMarksSheet({
    required int examId,
    required int classId,
    required int sectionId,
    required int subjectId,
  }) async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    try {
      final data = await _repository.getExamMarksSheet(
        examId: examId,
        classId: classId,
        sectionId: sectionId,
        subjectId: subjectId,
      );

      final schedule = ExamScheduleModel.fromJson(data['schedule'] ?? {});
      
      final gradesList = data['grades'] as List? ?? [];
      final grades = gradesList.map((e) => GradeScaleModel.fromJson(e)).toList();

      final studentsList = data['students'] as List? ?? [];
      final students = studentsList.map((e) => StudentExamMarkModel.fromJson(e)).toList();

      state = state.copyWith(
        schedule: schedule,
        grades: grades,
        students: students,
        isLoading: false,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
    }
  }

  void updateStudentMarks(int studentId, double? marks) {
    final schedule = state.schedule;
    if (schedule == null) return;

    final updated = state.students.map((s) {
      if (s.studentId == studentId) {
        s.marksObtained = marks;
        if (marks != null && schedule.maxMarks > 0) {
          // Calculate auto grade
          final percentage = (marks / schedule.maxMarks) * 100;
          final matched = _findMatchedGrade(percentage);
          s.gradeId = matched?.id;
        } else {
          s.gradeId = null;
        }
      }
      return s;
    }).toList();

    state = state.copyWith(students: updated);
  }

  void updateStudentAbsence(int studentId, bool isAbsent) {
    final updated = state.students.map((s) {
      if (s.studentId == studentId) {
        s.isAbsent = isAbsent;
        if (isAbsent) {
          s.marksObtained = 0.0;
          s.gradeId = null;
        }
      }
      return s;
    }).toList();
    state = state.copyWith(students: updated);
  }

  void updateStudentRemarks(int studentId, String remarks) {
    final updated = state.students.map((s) {
      if (s.studentId == studentId) {
        s.remarks = remarks;
      }
      return s;
    }).toList();
    state = state.copyWith(students: updated);
  }

  GradeScaleModel? _findMatchedGrade(double percentage) {
    GradeScaleModel? matched;
    double highestMin = -1;
    for (var grade in state.grades) {
      if (grade.minPercentage <= percentage && grade.minPercentage > highestMin) {
        highestMin = grade.minPercentage;
        matched = grade;
      }
    }
    return matched;
  }

  Future<bool> saveExamMarks({
    required int examId,
    required int subjectId,
  }) async {
    final schedule = state.schedule;
    if (schedule == null) return false;

    state = state.copyWith(isSaving: true, errorMessage: null);

    try {
      final success = await _repository.saveExamMarks(
        examId: examId,
        examScheduleId: schedule.id,
        subjectId: subjectId,
        marks: state.students,
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
}

final examProvider = StateNotifierProvider<ExamNotifier, ExamState>((ref) {
  return ExamNotifier();
});

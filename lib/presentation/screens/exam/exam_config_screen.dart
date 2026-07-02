import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../providers/dashboard_provider.dart';
import '../../../providers/exam_provider.dart';
import '../../widgets/custom_button.dart';
import 'exam_marks_entry_screen.dart';

class ExamConfigScreen extends ConsumerStatefulWidget {
  const ExamConfigScreen({super.key});

  @override
  ConsumerState<ExamConfigScreen> createState() => _ExamConfigScreenState();
}

class _ExamConfigScreenState extends ConsumerState<ExamConfigScreen> {
  final _formKey = GlobalKey<FormState>();

  int? _selectedClassId;
  int? _selectedSectionId;
  int? _selectedExamId;
  int? _selectedSubjectId;

  String? _selectedClassName;
  String? _selectedSectionName;
  String? _selectedExamName;
  String? _selectedSubjectName;

  bool _loadingExams = false;
  bool _loadingSubjects = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(dashboardProvider.notifier).fetchDashboardData();
    });
  }

  void _onClassChanged(int? classId, String className) {
    setState(() {
      _selectedClassId = classId;
      _selectedClassName = className;
      
      // Reset dependent values
      _selectedSectionId = null;
      _selectedSectionName = null;
      _selectedExamId = null;
      _selectedExamName = null;
      _selectedSubjectId = null;
      _selectedSubjectName = null;
    });
  }

  void _onSectionChanged(int? sectionId, String sectionName) async {
    setState(() {
      _selectedSectionId = sectionId;
      _selectedSectionName = sectionName;
      
      _selectedExamId = null;
      _selectedExamName = null;
      _selectedSubjectId = null;
      _selectedSubjectName = null;
      _loadingExams = true;
    });

    try {
      await ref.read(examProvider.notifier).fetchExams(
            classId: _selectedClassId,
            sectionId: sectionId,
          );
    } catch (e) {
      debugPrint("Failed to load exams: $e");
    } finally {
      if (mounted) {
        setState(() {
          _loadingExams = false;
        });
      }
    }
  }

  void _onExamChanged(int? examId, String examName) async {
    setState(() {
      _selectedExamId = examId;
      _selectedExamName = examName;
      
      _selectedSubjectId = null;
      _selectedSubjectName = null;
      _loadingSubjects = true;
    });

    try {
      await ref.read(examProvider.notifier).fetchExamSubjects(
            examId: examId!,
            classId: _selectedClassId!,
            sectionId: _selectedSectionId!,
          );
    } catch (e) {
      debugPrint("Failed to load exam subjects: $e");
    } finally {
      if (mounted) {
        setState(() {
          _loadingSubjects = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final dashboardState = ref.watch(dashboardProvider);
    final examState = ref.watch(examProvider);

    final assignments = dashboardState.assignments;

    // 1. Unique Class List
    final classesMap = <int, String>{};
    for (var a in assignments) {
      classesMap[a.classId] = a.className.name;
    }

    // 2. Sections for selected class
    final sectionsMap = <int, String>{};
    if (_selectedClassId != null) {
      for (var a in assignments) {
        if (a.classId == _selectedClassId) {
          sectionsMap[a.sectionId] = a.sectionName.name;
        }
      }
    }

    // Modern input decoration style helper
    InputDecoration buildModernInputDecoration(String labelText, IconData icon, {bool isLoading = false}) {
      return InputDecoration(
        labelText: labelText,
        labelStyle: TextStyle(color: theme.colorScheme.onSurface.withOpacity(0.6), fontSize: 13),
        prefixIcon: Icon(icon, color: theme.colorScheme.primary, size: 20),
        suffixIcon: isLoading
            ? const Padding(
                padding: EdgeInsets.all(12.0),
                child: SizedBox(
                  width: 16,
                  height: 16,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
              )
            : null,
        filled: true,
        fillColor: theme.colorScheme.surfaceContainerLow,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: theme.colorScheme.outline.withOpacity(0.08)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: theme.colorScheme.primary, width: 1.5),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: theme.colorScheme.error, width: 1),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: theme.colorScheme.error, width: 1.5),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text("Marks Entry Configuration"),
        elevation: 0,
        backgroundColor: Colors.transparent,
      ),
      extendBodyBehindAppBar: true,
      body: Container(
        height: double.infinity,
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              theme.colorScheme.primary.withOpacity(0.08),
              theme.colorScheme.surface,
            ],
            stops: const [0.0, 0.4],
          ),
        ),
        child: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(20.0),
            child: Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const SizedBox(height: 10),
                  // Premium Gradient Header Card
                  Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [
                          theme.colorScheme.primary,
                          theme.colorScheme.primary.withBlue(220),
                        ],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(20),
                      boxShadow: [
                        BoxShadow(
                          color: theme.colorScheme.primary.withOpacity(0.2),
                          blurRadius: 16,
                          offset: const Offset(0, 8),
                        ),
                      ],
                    ),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.15),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(
                            Icons.school_rounded,
                            color: Colors.white,
                            size: 28,
                          ),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                "Grading Session",
                                style: TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.bold,
                                  fontSize: 18,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                "Configure class parameter selections step-by-step",
                                style: TextStyle(
                                  color: Colors.white.withOpacity(0.8),
                                  fontSize: 12,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 28),

                  // Input Group Card
                  Card(
                    elevation: 0,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(20),
                      side: BorderSide(color: theme.colorScheme.outline.withOpacity(0.08)),
                    ),
                    color: theme.colorScheme.surfaceContainerLowest.withOpacity(0.8),
                    child: Padding(
                      padding: const EdgeInsets.all(20.0),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            "Select Assignment Context",
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 14,
                            ),
                          ),
                          const SizedBox(height: 20),

                          // 1. Class Dropdown
                          DropdownButtonFormField<int>(
                            value: _selectedClassId,
                            hint: const Text("Select Class"),
                            decoration: buildModernInputDecoration("Class", Icons.class_outlined),
                            dropdownColor: theme.colorScheme.surface,
                            icon: const Icon(Icons.arrow_drop_down_rounded, size: 28),
                            items: classesMap.entries.map((entry) {
                              return DropdownMenuItem<int>(
                                value: entry.key,
                                child: Text(entry.value, style: const TextStyle(fontSize: 14)),
                              );
                            }).toList(),
                            onChanged: (val) {
                              if (val != null) _onClassChanged(val, classesMap[val]!);
                            },
                            validator: (val) => val == null ? "Please select a class" : null,
                          ),
                          const SizedBox(height: 18),

                          // 2. Section Dropdown
                          DropdownButtonFormField<int>(
                            value: _selectedSectionId,
                            hint: const Text("Select Section"),
                            decoration: buildModernInputDecoration("Section", Icons.grid_view_rounded),
                            dropdownColor: theme.colorScheme.surface,
                            icon: const Icon(Icons.arrow_drop_down_rounded, size: 28),
                            items: _selectedClassId == null
                                ? []
                                : sectionsMap.entries.map((entry) {
                                    return DropdownMenuItem<int>(
                                      value: entry.key,
                                      child: Text(entry.value, style: const TextStyle(fontSize: 14)),
                                    );
                                  }).toList(),
                            onChanged: _selectedClassId == null
                                ? null
                                : (val) {
                                    if (val != null) _onSectionChanged(val, sectionsMap[val]!);
                                  },
                            validator: (val) => val == null ? "Please select a section" : null,
                          ),
                          const SizedBox(height: 18),

                          // 3. Exam Dropdown
                          DropdownButtonFormField<int>(
                            value: _selectedExamId,
                            hint: _loadingExams
                                ? const Text("Loading exams...")
                                : const Text("Select Exam"),
                            decoration: buildModernInputDecoration("Exam", Icons.description_outlined, isLoading: _loadingExams),
                            dropdownColor: theme.colorScheme.surface,
                            icon: _loadingExams
                                ? const SizedBox.shrink()
                                : const Icon(Icons.arrow_drop_down_rounded, size: 28),
                            items: _selectedSectionId == null
                                ? []
                                : examState.exams.map((exam) {
                                    return DropdownMenuItem<int>(
                                      value: exam.id,
                                      child: Text(exam.name, style: const TextStyle(fontSize: 14)),
                                    );
                                  }).toList(),
                            onChanged: _selectedSectionId == null || _loadingExams
                                ? null
                                : (val) {
                                    if (val != null) {
                                      final examName = examState.exams.firstWhere((e) => e.id == val).name;
                                      _onExamChanged(val, examName);
                                    }
                                  },
                            validator: (val) => val == null ? "Please select an exam" : null,
                          ),
                          const SizedBox(height: 18),

                          // 4. Subject Dropdown
                          DropdownButtonFormField<int>(
                            value: _selectedSubjectId,
                            hint: _loadingSubjects
                                ? const Text("Loading subjects...")
                                : const Text("Select Subject"),
                            decoration: buildModernInputDecoration("Subject", Icons.book_outlined, isLoading: _loadingSubjects),
                            dropdownColor: theme.colorScheme.surface,
                            icon: _loadingSubjects
                                ? const SizedBox.shrink()
                                : const Icon(Icons.arrow_drop_down_rounded, size: 28),
                            items: _selectedExamId == null
                                ? []
                                : examState.subjects.map((sub) {
                                    return DropdownMenuItem<int>(
                                      value: sub['id'] as int,
                                      child: Text(sub['name'] as String, style: const TextStyle(fontSize: 14)),
                                    );
                                  }).toList(),
                            onChanged: _selectedExamId == null || _loadingSubjects
                                ? null
                                : (val) {
                                    if (val != null) {
                                      final selectedSub = examState.subjects.firstWhere((sub) => sub['id'] == val);
                                      setState(() {
                                        _selectedSubjectId = val;
                                        _selectedSubjectName = selectedSub['name'] as String;
                                      });
                                    }
                                  },
                            validator: (val) => val == null ? "Please select a subject" : null,
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 36),

                  // Load Marks Sheet Action Button
                  SizedBox(
                    height: 54,
                    child: CustomButton(
                      text: "Load Marks Sheet",
                      onPressed: () {
                        if (_formKey.currentState!.validate() &&
                            _selectedClassId != null &&
                            _selectedSectionId != null &&
                            _selectedExamId != null &&
                            _selectedSubjectId != null) {
                          Navigator.of(context).push(
                            MaterialPageRoute(
                              builder: (_) => ExamMarksEntryScreen(
                                examId: _selectedExamId!,
                                examName: _selectedExamName!,
                                classId: _selectedClassId!,
                                sectionId: _selectedSectionId!,
                                subjectId: _selectedSubjectId!,
                                className: _selectedClassName!,
                                sectionName: _selectedSectionName!,
                                subjectName: _selectedSubjectName!,
                              ),
                            ),
                          );
                        }
                      },
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

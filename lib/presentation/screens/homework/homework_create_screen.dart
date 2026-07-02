import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../providers/homework_provider.dart';
import '../../../providers/dashboard_provider.dart';
import '../../../data/models/homework.dart';
import '../../widgets/custom_button.dart';

class HomeworkCreateScreen extends ConsumerStatefulWidget {
  const HomeworkCreateScreen({super.key});

  @override
  ConsumerState<HomeworkCreateScreen> createState() => _HomeworkCreateScreenState();
}

class _HomeworkCreateScreenState extends ConsumerState<HomeworkCreateScreen> {
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();
  final _descController = TextEditingController();
  final _marksController = TextEditingController();

  int? _selectedClassId;
  int? _selectedSectionId;
  int? _selectedSubjectId;
  
  DateTime? _submissionDate;

  @override
  void dispose() {
    _titleController.dispose();
    _descController.dispose();
    _marksController.dispose();
    super.dispose();
  }

  Future<void> _selectDate(BuildContext context) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now().add(const Duration(days: 1)),
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 90)),
    );
    if (picked != null) {
      setState(() {
        _submissionDate = picked;
      });
    }
  }

  void _handleCreate() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedClassId == null || _selectedSectionId == null || _selectedSubjectId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Please select class, section, and subject")),
      );
      return;
    }
    if (_submissionDate == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Please select a submission date")),
      );
      return;
    }

    final newHomework = HomeworkModel(
      id: 0,
      classId: _selectedClassId!,
      sectionId: _selectedSectionId!,
      subjectId: _selectedSubjectId!,
      title: _titleController.text.trim(),
      description: _descController.text.trim(),
      submissionDate: DateFormat('yyyy-MM-dd').format(_submissionDate!),
      maxMarks: _marksController.text.isNotEmpty ? double.tryParse(_marksController.text) : null,
    );

    final success = await ref.read(homeworkProvider.notifier).createHomework(newHomework);

    if (mounted) {
      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text("Homework created successfully"), backgroundColor: Colors.green),
        );
        Navigator.of(context).pop();
      } else {
        final err = ref.read(homeworkProvider).errorMessage;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(err ?? "Failed to create homework"), backgroundColor: Colors.red),
        );
      }
    }
  }

  void _onClassChanged(int? classId) {
    setState(() {
      _selectedClassId = classId;
      _selectedSectionId = null;
      _selectedSubjectId = null;
    });
  }

  void _onSectionChanged(int? sectionId) {
    setState(() {
      _selectedSectionId = sectionId;
      _selectedSubjectId = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final homeworkState = ref.watch(homeworkProvider);
    final dashboardState = ref.watch(dashboardProvider);
    final assignments = dashboardState.assignments;

    // 1. Get unique classes
    final classesMap = <int, String>{};
    for (var a in assignments) {
      classesMap[a.classId] = a.className.name;
    }

    // 2. Get sections for selected class
    final sectionsMap = <int, String>{};
    if (_selectedClassId != null) {
      for (var a in assignments) {
        if (a.classId == _selectedClassId) {
          sectionsMap[a.sectionId] = a.sectionName.name;
        }
      }
    }

    // 3. Get subjects for selected class & section
    final subjectsMap = <int, String>{};
    if (_selectedClassId != null && _selectedSectionId != null) {
      for (var a in assignments) {
        if (a.classId == _selectedClassId && a.sectionId == _selectedSectionId) {
          if (a.subjectId > 0 && a.subjectName.name.trim().isNotEmpty) {
            subjectsMap[a.subjectId] = a.subjectName.name.trim();
          }
        }
      }
    }

    final displayDate = _submissionDate != null
        ? DateFormat('MMMM d, y').format(_submissionDate!)
        : "Select Date";

    // Modern input decoration style helper
    InputDecoration buildModernInputDecoration(String labelText, IconData icon) {
      return InputDecoration(
        labelText: labelText,
        labelStyle: TextStyle(color: theme.colorScheme.onSurface.withOpacity(0.6), fontSize: 13),
        prefixIcon: Icon(icon, color: theme.colorScheme.primary, size: 20),
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
        title: const Text("Create Homework"),
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
                    onChanged: (val) => _onClassChanged(val),
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
                    onChanged: _selectedClassId == null ? null : (val) => _onSectionChanged(val),
                    validator: (val) => val == null ? "Please select a section" : null,
                  ),
                  const SizedBox(height: 18),

                  // 3. Subject Dropdown
                  DropdownButtonFormField<int>(
                    value: _selectedSubjectId,
                    hint: const Text("Select Subject"),
                    decoration: buildModernInputDecoration("Subject", Icons.book_outlined),
                    dropdownColor: theme.colorScheme.surface,
                    icon: const Icon(Icons.arrow_drop_down_rounded, size: 28),
                    items: _selectedSectionId == null
                        ? []
                        : subjectsMap.entries.map((entry) {
                            return DropdownMenuItem<int>(
                              value: entry.key,
                              child: Text(entry.value, style: const TextStyle(fontSize: 14)),
                            );
                          }).toList(),
                    onChanged: _selectedSectionId == null
                        ? null
                        : (val) {
                            setState(() {
                              _selectedSubjectId = val;
                            });
                          },
                    validator: (val) => val == null ? "Please select a subject" : null,
                  ),
                  const SizedBox(height: 24),

                  // Title Input
                  TextFormField(
                    controller: _titleController,
                    decoration: buildModernInputDecoration("Homework Title", Icons.title_rounded),
                    style: const TextStyle(fontSize: 14),
                    validator: (val) {
                      if (val == null || val.trim().isEmpty) {
                        return "Title is required";
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 18),

                  // Description Input
                  TextFormField(
                    controller: _descController,
                    maxLines: 5,
                    decoration: buildModernInputDecoration("Instructions / Description", Icons.description_outlined).copyWith(
                      alignLabelWithHint: true,
                      prefixIcon: const Padding(
                        padding: EdgeInsets.only(bottom: 80.0),
                        child: Icon(Icons.description_outlined, color: Colors.blue, size: 20),
                      ),
                    ),
                    style: const TextStyle(fontSize: 14),
                    validator: (val) {
                      if (val == null || val.trim().isEmpty) {
                        return "Description is required";
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 18),

                  // Max Marks Input
                  TextFormField(
                    controller: _marksController,
                    decoration: buildModernInputDecoration("Maximum Marks (Optional)", Icons.score_outlined),
                    keyboardType: TextInputType.number,
                    style: const TextStyle(fontSize: 14),
                    validator: (val) {
                      if (val != null && val.isNotEmpty) {
                        if (double.tryParse(val) == null) {
                          return "Enter a valid number";
                        }
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 18),

                  // Submission Date Selector
                  InkWell(
                    onTap: () => _selectDate(context),
                    borderRadius: BorderRadius.circular(14),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                      decoration: BoxDecoration(
                        color: theme.colorScheme.surfaceContainerLow,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: theme.colorScheme.outline.withOpacity(0.08)),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Row(
                            children: [
                              Icon(Icons.calendar_month_outlined, color: theme.colorScheme.primary, size: 20),
                              const SizedBox(width: 12),
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    "Submission Due Date",
                                    style: TextStyle(fontSize: 10, color: theme.colorScheme.onSurface.withOpacity(0.5)),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    displayDate,
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 14,
                                      color: theme.colorScheme.onSurface,
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                          Icon(Icons.chevron_right_rounded, color: theme.colorScheme.onSurface.withOpacity(0.4)),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 32),

                  // Create button
                  SizedBox(
                    height: 52,
                    child: CustomButton(
                      text: "Create & Publish",
                      isLoading: homeworkState.isSaving,
                      onPressed: _handleCreate,
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

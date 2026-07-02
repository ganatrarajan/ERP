import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../providers/exam_provider.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/error_view.dart';
import '../../widgets/empty_view.dart';
import '../../widgets/custom_button.dart';

class ExamMarksEntryScreen extends ConsumerStatefulWidget {
  final int examId;
  final String examName;
  final int classId;
  final int sectionId;
  final int subjectId;
  final String className;
  final String sectionName;
  final String subjectName;

  const ExamMarksEntryScreen({
    super.key,
    required this.examId,
    required this.examName,
    required this.classId,
    required this.sectionId,
    required this.subjectId,
    required this.className,
    required this.sectionName,
    required this.subjectName,
  });

  @override
  ConsumerState<ExamMarksEntryScreen> createState() => _ExamMarksEntryScreenState();
}

class _ExamMarksEntryScreenState extends ConsumerState<ExamMarksEntryScreen> {
  final _formKey = GlobalKey<FormState>();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(examProvider.notifier).fetchMarksSheet(
            examId: widget.examId,
            classId: widget.classId,
            sectionId: widget.sectionId,
            subjectId: widget.subjectId,
          );
    });
  }

  void _handleSave() async {
    if (!_formKey.currentState!.validate()) return;

    final success = await ref.read(examProvider.notifier).saveExamMarks(
          examId: widget.examId,
          subjectId: widget.subjectId,
        );

    if (mounted) {
      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text("Marks saved successfully"), backgroundColor: Colors.green),
        );
        Navigator.of(context).pop();
      } else {
        final err = ref.read(examProvider).errorMessage;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(err ?? "Failed to save marks"), backgroundColor: Colors.red),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final state = ref.watch(examProvider);
    final maxMarks = state.schedule?.maxMarks ?? 100.0;

    return Scaffold(
      appBar: AppBar(
        title: const Text("Marks Entry Table"),
      ),
      body: Column(
        children: [
          // Banner for locked parameter configurations
          Container(
            padding: const EdgeInsets.all(16),
            color: theme.colorScheme.primary.withOpacity(0.04),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      "${widget.className} - ${widget.sectionName}",
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    ),
                    Text(
                      widget.subjectName,
                      style: TextStyle(
                        color: theme.colorScheme.primary,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      "Exam: ${widget.examName}",
                      style: TextStyle(color: theme.colorScheme.onSurface.withOpacity(0.6), fontSize: 12),
                    ),
                    Text(
                      "Max Marks: ${maxMarks.toInt()}",
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
                    ),
                  ],
                ),
              ],
            ),
          ),

          Expanded(
            child: state.isLoading
                ? const LoadingView(message: "Loading student marks list...")
                : state.errorMessage != null
                    ? ErrorView(
                        message: state.errorMessage!,
                        onRetry: () => ref.read(examProvider.notifier).fetchMarksSheet(
                              examId: widget.examId,
                              classId: widget.classId,
                              sectionId: widget.sectionId,
                              subjectId: widget.subjectId,
                            ),
                      )
                    : state.students.isEmpty
                        ? const EmptyView(
                            title: "No Students Found",
                            description: "No active student academic records match this configuration.",
                            icon: Icons.people_outline,
                          )
                        : Form(
                            key: _formKey,
                            child: ListView.builder(
                              padding: const EdgeInsets.all(16),
                              itemCount: state.students.length,
                              itemBuilder: (context, index) {
                                final student = state.students[index];

                                return Card(
                                  margin: const EdgeInsets.only(bottom: 14),
                                  child: Padding(
                                    padding: const EdgeInsets.all(16.0),
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Row(
                                          children: [
                                            CircleAvatar(
                                              radius: 16,
                                              backgroundColor: theme.colorScheme.primary.withOpacity(0.08),
                                              child: Text(
                                                student.rollNo.isNotEmpty ? student.rollNo : "?",
                                                style: TextStyle(
                                                  color: theme.colorScheme.primary,
                                                  fontWeight: FontWeight.bold,
                                                  fontSize: 11,
                                                ),
                                              ),
                                            ),
                                            const SizedBox(width: 12),
                                            Expanded(
                                              child: Column(
                                                crossAxisAlignment: CrossAxisAlignment.start,
                                                children: [
                                                  Text(
                                                    student.name,
                                                    style: const TextStyle(fontWeight: FontWeight.bold),
                                                  ),
                                                  Text(
                                                    "Adm No: ${student.admissionNo}",
                                                    style: TextStyle(
                                                      fontSize: 11,
                                                      color: theme.colorScheme.onSurface.withOpacity(0.5),
                                                    ),
                                                  ),
                                                ],
                                              ),
                                            ),
                                          ],
                                        ),
                                        const SizedBox(height: 16),
                                        Row(
                                          children: [
                                            // Score input field
                                            Expanded(
                                              child: TextFormField(
                                                key: ValueKey("marks_${student.studentId}_${student.isAbsent}"),
                                                initialValue: student.marksObtained != null ? "${student.marksObtained!.toInt()}" : "",
                                                enabled: !student.isAbsent,
                                                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                                decoration: const InputDecoration(
                                                  labelText: "Marks Obtained",
                                                  contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                                                ),
                                                validator: (val) {
                                                  if (student.isAbsent) return null;
                                                  if (val == null || val.isEmpty) {
                                                    return "Required";
                                                  }
                                                  final numVal = double.tryParse(val);
                                                  if (numVal == null) {
                                                    return "Invalid";
                                                  }
                                                  if (numVal < 0 || numVal > maxMarks) {
                                                    return "0 - ${maxMarks.toInt()}";
                                                  }
                                                  return null;
                                                },
                                                onChanged: (val) {
                                                  final marks = double.tryParse(val);
                                                  ref.read(examProvider.notifier).updateStudentMarks(
                                                        student.studentId,
                                                        marks,
                                                      );
                                                },
                                              ),
                                            ),
                                            const SizedBox(width: 8),
                                            // "/ 100" label
                                            Text(
                                              "/ ${maxMarks.toInt()}",
                                              style: TextStyle(
                                                fontSize: 14,
                                                fontWeight: FontWeight.bold,
                                                color: theme.colorScheme.onSurface.withOpacity(0.5),
                                              ),
                                            ),
                                            const SizedBox(width: 24),

                                            // Absent switch
                                            Column(
                                              children: [
                                                const Text("Absent", style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.grey)),
                                                const SizedBox(height: 4),
                                                Switch(
                                                  value: student.isAbsent,
                                                  activeColor: Colors.red,
                                                  onChanged: (val) {
                                                    ref.read(examProvider.notifier).updateStudentAbsence(
                                                          student.studentId,
                                                          val,
                                                        );
                                                  },
                                                ),
                                              ],
                                            ),
                                          ],
                                        ),
                                        const SizedBox(height: 12),
                                        TextFormField(
                                          initialValue: student.remarks,
                                          decoration: const InputDecoration(
                                            labelText: "Remarks (optional)",
                                            contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                                            prefixIcon: Icon(Icons.comment_outlined, size: 16),
                                          ),
                                          onChanged: (val) {
                                            ref.read(examProvider.notifier).updateStudentRemarks(
                                                  student.studentId,
                                                  val,
                                                );
                                          },
                                        ),
                                      ],
                                    ),
                                  ),
                                );
                              },
                            ),
                          ),
          ),

          if (!state.isLoading && state.errorMessage == null && state.students.isNotEmpty)
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: theme.cardTheme.color,
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.05),
                    blurRadius: 10,
                    offset: const Offset(0, -3),
                  ),
                ],
              ),
              child: SizedBox(
                width: double.infinity,
                height: 54,
                child: CustomButton(
                  text: "Submit Marks Sheet",
                  isLoading: state.isSaving,
                  onPressed: _handleSave,
                ),
              ),
            ),
        ],
      ),
    );
  }
}

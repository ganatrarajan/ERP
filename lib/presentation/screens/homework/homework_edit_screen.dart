import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../providers/homework_provider.dart';
import '../../../data/models/homework.dart';
import '../../widgets/custom_button.dart';
import '../../widgets/custom_text_field.dart';

class HomeworkEditScreen extends ConsumerStatefulWidget {
  final HomeworkModel homework;
  const HomeworkEditScreen({super.key, required this.homework});

  @override
  ConsumerState<HomeworkEditScreen> createState() => _HomeworkEditScreenState();
}

class _HomeworkEditScreenState extends ConsumerState<HomeworkEditScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _titleController;
  late TextEditingController _descController;
  late TextEditingController _marksController;
  
  DateTime? _submissionDate;

  @override
  void initState() {
    super.initState();
    _titleController = TextEditingController(text: widget.homework.title);
    _descController = TextEditingController(text: widget.homework.description);
    _marksController = TextEditingController(
      text: widget.homework.maxMarks != null ? "${widget.homework.maxMarks!.toInt()}" : "",
    );
    _submissionDate = DateTime.tryParse(widget.homework.submissionDate);
  }

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
      initialDate: _submissionDate ?? DateTime.now().add(const Duration(days: 1)),
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 90)),
    );
    if (picked != null) {
      setState(() {
        _submissionDate = picked;
      });
    }
  }

  void _handleUpdate() async {
    if (!_formKey.currentState!.validate()) return;
    if (_submissionDate == null) return;

    final dateStr = DateFormat('yyyy-MM-dd').format(_submissionDate!);
    final double? maxMarks = _marksController.text.isNotEmpty ? double.tryParse(_marksController.text) : null;

    final success = await ref.read(homeworkProvider.notifier).updateHomework(
          widget.homework.id,
          title: _titleController.text.trim(),
          description: _descController.text.trim(),
          submissionDate: dateStr,
          maxMarks: maxMarks,
        );

    if (mounted) {
      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text("Homework updated successfully"), backgroundColor: Colors.green),
        );
        Navigator.of(context).pop();
      } else {
        final err = ref.read(homeworkProvider).errorMessage;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(err ?? "Failed to update homework"), backgroundColor: Colors.red),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final homeworkState = ref.watch(homeworkProvider);

    final displayDate = _submissionDate != null
        ? DateFormat('MMMM d, y').format(_submissionDate!)
        : "Select Date";

    return Scaffold(
      appBar: AppBar(
        title: const Text("Edit Homework"),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20.0),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Roster Info display (Unmodifiable)
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: theme.colorScheme.primary.withOpacity(0.04),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  "Editing homework for: ${widget.homework.className?.name ?? ''} - ${widget.homework.sectionName?.name ?? ''} (${widget.homework.subjectName?.name ?? ''})",
                  style: TextStyle(color: theme.colorScheme.primary, fontSize: 13, fontWeight: FontWeight.bold),
                ),
              ),
              const SizedBox(height: 24),

              // Title
              CustomTextField(
                controller: _titleController,
                label: "Homework Title",
                prefixIcon: Icons.title_rounded,
                validator: (val) {
                  if (val == null || val.trim().isEmpty) {
                    return "Title is required";
                  }
                  return null;
                },
              ),
              const SizedBox(height: 20),

              // Description
              TextFormField(
                controller: _descController,
                maxLines: 5,
                decoration: const InputDecoration(
                  labelText: "Instructions / Description",
                  alignLabelWithHint: true,
                  prefixIcon: Padding(
                    padding: EdgeInsets.only(bottom: 80.0),
                    child: Icon(Icons.description_outlined),
                  ),
                ),
                validator: (val) {
                  if (val == null || val.trim().isEmpty) {
                    return "Description is required";
                  }
                  return null;
                },
              ),
              const SizedBox(height: 20),

              // Max Marks
              CustomTextField(
                controller: _marksController,
                label: "Maximum Marks (Optional)",
                prefixIcon: Icons.score_outlined,
                keyboardType: TextInputType.number,
                validator: (val) {
                  if (val != null && val.isNotEmpty) {
                    if (double.tryParse(val) == null) {
                      return "Enter a valid number";
                    }
                  }
                  return null;
                },
              ),
              const SizedBox(height: 20),

              // Submission Date Selector
              InkWell(
                onTap: () => _selectDate(context),
                borderRadius: BorderRadius.circular(12),
                child: Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    border: Border.all(color: theme.colorScheme.surfaceContainerHighest),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          Icon(Icons.calendar_month_outlined, color: theme.colorScheme.primary),
                          const SizedBox(width: 12),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                "Submission Due Date",
                                style: TextStyle(fontSize: 10, color: Colors.grey),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                displayDate,
                                style: const TextStyle(fontWeight: FontWeight.bold),
                              ),
                            ],
                          ),
                        ],
                      ),
                      const Icon(Icons.chevron_right_rounded),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 40),

              // Save button
              CustomButton(
                text: "Save Changes",
                isLoading: homeworkState.isSaving,
                onPressed: _handleUpdate,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

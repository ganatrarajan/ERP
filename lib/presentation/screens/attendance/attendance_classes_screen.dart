import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../providers/attendance_provider.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/error_view.dart';
import '../../widgets/empty_view.dart';
import 'attendance_students_screen.dart';

class AttendanceClassesScreen extends ConsumerStatefulWidget {
  const AttendanceClassesScreen({super.key});

  @override
  ConsumerState<AttendanceClassesScreen> createState() => _AttendanceClassesScreenState();
}

class _AttendanceClassesScreenState extends ConsumerState<AttendanceClassesScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(attendanceProvider.notifier).fetchClasses();
    });
  }

  Future<void> _selectDate(BuildContext context) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: DateTime.parse(ref.read(attendanceProvider).selectedDate),
      firstDate: DateTime.now().subtract(const Duration(days: 365)),
      lastDate: DateTime.now().add(const Duration(days: 30)),
    );
    if (picked != null) {
      final dateStr = DateFormat('yyyy-MM-dd').format(picked);
      ref.read(attendanceProvider.notifier).updateDate(dateStr);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final attendanceState = ref.watch(attendanceProvider);
    final displayDate = DateFormat('EEEE, MMM d, yyyy').format(DateTime.parse(attendanceState.selectedDate));

    return Scaffold(
      appBar: AppBar(
        title: const Text("Select Class"),
      ),
      body: Column(
        children: [
          // Date Selector Header
          Container(
            padding: const EdgeInsets.all(16),
            color: theme.colorScheme.primary.withOpacity(0.05),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      "Attendance Date",
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: theme.colorScheme.onSurface.withOpacity(0.6),
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      displayDate,
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                        color: theme.colorScheme.primary,
                      ),
                    ),
                  ],
                ),
                OutlinedButton.icon(
                  onPressed: () => _selectDate(context),
                  icon: const Icon(Icons.calendar_month_rounded, size: 18),
                  label: const Text("Change"),
                  style: OutlinedButton.styleFrom(
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                ),
              ],
            ),
          ),
          
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => ref.read(attendanceProvider.notifier).fetchClasses(),
              child: attendanceState.isLoading && attendanceState.classes.isEmpty
                  ? const LoadingView(message: "Loading assigned classes...")
                  : attendanceState.errorMessage != null && attendanceState.classes.isEmpty
                      ? ErrorView(
                          message: attendanceState.errorMessage!,
                          onRetry: () => ref.read(attendanceProvider.notifier).fetchClasses(),
                        )
                      : attendanceState.classes.isEmpty
                          ? const EmptyView(
                              title: "No Assigned Classes",
                              description: "You have no classes assigned for attendance.",
                              icon: Icons.assignment_outlined,
                            )
                          : ListView.builder(
                              padding: const EdgeInsets.all(16),
                              itemCount: attendanceState.classes.length,
                              itemBuilder: (context, index) {
                                final item = attendanceState.classes[index];
                                return Card(
                                  margin: const EdgeInsets.only(bottom: 12),
                                  child: ListTile(
                                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                    leading: CircleAvatar(
                                      backgroundColor: theme.colorScheme.primary.withOpacity(0.1),
                                      child: Icon(Icons.class_rounded, color: theme.colorScheme.primary),
                                    ),
                                    title: Text(
                                      "${item.className.name} - ${item.sectionName.name}",
                                      style: const TextStyle(fontWeight: FontWeight.bold),
                                    ),
                                    subtitle: Text(
                                      item.isClassTeacher ? "Class Teacher" : "Assigned Teacher",
                                      style: TextStyle(
                                        color: item.isClassTeacher ? theme.colorScheme.secondary : Colors.grey,
                                        fontSize: 12,
                                        fontWeight: item.isClassTeacher ? FontWeight.bold : FontWeight.normal,
                                      ),
                                    ),
                                    trailing: const Icon(Icons.chevron_right_rounded),
                                    onTap: () {
                                      Navigator.of(context).push(
                                        MaterialPageRoute(
                                          builder: (_) => AttendanceStudentsScreen(
                                            classId: item.classId,
                                            sectionId: item.sectionId,
                                            className: item.className.name,
                                            sectionName: item.sectionName.name,
                                          ),
                                        ),
                                      );
                                    },
                                  ),
                                );
                              },
                            ),
            ),
          ),
        ],
      ),
    );
  }
}

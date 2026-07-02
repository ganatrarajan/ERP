import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../providers/attendance_provider.dart';
import '../../../core/constants/app_colors.dart';
import '../../../data/models/student_attendance.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/error_view.dart';
import '../../widgets/empty_view.dart';
import '../../widgets/custom_button.dart';
import 'monthly_grid_screen.dart';

class AttendanceStudentsScreen extends ConsumerStatefulWidget {
  final int classId;
  final int sectionId;
  final String className;
  final String sectionName;

  const AttendanceStudentsScreen({
    super.key,
    required this.classId,
    required this.sectionId,
    required this.className,
    required this.sectionName,
  });

  @override
  ConsumerState<AttendanceStudentsScreen> createState() => _AttendanceStudentsScreenState();
}

class _AttendanceStudentsScreenState extends ConsumerState<AttendanceStudentsScreen> {
  final _searchController = TextEditingController();
  String _searchQuery = "";

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(attendanceProvider.notifier).fetchStudents(
            classId: widget.classId,
            sectionId: widget.sectionId,
          );
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _handleSave() async {
    final success = await ref.read(attendanceProvider.notifier).saveAttendance(
          classId: widget.classId,
          sectionId: widget.sectionId,
        );

    if (mounted) {
      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text("Attendance saved successfully"),
            backgroundColor: Colors.green,
            behavior: SnackBarBehavior.floating,
          ),
        );
        Navigator.of(context).pop();
      } else {
        final err = ref.read(attendanceProvider).errorMessage;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(err ?? "Failed to save attendance"),
            backgroundColor: Theme.of(context).colorScheme.error,
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final attendanceState = ref.watch(attendanceProvider);
    final displayDate = DateFormat('MMM d, y').format(DateTime.parse(attendanceState.selectedDate));

    final filteredStudents = attendanceState.students.where((student) {
      final name = student.name.toLowerCase();
      final roll = student.rollNo.toLowerCase();
      final query = _searchQuery.toLowerCase();
      return name.contains(query) || roll.contains(query);
    }).toList();

    return Scaffold(
      appBar: AppBar(
        title: Text("${widget.className} - ${widget.sectionName}"),
        actions: [
          IconButton(
            icon: const Icon(Icons.grid_on_rounded),
            tooltip: "Monthly Matrix",
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) => MonthlyGridScreen(
                    classId: widget.classId,
                    sectionId: widget.sectionId,
                    className: widget.className,
                    sectionName: widget.sectionName,
                  ),
                ),
              );
            },
          ),
        ],
      ),
      body: Column(
        children: [
          // Class Stats Header
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            color: theme.colorScheme.primary.withOpacity(0.04),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  "Date: $displayDate",
                  style: const TextStyle(fontWeight: FontWeight.bold),
                ),
                Text(
                  "Total: ${attendanceState.students.length} Students",
                  style: TextStyle(color: theme.colorScheme.primary, fontWeight: FontWeight.bold),
                ),
              ],
            ),
          ),

          // Search Bar & Mark All Present Row
          Padding(
            padding: const EdgeInsets.all(12.0),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _searchController,
                    decoration: InputDecoration(
                      hintText: "Search student...",
                      prefixIcon: const Icon(Icons.search),
                      contentPadding: const EdgeInsets.symmetric(vertical: 0, horizontal: 16),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(10),
                        borderSide: BorderSide(color: theme.colorScheme.surfaceContainerHighest),
                      ),
                    ),
                    onChanged: (val) {
                      setState(() {
                        _searchQuery = val;
                      });
                    },
                  ),
                ),
                const SizedBox(width: 12),
                ElevatedButton.icon(
                  onPressed: ref.read(attendanceProvider.notifier).markAllPresent,
                  icon: const Icon(Icons.done_all_rounded, size: 18),
                  label: const Text("All Present"),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.green.shade800,
                    foregroundColor: Colors.white,
                    minimumSize: const Size(110, 42),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    padding: const EdgeInsets.symmetric(horizontal: 8),
                  ),
                ),
              ],
            ),
          ),

          Expanded(
            child: attendanceState.isLoading
                ? const LoadingView(message: "Fetching class roster...")
                : attendanceState.errorMessage != null
                    ? ErrorView(
                        message: attendanceState.errorMessage!,
                        onRetry: () => ref.read(attendanceProvider.notifier).fetchStudents(
                              classId: widget.classId,
                              sectionId: widget.sectionId,
                            ),
                      )
                    : filteredStudents.isEmpty
                        ? const EmptyView(
                            title: "No Students Found",
                            description: "No student matching the query exists in this class section.",
                            icon: Icons.person_search_rounded,
                          )
                        : ListView.builder(
                            padding: const EdgeInsets.all(12),
                            itemCount: filteredStudents.length,
                            itemBuilder: (context, index) {
                              final student = filteredStudents[index];
                              return Card(
                                margin: const EdgeInsets.only(bottom: 12),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12),
                                  side: BorderSide(
                                    color: student.attendanceStatus == 'Absent'
                                        ? AppColors.error.withOpacity(0.3)
                                        : theme.colorScheme.surfaceContainerHighest,
                                  ),
                                ),
                                child: Padding(
                                  padding: const EdgeInsets.all(12.0),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        children: [
                                          CircleAvatar(
                                            radius: 18,
                                            backgroundColor: theme.colorScheme.primary.withOpacity(0.1),
                                            child: Text(
                                              student.rollNo.isNotEmpty ? student.rollNo : "?",
                                              style: TextStyle(
                                                color: theme.colorScheme.primary,
                                                fontWeight: FontWeight.bold,
                                                fontSize: 12,
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
                                                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                                                ),
                                                Text(
                                                  student.admissionNo,
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
                                      const SizedBox(height: 12),
                                      if (student.isHoliday)
                                        Container(
                                          padding: const EdgeInsets.all(8),
                                          width: double.infinity,
                                          decoration: BoxDecoration(
                                            color: AppColors.holiday.withOpacity(0.1),
                                            borderRadius: BorderRadius.circular(8),
                                          ),
                                          child: Text(
                                            student.remarks.isNotEmpty ? student.remarks : "School Holiday",
                                            style: const TextStyle(color: AppColors.holiday, fontWeight: FontWeight.bold, fontSize: 12),
                                            textAlign: TextAlign.center,
                                          ),
                                        )
                                      else
                                        Row(
                                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                          children: [
                                            _buildStatusButton("Present", Colors.green, student),
                                            _buildStatusButton("Absent", Colors.red, student),
                                            _buildStatusButton("Late", Colors.orange, student),
                                            _buildStatusButton("Leave", Colors.blue, student),
                                          ],
                                        ),
                                    ],
                                  ),
                                ),
                              );
                            },
                          ),
          ),
          
          if (!attendanceState.isLoading && attendanceState.errorMessage == null && filteredStudents.isNotEmpty)
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
                  text: "Submit Attendance",
                  isLoading: attendanceState.isSaving,
                  onPressed: _handleSave,
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildStatusButton(String status, Color color, StudentAttendanceModel student) {
    final isSelected = student.attendanceStatus == status;
    return Expanded(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 4.0),
        child: InkWell(
          onTap: () {
            ref.read(attendanceProvider.notifier).updateStudentStatus(student.studentId, status);
          },
          borderRadius: BorderRadius.circular(8),
          child: Container(
            padding: const EdgeInsets.symmetric(vertical: 8),
            decoration: BoxDecoration(
              color: isSelected ? color : color.withOpacity(0.05),
              borderRadius: BorderRadius.circular(8),
              border: Border.all(
                color: isSelected ? color : color.withOpacity(0.2),
                width: 1,
              ),
            ),
            child: Text(
              status,
              style: TextStyle(
                color: isSelected ? Colors.white : color,
                fontWeight: FontWeight.bold,
                fontSize: 12,
              ),
              textAlign: TextAlign.center,
            ),
          ),
        ),
      ),
    );
  }
}

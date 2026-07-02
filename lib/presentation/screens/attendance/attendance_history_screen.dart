import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../providers/attendance_provider.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/error_view.dart';
import '../../widgets/empty_view.dart';

class AttendanceHistoryScreen extends ConsumerStatefulWidget {
  final int classId;
  final int sectionId;
  final String className;
  final String sectionName;

  const AttendanceHistoryScreen({
    super.key,
    required this.classId,
    required this.sectionId,
    required this.className,
    required this.sectionName,
  });

  @override
  ConsumerState<AttendanceHistoryScreen> createState() => _AttendanceHistoryScreenState();
}

class _AttendanceHistoryScreenState extends ConsumerState<AttendanceHistoryScreen> {
  DateTime _startDate = DateTime.now().subtract(const Duration(days: 7));
  DateTime _endDate = DateTime.now();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _fetchHistory();
    });
  }

  void _fetchHistory() {
    final startStr = DateFormat('yyyy-MM-dd').format(_startDate);
    final endStr = DateFormat('yyyy-MM-dd').format(_endDate);
    ref.read(attendanceProvider.notifier).fetchHistory(
          classId: widget.classId,
          sectionId: widget.sectionId,
          startDate: startStr,
          endDate: endStr,
        );
  }

  Future<void> _selectStartDate(BuildContext context) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _startDate,
      firstDate: DateTime.now().subtract(const Duration(days: 365)),
      lastDate: DateTime.now(),
    );
    if (picked != null && picked != _startDate) {
      setState(() {
        _startDate = picked;
        if (_startDate.isAfter(_endDate)) {
          _endDate = _startDate;
        }
      });
      _fetchHistory();
    }
  }

  Future<void> _selectEndDate(BuildContext context) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _endDate,
      firstDate: _startDate,
      lastDate: DateTime.now(),
    );
    if (picked != null && picked != _endDate) {
      setState(() {
        _endDate = picked;
      });
      _fetchHistory();
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final attendanceState = ref.watch(attendanceProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text("Attendance History"),
      ),
      body: Column(
        children: [
          // Filter Header
          Container(
            padding: const EdgeInsets.all(12),
            color: theme.colorScheme.primary.withOpacity(0.04),
            child: Row(
              children: [
                Expanded(
                  child: InkWell(
                    onTap: () => _selectStartDate(context),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text("From Date", style: TextStyle(fontSize: 10, color: Colors.grey)),
                        const SizedBox(height: 4),
                        Text(
                          DateFormat('yyyy-MM-dd').format(_startDate),
                          style: TextStyle(color: theme.colorScheme.primary, fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                  ),
                ),
                const Icon(Icons.arrow_forward_rounded, color: Colors.grey, size: 16),
                const SizedBox(width: 8),
                Expanded(
                  child: InkWell(
                    onTap: () => _selectEndDate(context),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text("To Date", style: TextStyle(fontSize: 10, color: Colors.grey)),
                        const SizedBox(height: 4),
                        Text(
                          DateFormat('yyyy-MM-dd').format(_endDate),
                          style: TextStyle(color: theme.colorScheme.primary, fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),

          Expanded(
            child: attendanceState.isLoading
                ? const LoadingView(message: "Loading history log...")
                : attendanceState.errorMessage != null
                    ? ErrorView(
                        message: attendanceState.errorMessage!,
                        onRetry: _fetchHistory,
                      )
                    : attendanceState.history.isEmpty
                        ? const EmptyView(
                            title: "No Attendance Logs",
                            description: "No attendance was marked in the selected range.",
                            icon: Icons.history_edu_rounded,
                          )
                        : ListView.builder(
                            padding: const EdgeInsets.all(16),
                            itemCount: attendanceState.history.length,
                            itemBuilder: (context, index) {
                              final record = attendanceState.history[index];
                              final statusColor = record.status == 'Present'
                                  ? Colors.green
                                  : record.status == 'Absent'
                                      ? Colors.red
                                      : record.status == 'Late'
                                          ? Colors.orange
                                          : Colors.blue;

                              return Card(
                                margin: const EdgeInsets.only(bottom: 10),
                                child: ListTile(
                                  title: Text(
                                    "Date: ${record.attendanceDate}",
                                    style: const TextStyle(fontWeight: FontWeight.bold),
                                  ),
                                  subtitle: record.remarks != null && record.remarks!.isNotEmpty
                                      ? Text("Remarks: ${record.remarks}")
                                      : null,
                                  trailing: Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                    decoration: BoxDecoration(
                                      color: statusColor.withOpacity(0.1),
                                      borderRadius: BorderRadius.circular(6),
                                      border: Border.all(color: statusColor.withOpacity(0.3)),
                                    ),
                                    child: Text(
                                      record.status,
                                      style: TextStyle(
                                        color: statusColor,
                                        fontWeight: FontWeight.bold,
                                        fontSize: 12,
                                      ),
                                    ),
                                  ),
                                ),
                              );
                            },
                          ),
          ),
        ],
      ),
    );
  }
}

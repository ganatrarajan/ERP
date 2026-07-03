import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../providers/my_attendance_provider.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/empty_view.dart';
import '../../widgets/error_view.dart';

class MyAttendanceScreen extends ConsumerStatefulWidget {
  const MyAttendanceScreen({super.key});

  @override
  ConsumerState<MyAttendanceScreen> createState() => _MyAttendanceScreenState();
}

class _MyAttendanceScreenState extends ConsumerState<MyAttendanceScreen> {
  DateTime _currentMonth = DateTime.now();

  void _changeMonth(int increment) {
    setState(() {
      _currentMonth = DateTime(_currentMonth.year, _currentMonth.month + increment);
    });
    final monthStr = DateFormat('yyyy-MM').format(_currentMonth);
    ref.read(myAttendanceProvider.notifier).fetchAttendance(monthStr);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final state = ref.watch(myAttendanceProvider);
    final monthDisplay = DateFormat('MMMM yyyy').format(_currentMonth);

    return Scaffold(
      appBar: AppBar(
        title: const Text("My Attendance"),
      ),
      body: Column(
        children: [
          // Month Selector Bar
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: theme.colorScheme.surface,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.05),
                  blurRadius: 4,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                IconButton(
                  icon: const Icon(Icons.chevron_left_rounded),
                  onPressed: () => _changeMonth(-1),
                ),
                Text(
                  monthDisplay,
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.chevron_right_rounded),
                  onPressed: _currentMonth.year == DateTime.now().year && _currentMonth.month == DateTime.now().month
                      ? null
                      : () => _changeMonth(1),
                ),
              ],
            ),
          ),

          // Main body
          Expanded(
            child: state.isLoading && state.records.isEmpty
                ? const LoadingView(message: "Loading attendance records...")
                : state.errorMessage != null && state.records.isEmpty
                    ? ErrorView(
                        message: state.errorMessage!,
                        onRetry: () {
                          final monthStr = DateFormat('yyyy-MM').format(_currentMonth);
                          ref.read(myAttendanceProvider.notifier).fetchAttendance(monthStr);
                        },
                      )
                    : RefreshIndicator(
                        onRefresh: () {
                          final monthStr = DateFormat('yyyy-MM').format(_currentMonth);
                          return ref.read(myAttendanceProvider.notifier).fetchAttendance(monthStr);
                        },
                        child: SingleChildScrollView(
                          physics: const AlwaysScrollableScrollPhysics(),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              // Attendance Stats Header (if stats exist)
                              if (state.stats != null) _buildStatsCard(context, state.stats!),

                              // Records List
                              if (state.records.isEmpty)
                                const Padding(
                                  padding: EdgeInsets.all(40.0),
                                  child: EmptyView(
                                    title: "No Attendance Found",
                                    description: "No attendance logs marked for this month.",
                                    icon: Icons.calendar_today_outlined,
                                  ),
                                )
                              else
                                Padding(
                                  padding: const EdgeInsets.symmetric(horizontal: 16.0),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        "Attendance Logs",
                                        style: theme.textTheme.titleMedium?.copyWith(
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                      const SizedBox(height: 12),
                                      ListView.builder(
                                        shrinkWrap: true,
                                        physics: const NeverScrollableScrollPhysics(),
                                        itemCount: state.records.length,
                                        itemBuilder: (context, index) {
                                          final record = state.records[index];
                                          return _buildRecordItem(context, record);
                                        },
                                      ),
                                      const SizedBox(height: 24),
                                    ],
                                  ),
                                ),
                            ],
                          ),
                        ),
                      ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatsCard(BuildContext context, dynamic stats) {
    final theme = Theme.of(context);
    final rate = stats.rate;

    return Card(
      margin: const EdgeInsets.all(16.0),
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: theme.colorScheme.outlineVariant),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        "Attendance Rate",
                        style: theme.textTheme.bodyMedium?.copyWith(color: Colors.grey),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        "${rate.toStringAsFixed(1)}%",
                        style: theme.textTheme.headlineMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                          color: rate >= 75 ? Colors.green : Colors.red,
                        ),
                      ),
                      const SizedBox(height: 8),
                      LinearProgressIndicator(
                        value: rate / 100,
                        backgroundColor: theme.colorScheme.surfaceContainerHighest,
                        color: rate >= 75 ? Colors.green : Colors.red,
                        borderRadius: BorderRadius.circular(4),
                        minHeight: 8,
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 24),
                // Circular rate indicator alternative or icon
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: theme.colorScheme.primaryContainer.withOpacity(0.3),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(Icons.donut_large_rounded, color: theme.colorScheme.primary, size: 36),
                ),
              ],
            ),
            const SizedBox(height: 16),
            const Divider(),
            const SizedBox(height: 8),
            // Stats Row
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _buildStatItem("Present", stats.present, Colors.green),
                _buildStatItem("Absent", stats.absent, Colors.red),
                _buildStatItem("Leave", stats.leave, Colors.blue),
                _buildStatItem("Late", stats.late, Colors.orange),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatItem(String label, int value, Color color) {
    return Column(
      children: [
        Text(
          value.toString(),
          style: TextStyle(
            fontSize: 20,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          label,
          style: const TextStyle(
            fontSize: 12,
            color: Colors.grey,
          ),
        ),
      ],
    );
  }

  Widget _buildRecordItem(BuildContext context, dynamic record) {
    final theme = Theme.of(context);
    final parsedDate = DateTime.tryParse(record.date) ?? DateTime.now();
    final dayName = DateFormat('EEEE').format(parsedDate);
    final dateStr = DateFormat('MMM d, yyyy').format(parsedDate);

    Color statusColor = Colors.grey;
    IconData statusIcon = Icons.help_outline;

    switch (record.status) {
      case 'Present':
        statusColor = Colors.green;
        statusIcon = Icons.check_circle_rounded;
        break;
      case 'Absent':
        statusColor = Colors.red;
        statusIcon = Icons.cancel_rounded;
        break;
      case 'Late':
        statusColor = Colors.orange;
        statusIcon = Icons.watch_later_rounded;
        break;
      case 'Half Day':
        statusColor = Colors.purple;
        statusIcon = Icons.hourglass_bottom_rounded;
        break;
      case 'Leave':
        statusColor = Colors.blue;
        statusIcon = Icons.flight_takeoff_rounded;
        break;
      case 'Holiday':
        statusColor = Colors.indigo;
        statusIcon = Icons.celebration_rounded;
        break;
      case 'Future':
        statusColor = Colors.grey;
        statusIcon = Icons.update_rounded;
        break;
      case 'Not Marked':
      default:
        statusColor = Colors.grey.shade400;
        statusIcon = Icons.remove_circle_outline_rounded;
        break;
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: statusColor.withOpacity(0.1),
            shape: BoxShape.circle,
          ),
          child: Icon(statusIcon, color: statusColor),
        ),
        title: Text(
          dateStr,
          style: const TextStyle(fontWeight: FontWeight.bold),
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(dayName, style: const TextStyle(color: Colors.grey, fontSize: 12)),
            if (record.remarks.isNotEmpty) ...[
              const SizedBox(height: 4),
              Text(
                "Remarks: ${record.remarks}",
                style: TextStyle(color: theme.colorScheme.onSurface.withOpacity(0.6), fontSize: 11),
              ),
            ],
          ],
        ),
        trailing: Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
          decoration: BoxDecoration(
            color: statusColor.withOpacity(0.1),
            borderRadius: BorderRadius.circular(6),
            border: Border.all(color: statusColor.withOpacity(0.3)),
          ),
          child: Text(
            record.status,
            style: TextStyle(color: statusColor, fontWeight: FontWeight.bold, fontSize: 12),
          ),
        ),
      ),
    );
  }
}

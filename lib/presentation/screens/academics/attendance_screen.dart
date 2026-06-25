import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:table_calendar/table_calendar.dart';
import 'package:intl/intl.dart';
import '../../../providers/attendance_provider.dart';
import '../../../data/models/attendance_model.dart';
import '../../../core/constants/app_colors.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/error_view.dart';

class AttendanceScreen extends StatefulWidget {
  const AttendanceScreen({super.key});

  @override
  State<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends State<AttendanceScreen> {
  CalendarFormat _calendarFormat = CalendarFormat.month;
  DateTime _focusedDay = DateTime.now();
  DateTime? _selectedDay;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<AttendanceProvider>().fetchAttendance();
    });
  }

  AttendanceRecord? _findRecord(DateTime day, List<AttendanceRecord> records) {
    for (var r in records) {
      try {
        final recDate = DateTime.parse(r.date);
        if (recDate.year == day.year && recDate.month == day.month && recDate.day == day.day) {
          return r;
        }
      } catch (_) {}
    }
    return null;
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'present':
        return AppColors.success;
      case 'absent':
        return AppColors.error;
      case 'late':
        return AppColors.warning;
      case 'leave':
        return AppColors.info;
      case 'half day':
        return AppColors.halfDay;
      case 'holiday':
      case 'h':
        return AppColors.holiday;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final attendanceProv = context.watch<AttendanceProvider>();

    return Scaffold(
      appBar: AppBar(
        title: const Text("Attendance Detail"),
      ),
      body: _buildBody(attendanceProv, theme),
    );
  }

  Widget _buildBody(AttendanceProvider prov, ThemeData theme) {
    if (prov.isLoading && prov.records.isEmpty) {
      return const LoadingView(message: "Loading attendance calendar...");
    }

    if (prov.errorMessage != null && prov.records.isEmpty) {
      return ErrorView(
        message: prov.errorMessage!,
        onRetry: () => prov.fetchAttendance(),
      );
    }

    final stats = prov.stats;

    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // 1. Calendar Widget
          Card(
            margin: const EdgeInsets.all(16),
            child: Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: TableCalendar(
                firstDay: DateTime.utc(DateTime.now().year - 2, 1, 1),
                lastDay: DateTime.utc(DateTime.now().year + 1, 12, 31),
                focusedDay: _focusedDay,
                calendarFormat: _calendarFormat,
                selectedDayPredicate: (day) => isSameDay(_selectedDay, day),
                onDaySelected: (selectedDay, focusedDay) {
                  setState(() {
                    _selectedDay = selectedDay;
                    _focusedDay = focusedDay;
                  });
                },
                onFormatChanged: (format) {
                  setState(() {
                    _calendarFormat = format;
                  });
                },
                onPageChanged: (focusedDay) {
                  _focusedDay = focusedDay;
                  prov.changeMonth(focusedDay);
                },
                headerStyle: const HeaderStyle(
                  formatButtonVisible: false,
                  titleCentered: true,
                ),
                calendarBuilders: CalendarBuilders(
                  defaultBuilder: (context, day, focusedDay) {
                    final record = _findRecord(day, prov.records);
                    if (record != null) {
                      final color = _getStatusColor(record.status);
                      return _buildCalendarDay(day, color, Colors.white, theme);
                    }
                    return null;
                  },
                  todayBuilder: (context, day, focusedDay) {
                    final record = _findRecord(day, prov.records);
                    final color = record != null ? _getStatusColor(record.status) : theme.colorScheme.primary.withOpacity(0.2);
                    return _buildCalendarDay(day, color, record != null ? Colors.white : theme.colorScheme.primary, theme, isToday: true);
                  },
                  selectedBuilder: (context, day, focusedDay) {
                    return _buildCalendarDay(day, theme.colorScheme.secondary, Colors.white, theme, isSelected: true);
                  },
                ),
              ),
            ),
          ),

          // 2. Metrics summary
          if (stats != null) ...[
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Text(
                "Summary (${DateFormat('MMMM yyyy').format(prov.selectedMonth)})",
                style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
              ),
            ),
            const SizedBox(height: 12),
            _buildStatsGrid(stats, theme),
            const SizedBox(height: 24),
          ],
        ],
      ),
    );
  }

  Widget _buildCalendarDay(
    DateTime day,
    Color bgColor,
    Color textColor,
    ThemeData theme, {
    bool isToday = false,
    bool isSelected = false,
  }) {
    return Container(
      margin: const EdgeInsets.all(4),
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(10),
        border: isToday
            ? Border.all(color: theme.colorScheme.primary, width: 1.5)
            : isSelected
                ? Border.all(color: theme.colorScheme.secondary, width: 1.5)
                : null,
      ),
      child: Text(
        '${day.day}',
        style: TextStyle(
          color: textColor,
          fontWeight: isToday || isSelected ? FontWeight.bold : FontWeight.normal,
        ),
      ),
    );
  }

  Widget _buildStatsGrid(AttendanceStats stats, ThemeData theme) {
    return GridView.count(
      crossAxisCount: 3,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      padding: const EdgeInsets.symmetric(horizontal: 16),
      crossAxisSpacing: 10,
      mainAxisSpacing: 10,
      childAspectRatio: 1.25,
      children: [
        _buildMiniStat("Present", "${stats.present}", AppColors.success),
        _buildMiniStat("Absent", "${stats.absent}", AppColors.error),
        _buildMiniStat("Late", "${stats.late}", AppColors.warning),
        _buildMiniStat("Leave", "${stats.leave}", AppColors.info),
        _buildMiniStat("Half Day", "${stats.halfDay}", AppColors.halfDay),
        _buildMiniStat("Holiday", "${stats.holiday}", AppColors.holiday),
        _buildMiniStat("Rate", "${stats.rate.toStringAsFixed(1)}%", theme.colorScheme.primary),
      ],
    );
  }

  Widget _buildMiniStat(String label, String value, Color color) {
    return Card(
      elevation: 0,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(color: color.withOpacity(0.2), width: 1),
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Text(
            value,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w800,
              color: color,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: const TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}

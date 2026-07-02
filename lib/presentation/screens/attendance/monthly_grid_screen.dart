import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../providers/attendance_provider.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/error_view.dart';
import '../../widgets/empty_view.dart';

class MonthlyGridScreen extends ConsumerStatefulWidget {
  final int classId;
  final int sectionId;
  final String className;
  final String sectionName;

  const MonthlyGridScreen({
    super.key,
    required this.classId,
    required this.sectionId,
    required this.className,
    required this.sectionName,
  });

  @override
  ConsumerState<MonthlyGridScreen> createState() => _MonthlyGridScreenState();
}

class _MonthlyGridScreenState extends ConsumerState<MonthlyGridScreen> {
  late DateTime _selectedMonth;

  @override
  void initState() {
    super.initState();
    _selectedMonth = DateTime.now();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _fetchGrid();
    });
  }

  void _fetchGrid() {
    final monthStr = DateFormat('yyyy-MM').format(_selectedMonth);
    ref.read(attendanceProvider.notifier).fetchMonthlyGrid(
          classId: widget.classId,
          sectionId: widget.sectionId,
          month: monthStr,
        );
  }

  void _previousMonth() {
    setState(() {
      _selectedMonth = DateTime(_selectedMonth.year, _selectedMonth.month - 1);
    });
    _fetchGrid();
  }

  void _nextMonth() {
    setState(() {
      _selectedMonth = DateTime(_selectedMonth.year, _selectedMonth.month + 1);
    });
    _fetchGrid();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final attendanceState = ref.watch(attendanceProvider);
    final displayMonth = DateFormat('MMMM yyyy').format(_selectedMonth);

    // Parse matrix items
    final matrix = attendanceState.monthlyGrid != null
        ? (attendanceState.monthlyGrid!['matrix'] as List? ?? [])
        : [];
    final daysInMonth = attendanceState.monthlyGrid != null
        ? (attendanceState.monthlyGrid!['days_in_month'] as int? ?? 30)
        : 30;

    return Scaffold(
      appBar: AppBar(
        title: const Text("Monthly Matrix"),
      ),
      body: Column(
        children: [
          // Month navigation header
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            color: theme.colorScheme.primary.withOpacity(0.04),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                IconButton(
                  icon: const Icon(Icons.chevron_left),
                  onPressed: _previousMonth,
                ),
                Text(
                  displayMonth,
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                    color: theme.colorScheme.primary,
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.chevron_right),
                  onPressed: _selectedMonth.year == DateTime.now().year && _selectedMonth.month == DateTime.now().month
                      ? null
                      : _nextMonth,
                ),
              ],
            ),
          ),

          Expanded(
            child: attendanceState.isLoading
                ? const LoadingView(message: "Loading monthly matrix...")
                : attendanceState.errorMessage != null
                    ? ErrorView(
                        message: attendanceState.errorMessage!,
                        onRetry: _fetchGrid,
                      )
                    : matrix.isEmpty
                        ? const EmptyView(
                            title: "No Records Found",
                            description: "No attendance matrix records found for this month.",
                            icon: Icons.grid_off_rounded,
                          )
                        : _buildMatrixTable(matrix, daysInMonth, theme),
          ),
        ],
      ),
    );
  }

  Widget _buildMatrixTable(List<dynamic> matrix, int daysInMonth, ThemeData theme) {
    return SingleChildScrollView(
      scrollDirection: Axis.vertical,
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: DataTable(
          columnSpacing: 16,
          headingRowHeight: 48,
          dataRowMinHeight: 48,
          dataRowMaxHeight: 48,
          columns: [
            const DataColumn(label: Text('Student', style: TextStyle(fontWeight: FontWeight.bold))),
            const DataColumn(label: Text('Present', style: TextStyle(fontWeight: FontWeight.bold))),
            const DataColumn(label: Text('Absent', style: TextStyle(fontWeight: FontWeight.bold))),
            ...List.generate(
              daysInMonth,
              (index) => DataColumn(
                label: Text(
                  '${index + 1}',
                  style: const TextStyle(fontWeight: FontWeight.bold),
                ),
              ),
            ),
          ],
          rows: matrix.map((row) {
            final name = row['name'] ?? '';
            final roll = row['roll_no']?.toString() ?? '';
            final stats = row['stats'] ?? {};
            final days = row['days'] ?? {};
            
            final presentCount = stats['Present'] ?? 0;
            final absentCount = stats['Absent'] ?? 0;

            return DataRow(
              cells: [
                DataCell(
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                      Text("Roll: $roll", style: TextStyle(fontSize: 10, color: theme.colorScheme.onSurface.withOpacity(0.5))),
                    ],
                  ),
                ),
                DataCell(Text('$presentCount', style: const TextStyle(color: Colors.green, fontWeight: FontWeight.bold))),
                DataCell(Text('$absentCount', style: const TextStyle(color: Colors.red, fontWeight: FontWeight.bold))),
                ...List.generate(
                  daysInMonth,
                  (index) {
                    final dayKey = '${index + 1}';
                    final status = days[dayKey] ?? '-';
                    return DataCell(
                      _buildDayIndicator(status),
                    );
                  },
                ),
              ],
            );
          }).toList(),
        ),
      ),
    );
  }

  Widget _buildDayIndicator(String status) {
    Color color = Colors.grey.shade400;
    String symbol = '-';
    
    if (status == 'Present') {
      color = Colors.green;
      symbol = 'P';
    } else if (status == 'Absent') {
      color = Colors.red;
      symbol = 'A';
    } else if (status == 'Late') {
      color = Colors.orange;
      symbol = 'L';
    } else if (status == 'Leave') {
      color = Colors.blue;
      symbol = 'LV';
    } else if (status == 'Holiday') {
      color = Colors.purple;
      symbol = 'H';
    }

    return Container(
      width: 24,
      height: 24,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: color.withOpacity(0.15),
        borderRadius: BorderRadius.circular(4),
        border: Border.all(color: color.withOpacity(0.4), width: 1),
      ),
      child: Text(
        symbol,
        style: TextStyle(
          color: color,
          fontWeight: FontWeight.bold,
          fontSize: 10,
        ),
      ),
    );
  }
}

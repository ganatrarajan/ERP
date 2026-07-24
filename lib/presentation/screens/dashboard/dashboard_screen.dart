import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../providers/dashboard_provider.dart';
import '../../../providers/auth_provider.dart';
import '../../../data/models/dashboard_model.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/error_view.dart';
import '../login_screen.dart';
import '../main_navigation_screen.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<DashboardProvider>().fetchDashboardData();
    });
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final dashboardProv = context.watch<DashboardProvider>();

    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text("EduvoraX"),
            if (dashboardProv.dashboardData != null)
              Text(
                "Academic Year: ${dashboardProv.dashboardData!.mobileAcademicYear}",
                style: const TextStyle(fontSize: 11, fontWeight: FontWeight.normal, color: Colors.white70),
              ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout_rounded),
            onPressed: () {
              showDialog(
                context: context,
                builder: (dialogContext) => AlertDialog(
                  title: const Text("Logout"),
                  content: const Text("Are you sure you want to log out of the student application?"),
                  actions: [
                    TextButton(
                      child: const Text("Cancel"),
                      onPressed: () => Navigator.pop(dialogContext),
                    ),
                    TextButton(
                      child: const Text("Logout", style: TextStyle(color: Colors.red)),
                      onPressed: () async {
                        Navigator.pop(dialogContext);
                        await context.read<AuthProvider>().logout();
                        if (!context.mounted) return;
                        Navigator.of(context, rootNavigator: true).pushAndRemoveUntil(
                          MaterialPageRoute(builder: (_) => const LoginScreen()),
                          (route) => false,
                        );
                      },
                    ),
                  ],
                ),
              );
            },
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => dashboardProv.fetchDashboardData(),
        child: _buildContent(dashboardProv, theme),
      ),
    );
  }

  Widget _buildContent(DashboardProvider prov, ThemeData theme) {
    if (prov.isLoading && prov.dashboardData == null) {
      return const LoadingView(message: "Loading dashboard information...");
    }

    if (prov.errorMessage != null && prov.dashboardData == null) {
      return ErrorView(
        message: prov.errorMessage!,
        onRetry: () => prov.fetchDashboardData(),
      );
    }

    final data = prov.dashboardData;
    if (data == null) {
      return const Center(child: Text("No dashboard details found"));
    }

    final student = data.student;
    final record = student.academicRecord;
    final className = record?.classInfo?.name ?? 'N/A';
    final sectionName = record?.sectionInfo?.name ?? 'N/A';

    return SingleChildScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // 1. Student Info ID Card
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [theme.colorScheme.primary, theme.colorScheme.primary.withOpacity(0.85)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: theme.colorScheme.primary.withOpacity(0.3),
                  blurRadius: 12,
                  offset: const Offset(0, 6),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          student.fullName,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          "Admission No: ${student.admissionNo}",
                          style: TextStyle(
                            color: Colors.white.withOpacity(0.85),
                            fontSize: 13,
                          ),
                        ),
                      ],
                    ),
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.2),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.person_rounded, color: Colors.white, size: 28),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                const Divider(color: Colors.white24, height: 1),
                const SizedBox(height: 12),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    _buildIdDetails("CLASS", className),
                    _buildIdDetails("SECTION", sectionName),
                    _buildIdDetails("ROLL NO", record?.rollNo ?? 'N/A'),
                  ],
                ),
                const SizedBox(height: 12),
                _buildIdDetails("ACADEMIC YEAR", data.mobileAcademicYear),
              ],
            ),
          ),
          
          if (data.pendingFeeAmount > 0 && data.isDueDateWithin5DaysOrOverdue) ...[
            const SizedBox(height: 16),
            Card(
              elevation: 0,
              color: theme.brightness == Brightness.dark ? const Color(0xFF1E293B) : const Color(0xFFEFF6FF),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
                side: BorderSide(color: theme.colorScheme.primary.withOpacity(0.2), width: 1.5),
              ),
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Row(
                          children: [
                            Icon(Icons.payment_rounded, color: theme.colorScheme.primary, size: 24),
                            const SizedBox(width: 8),
                            Text(
                              "Pending Fees Due",
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 16,
                                color: theme.colorScheme.primary,
                              ),
                            ),
                          ],
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: Colors.red[50],
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            "UNPAID",
                            style: TextStyle(
                              color: Colors.red[900],
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Text(
                      "₹${data.pendingFeeAmount.toStringAsFixed(2)}",
                      style: TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 24,
                        color: theme.brightness == Brightness.dark ? Colors.white : Colors.blue[900],
                      ),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              "Due Date",
                              style: TextStyle(color: Colors.grey, fontSize: 11),
                            ),
                            Text(
                              data.pendingFeeDueDate ?? 'N/A',
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                            ),
                          ],
                        ),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              "Pending Installments",
                              style: TextStyle(color: Colors.grey, fontSize: 11),
                            ),
                            Text(
                              "${data.pendingInstallmentsCount}",
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                            ),
                          ],
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    ElevatedButton(
                      onPressed: () {
                        Navigator.pushAndRemoveUntil(
                          context,
                          MaterialPageRoute(builder: (_) => const MainNavigationScreen(initialIndex: 2)),
                          (route) => false,
                        );
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: theme.colorScheme.primary,
                        foregroundColor: Colors.white,
                        minimumSize: const Size.fromHeight(48),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                      child: const Text("Pay Now"),
                    ),
                  ],
                ),
              ),
            ),
          ],
          
          const SizedBox(height: 24),

          // 2. Metrics Title
          Text(
            "Overview & Metrics",
            style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 12),

          // 3. Quick Stats Grid
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
            childAspectRatio: 1.35,
            children: [
              _buildStatCard(
                title: "Attendance",
                value: "${data.attendancePercentage.toStringAsFixed(1)}%",
                icon: Icons.calendar_today_rounded,
                color: Colors.green,
                context: context,
              ),
              _buildStatCard(
                title: "Pending Homework",
                value: "${data.pendingHomeworkCount}",
                icon: Icons.assignment_late_rounded,
                color: Colors.orange,
                context: context,
              ),
              _buildStatCard(
                title: "Notices Feed",
                value: "${data.latestNoticeCount}",
                icon: Icons.notifications_active_rounded,
                color: Colors.amber[700]!,
                context: context,
              ),
              _buildStatCard(
                title: "Pending Fees",
                value: "₹${data.pendingFeeAmount.toStringAsFixed(2)}",
                icon: Icons.pending_actions_rounded,
                color: Colors.red,
                context: context,
              ),
            ],
          ),
          const SizedBox(height: 24),

          // 4. Latest Result Panel
          Text(
            "Latest Exam Result",
            style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 12),
          _buildLatestResultCard(data.latestExamResult, theme),
        ],
      ),
    );
  }

  Widget _buildIdDetails(String label, String value) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: TextStyle(
            color: Colors.white.withOpacity(0.6),
            fontSize: 10,
            fontWeight: FontWeight.bold,
            letterSpacing: 0.5,
          ),
        ),
        const SizedBox(height: 2),
        Text(
          value,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 14,
            fontWeight: FontWeight.bold,
          ),
        ),
      ],
    );
  }

  Widget _buildStatCard({
    required String title,
    required String value,
    required IconData icon,
    required Color color,
    required BuildContext context,
  }) {
    final theme = Theme.of(context);
    return Card(
      elevation: 0,
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: color.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(icon, color: color, size: 20),
                ),
              ],
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  value,
                  style: theme.textTheme.headlineMedium?.copyWith(
                    fontWeight: FontWeight.w800,
                    height: 1.1,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  title,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildLatestResultCard(LatestExamResult? result, ThemeData theme) {
    if (result == null) {
      return Card(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            children: [
              Icon(Icons.emoji_events_outlined, size: 48, color: theme.colorScheme.onSurface.withOpacity(0.4)),
              const SizedBox(height: 12),
              const Text("No exam results published yet", style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 4),
              Text("Results will show here as soon as they are released.",
                  style: theme.textTheme.bodyMedium, textAlign: TextAlign.center),
            ],
          ),
        ),
      );
    }

    final isPass = result.result.toLowerCase() == 'pass';

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        result.examName,
                        style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        "Rank: #${result.rank ?? 'N/A'}",
                        style: theme.textTheme.bodyMedium,
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: (isPass ? Colors.green : Colors.red).withOpacity(0.12),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    result.result.toUpperCase(),
                    style: TextStyle(
                      color: isPass ? Colors.green : Colors.red,
                      fontWeight: FontWeight.bold,
                      fontSize: 12,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _buildResultStat("Obtained", "${result.totalObtainedMarks.toStringAsFixed(1)} / ${result.totalMaxMarks.toStringAsFixed(0)}", theme),
                _buildResultStat("Percentage", "${result.percentage.toStringAsFixed(1)}%", theme),
                _buildResultStat("Grade", result.grade, theme),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildResultStat(String label, String value, ThemeData theme) {
    return Column(
      children: [
        Text(label, style: theme.textTheme.bodyMedium?.copyWith(fontSize: 11)),
        const SizedBox(height: 4),
        Text(value, style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
      ],
    );
  }
}

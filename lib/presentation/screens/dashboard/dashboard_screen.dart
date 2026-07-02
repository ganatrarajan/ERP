import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../providers/auth_provider.dart';
import '../../../providers/dashboard_provider.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/error_view.dart';
import '../attendance/attendance_classes_screen.dart';
import '../homework/homework_list_screen.dart';
import '../exam/exam_config_screen.dart';
import '../my_classes/my_classes_screen.dart';
import '../notifications/notifications_screen.dart';
import '../profile/profile_screen.dart';
import '../notice/notice_list_screen.dart';
import '../notice/notice_detail_screen.dart';
import '../leave/leave_list_screen.dart';
class DashboardScreen extends ConsumerStatefulWidget {
  const DashboardScreen({super.key});

  @override
  ConsumerState<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends ConsumerState<DashboardScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(dashboardProvider.notifier).fetchDashboardData();
    });
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final authState = ref.watch(authProvider);
    final dashboardState = ref.watch(dashboardProvider);
    final teacher = authState.teacher;

    final todayStr = DateFormat('EEEE, MMMM d, y').format(DateTime.now());

    return Scaffold(
      appBar: AppBar(
        title: const Text("EduvoraX Dashboard"),
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_outlined, size: 28),
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const NotificationsScreen()),
              );
            },
          ),
          IconButton(
            icon: const Icon(Icons.person_outline_rounded, size: 28),
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const ProfileScreen()),
              );
            },
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.read(dashboardProvider.notifier).fetchDashboardData(),
        child: dashboardState.isLoading && dashboardState.stats == null
            ? const LoadingView(message: "Loading your dashboard...")
            : dashboardState.errorMessage != null && dashboardState.stats == null
                ? ErrorView(
                    message: dashboardState.errorMessage!,
                    onRetry: () => ref.read(dashboardProvider.notifier).fetchDashboardData(),
                  )
                : SingleChildScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Welcome Banner
                        Text(
                          "Welcome Back,",
                          style: theme.textTheme.headlineSmall?.copyWith(
                            color: theme.colorScheme.onSurface.withOpacity(0.6),
                          ),
                        ),
                        Text(
                          teacher?.name ?? "Teacher",
                          style: theme.textTheme.headlineMedium?.copyWith(
                            fontWeight: FontWeight.w800,
                            color: theme.colorScheme.primary,
                          ),
                        ),
                        Text(
                          todayStr,
                          style: theme.textTheme.bodyMedium,
                        ),
                        const SizedBox(height: 24),

                        const SizedBox(height: 12),
                        GridView.count(
                          shrinkWrap: true,
                          crossAxisCount: 4,
                          crossAxisSpacing: 8,
                          mainAxisSpacing: 8,
                          childAspectRatio: 0.8,
                          physics: const NeverScrollableScrollPhysics(),
                          children: [
                            _buildQuickAction(
                              theme: theme,
                              icon: Icons.class_outlined,
                              label: "My Classes",
                              color: Colors.blue,
                              onTap: () {
                                Navigator.of(context).push(
                                  MaterialPageRoute(builder: (_) => const MyClassesScreen()),
                                );
                              },
                            ),
                            _buildQuickAction(
                              theme: theme,
                              icon: Icons.checklist_rtl_rounded,
                              label: "Attendance",
                              color: Colors.green,
                              onTap: () {
                                Navigator.of(context).push(
                                  MaterialPageRoute(builder: (_) => const AttendanceClassesScreen()),
                                );
                              },
                            ),
                             _buildQuickAction(
                              theme: theme,
                              icon: Icons.book_outlined,
                              label: "Homework",
                              color: Colors.orange,
                              onTap: () {
                                Navigator.of(context).push(
                                  MaterialPageRoute(builder: (_) => const HomeworkListScreen()),
                                );
                              },
                            ),
                             _buildQuickAction(
                               theme: theme,
                               icon: Icons.grade_outlined,
                               label: "Marks Entry",
                               color: Colors.purple,
                               onTap: () {
                                 Navigator.of(context).push(
                                   MaterialPageRoute(builder: (_) => const ExamConfigScreen()),
                                 );
                               },
                             ),
                            _buildQuickAction(
                              theme: theme,
                              icon: Icons.calendar_today_rounded,
                              label: "Leaves",
                              color: Colors.blue,
                              onTap: () {
                                Navigator.of(context).push(
                                  MaterialPageRoute(builder: (_) => const LeaveListScreen()),
                                );
                              },
                            ),
                          ],
                        ),
                        const SizedBox(height: 28),

                        // Recent Notices Section
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              "Recent Notices",
                              style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
                            ),
                            TextButton(
                              onPressed: () {
                                Navigator.of(context).push(
                                  MaterialPageRoute(builder: (_) => const NoticeListScreen()),
                                );
                              },
                              child: const Text("View All"),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        if (dashboardState.stats?.recentNotices.isEmpty ?? true)
                          Card(
                            child: Padding(
                              padding: const EdgeInsets.all(16.0),
                              child: Row(
                                children: [
                                  Icon(Icons.info_outline, color: theme.colorScheme.primary),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Text(
                                      "No new notices found.",
                                      style: theme.textTheme.bodyMedium,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          )
                        else
                          ListView.builder(
                            shrinkWrap: true,
                            physics: const NeverScrollableScrollPhysics(),
                            itemCount: dashboardState.stats?.recentNotices.length ?? 0,
                            itemBuilder: (context, index) {
                              final notice = dashboardState.stats!.recentNotices[index];
                              return Card(
                                margin: const EdgeInsets.only(bottom: 8),
                                child: ListTile(
                                  leading: CircleAvatar(
                                    backgroundColor: theme.colorScheme.primary.withOpacity(0.1),
                                    child: Icon(Icons.campaign, color: theme.colorScheme.primary),
                                  ),
                                  title: Text(
                                    notice.title,
                                    style: const TextStyle(fontWeight: FontWeight.bold),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                  subtitle: Text(notice.noticeDate),
                                  trailing: const Icon(Icons.chevron_right_rounded),
                                  onTap: () {
                                    Navigator.of(context).push(
                                      MaterialPageRoute(
                                        builder: (_) => NoticeDetailScreen(noticeId: notice.id),
                                      ),
                                    );
                                  },
                                ),
                              );
                            },
                          ),
                      ],
                    ),
                  ),
      ),
    );
  }


  Widget _buildQuickAction({
    required ThemeData theme,
    required IconData icon,
    required String label,
    required Color color,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 4),
        decoration: BoxDecoration(
          color: theme.cardTheme.color,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: theme.colorScheme.surfaceContainerHighest,
          ),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: color.withOpacity(0.1),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: color, size: 26),
            ),
            const SizedBox(height: 8),
            Text(
              label,
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.bold,
                fontSize: 11,
              ),
              textAlign: TextAlign.center,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }
}

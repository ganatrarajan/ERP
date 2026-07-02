import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../providers/dashboard_provider.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/error_view.dart';
import '../../widgets/empty_view.dart';

class GroupedAssignment {
  final String className;
  final String sectionName;
  bool isClassTeacher;
  int? totalStudents;
  final List<String> subjects;

  GroupedAssignment({
    required this.className,
    required this.sectionName,
    required this.isClassTeacher,
    this.totalStudents,
    required this.subjects,
  });
}

class MyClassesScreen extends ConsumerStatefulWidget {
  const MyClassesScreen({super.key});

  @override
  ConsumerState<MyClassesScreen> createState() => _MyClassesScreenState();
}

class _MyClassesScreenState extends ConsumerState<MyClassesScreen> {
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
    final dashboardState = ref.watch(dashboardProvider);

    // Grouping assignments by unique Class & Section
    final Map<String, GroupedAssignment> groupedMap = {};
    for (var a in dashboardState.assignments) {
      final key = "${a.classId}-${a.sectionId}";
      if (!groupedMap.containsKey(key)) {
        groupedMap[key] = GroupedAssignment(
          className: a.className.name,
          sectionName: a.sectionName.name,
          isClassTeacher: a.isClassTeacher,
          totalStudents: a.totalStudents,
          subjects: [],
        );
      } else {
        if (a.isClassTeacher) {
          groupedMap[key]!.isClassTeacher = true;
        }
        if (a.totalStudents != null) {
          groupedMap[key]!.totalStudents = a.totalStudents;
        }
      }

      if (a.subjectId > 0 && a.subjectName.name.trim().isNotEmpty) {
        final subName = a.subjectName.name.trim();
        if (!groupedMap[key]!.subjects.contains(subName)) {
          groupedMap[key]!.subjects.add(subName);
        }
      }
    }

    final groupedList = groupedMap.values.toList();

    return Scaffold(
      appBar: AppBar(
        title: const Text("My Assigned Classes"),
        elevation: 0,
      ),
      body: dashboardState.isLoading && dashboardState.assignments.isEmpty
          ? const LoadingView(message: "Loading assigned classes...")
          : dashboardState.errorMessage != null && dashboardState.assignments.isEmpty
              ? ErrorView(
                  message: dashboardState.errorMessage!,
                  onRetry: () => ref.read(dashboardProvider.notifier).fetchDashboardData(),
                )
              : groupedList.isEmpty
                  ? const EmptyView(
                      title: "No Assigned Classes",
                      description: "You have not been assigned to any classes for this term.",
                      icon: Icons.class_outlined,
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                      itemCount: groupedList.length,
                      itemBuilder: (context, index) {
                        final item = groupedList[index];
                        final subjectsText = item.subjects.isEmpty
                            ? "Class Teacher (No Assigned Subject)"
                            : item.subjects.join(", ");

                        return Container(
                          margin: const EdgeInsets.only(bottom: 16),
                          decoration: BoxDecoration(
                            color: theme.colorScheme.surfaceContainerLowest,
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(color: theme.colorScheme.outline.withOpacity(0.06)),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.015),
                                blurRadius: 10,
                                offset: const Offset(0, 4),
                              ),
                            ],
                          ),
                          child: Padding(
                            padding: const EdgeInsets.all(18.0),
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.center,
                              children: [
                                // Left details column
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        children: [
                                          Text(
                                            "${item.className} - ${item.sectionName}",
                                            style: TextStyle(
                                              fontWeight: FontWeight.w800,
                                              fontSize: 16,
                                              color: theme.colorScheme.onSurface,
                                            ),
                                          ),
                                          const SizedBox(width: 8),
                                          if (item.isClassTeacher)
                                            Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                              decoration: BoxDecoration(
                                                color: Colors.green.withOpacity(0.08),
                                                borderRadius: BorderRadius.circular(6),
                                                border: Border.all(color: Colors.green.withOpacity(0.2)),
                                              ),
                                              child: const Row(
                                                mainAxisSize: MainAxisSize.min,
                                                children: [
                                                  Icon(Icons.star_rounded, color: Colors.green, size: 12),
                                                  SizedBox(width: 2),
                                                  Text(
                                                    "Class Teacher",
                                                    style: TextStyle(
                                                      color: Colors.green,
                                                      fontSize: 9,
                                                      fontWeight: FontWeight.bold,
                                                    ),
                                                  ),
                                                ],
                                              ),
                                            ),
                                        ],
                                      ),
                                      const SizedBox(height: 10),
                                      Row(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Icon(
                                            Icons.book_outlined,
                                            size: 16,
                                            color: theme.colorScheme.onSurface.withOpacity(0.4),
                                          ),
                                          const SizedBox(width: 8),
                                          Expanded(
                                            child: Text(
                                              subjectsText,
                                              style: TextStyle(
                                                fontSize: 13,
                                                color: theme.colorScheme.onSurface.withOpacity(0.7),
                                                fontWeight: item.subjects.isEmpty
                                                    ? FontWeight.normal
                                                    : FontWeight.bold,
                                              ),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                                const SizedBox(width: 16),
                                // Right student count badge
                                Container(
                                  width: 60,
                                  height: 60,
                                  decoration: BoxDecoration(
                                    color: theme.colorScheme.primary.withOpacity(0.08),
                                    borderRadius: BorderRadius.circular(16),
                                  ),
                                  child: Column(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Text(
                                        item.totalStudents != null
                                            ? "${item.totalStudents}"
                                            : "...",
                                        style: TextStyle(
                                          fontWeight: FontWeight.w900,
                                          fontSize: 18,
                                          color: theme.colorScheme.primary,
                                        ),
                                      ),
                                      const SizedBox(height: 2),
                                      Text(
                                        "Students",
                                        style: TextStyle(
                                          fontSize: 9,
                                          fontWeight: FontWeight.bold,
                                          color: theme.colorScheme.primary.withOpacity(0.8),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
    );
  }
}

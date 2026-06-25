import 'package:flutter/material.dart';
import 'attendance_screen.dart';
import 'homework_screen.dart';
import 'notice_list_screen.dart';
import 'results_screen.dart';
import 'report_cards_screen.dart';

class AcademicsScreen extends StatelessWidget {
  const AcademicsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    // Modules
    final List<Map<String, dynamic>> modules = [
      {
        "title": "Attendance",
        "desc": "Check monthly calendar status & rate",
        "icon": Icons.calendar_month_rounded,
        "color": Colors.green,
        "page": const AttendanceScreen(),
      },
      {
        "title": "Homework Feed",
        "desc": "Submit algebra sheets & view attachments",
        "icon": Icons.assignment_rounded,
        "color": Colors.orange,
        "page": const HomeworkScreen(),
      },
      {
        "title": "Announcements",
        "desc": "School advisories & class notices",
        "icon": Icons.campaign_rounded,
        "color": Colors.amber[700]!,
        "page": const NoticeListScreen(),
      },
      {
        "title": "Exam Results",
        "desc": "Marks breakdown & final grades",
        "icon": Icons.emoji_events_rounded,
        "color": Colors.purple,
        "page": const ResultsScreen(),
      },
      {
        "title": "Report Cards",
        "desc": "Download & share signed PDF files",
        "icon": Icons.picture_as_pdf_rounded,
        "color": Colors.red,
        "page": const ReportCardsScreen(),
      },
    ];

    return Scaffold(
      appBar: AppBar(
        title: const Text("Academics Portal"),
      ),
      body: ListView.separated(
        padding: const EdgeInsets.all(16.0),
        itemCount: modules.length,
        separatorBuilder: (context, index) => const SizedBox(height: 12),
        itemBuilder: (context, index) {
          final item = modules[index];
          final color = item['color'] as Color;

          return Card(
            elevation: 0,
            margin: EdgeInsets.zero,
            child: InkWell(
              borderRadius: BorderRadius.circular(16),
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (context) => item['page'] as Widget),
                );
              },
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 20.0),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: color.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(14),
                      ),
                      child: Icon(item['icon'] as IconData, color: color, size: 28),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            item['title'] as String,
                            style: theme.textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            item['desc'] as String,
                            style: theme.textTheme.bodyMedium?.copyWith(
                              fontSize: 12,
                            ),
                          ),
                        ],
                      ),
                    ),
                    Icon(
                      Icons.arrow_forward_ios_rounded,
                      size: 16,
                      color: theme.colorScheme.onSurface.withOpacity(0.4),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

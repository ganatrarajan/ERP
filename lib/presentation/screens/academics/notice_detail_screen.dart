import 'package:flutter/material.dart';
import '../../../data/models/notice_model.dart';

class NoticeDetailScreen extends StatelessWidget {
  final NoticeModel notice;

  const NoticeDetailScreen({super.key, required this.notice});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isSchoolWide = notice.targetType.toLowerCase() == 'entire school';

    return Scaffold(
      appBar: AppBar(
        title: const Text("Announcement Detail"),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: (isSchoolWide ? Colors.indigo : Colors.teal).withOpacity(0.1),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    isSchoolWide ? "SCHOOL NOTICE" : "CLASS NOTICE",
                    style: TextStyle(
                      color: isSchoolWide ? Colors.indigo : Colors.teal,
                      fontWeight: FontWeight.bold,
                      fontSize: 11,
                    ),
                  ),
                ),
                Text(
                  notice.noticeDate,
                  style: theme.textTheme.bodyMedium,
                ),
              ],
            ),
            const SizedBox(height: 20),
            Text(
              notice.title,
              style: theme.textTheme.headlineMedium?.copyWith(
                fontWeight: FontWeight.bold,
                height: 1.25,
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                const Icon(Icons.account_circle_outlined, size: 18),
                const SizedBox(width: 6),
                Text(
                  "Author: ${notice.creator?.name ?? 'Management'}",
                  style: theme.textTheme.bodyMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),
            const Divider(),
            const SizedBox(height: 20),
            Text(
              notice.description,
              style: theme.textTheme.bodyLarge?.copyWith(
                height: 1.6,
                fontSize: 16,
              ),
            ),
            const SizedBox(height: 40),
            if (notice.attachment != null) ...[
              const Divider(),
              const SizedBox(height: 20),
              Text(
                "Attachment Linked",
                style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 12),
              Card(
                child: ListTile(
                  leading: const Icon(Icons.insert_drive_file_rounded, color: Colors.blue, size: 36),
                  title: Text(notice.attachment!.split('/').last),
                  subtitle: const Text("PDF File Attachment"),
                  trailing: const Icon(Icons.download_rounded),
                  onTap: () {
                    // For demo, notify attachment is linked
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text("Notice file download initiated")),
                    );
                  },
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

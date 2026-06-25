import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../../providers/notice_provider.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/error_view.dart';
import '../../widgets/empty_view.dart';
import 'notice_detail_screen.dart';

class NoticeListScreen extends StatefulWidget {
  const NoticeListScreen({super.key});

  @override
  State<NoticeListScreen> createState() => _NoticeListScreenState();
}

class _NoticeListScreenState extends State<NoticeListScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _tabController.addListener(_handleTabSelection);

    // Initial fetch for "All" notices
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<NoticeProvider>().fetchNotices();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  void _handleTabSelection() {
    if (_tabController.indexIsChanging) return;
    
    final prov = context.read<NoticeProvider>();
    switch (_tabController.index) {
      case 0:
        prov.changeType(null); // All
        break;
      case 1:
        prov.changeType('school'); // School-wide
        break;
      case 2:
        prov.changeType('class'); // Class-wide
        break;
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final noticeProv = context.watch<NoticeProvider>();

    return Scaffold(
      appBar: AppBar(
        title: const Text("Notice Board"),
        bottom: TabBar(
          controller: _tabController,
          indicatorSize: TabBarIndicatorSize.tab,
          tabs: const [
            Tab(text: "All Notices"),
            Tab(text: "School"),
            Tab(text: "Class"),
          ],
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          String? type;
          if (_tabController.index == 1) type = 'school';
          if (_tabController.index == 2) type = 'class';
          await noticeProv.fetchNotices(type: type);
        },
        child: _buildList(noticeProv, theme),
      ),
    );
  }

  Widget _buildList(NoticeProvider prov, ThemeData theme) {
    if (prov.isLoading && prov.notices.isEmpty) {
      return const LoadingView(message: "Loading announcements boards...");
    }

    if (prov.errorMessage != null && prov.notices.isEmpty) {
      return ErrorView(
        message: prov.errorMessage!,
        onRetry: () {
          String? type;
          if (_tabController.index == 1) type = 'school';
          if (_tabController.index == 2) type = 'class';
          prov.fetchNotices(type: type);
        },
      );
    }

    if (prov.notices.isEmpty) {
      return const EmptyView(
        title: "No Notices Broadcasted",
        description: "Your notice board is clear! Important bulletins will show up here.",
        icon: Icons.campaign_rounded,
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: prov.notices.length,
      separatorBuilder: (context, index) => const SizedBox(height: 12),
      itemBuilder: (context, index) {
        final notice = prov.notices[index];

        String formattedDate = notice.noticeDate;
        try {
          final parsed = DateTime.parse(notice.noticeDate);
          formattedDate = DateFormat('MMM dd, yyyy').format(parsed);
        } catch (_) {}

        final isSchoolWide = notice.targetType.toLowerCase() == 'entire school';

        return Card(
          margin: EdgeInsets.zero,
          child: InkWell(
            borderRadius: BorderRadius.circular(16),
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => NoticeDetailScreen(notice: notice),
                ),
              );
            },
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                        decoration: BoxDecoration(
                          color: (isSchoolWide ? Colors.indigo : Colors.teal).withOpacity(0.1),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          isSchoolWide ? "SCHOOL" : "CLASS",
                          style: TextStyle(
                            color: isSchoolWide ? Colors.indigo : Colors.teal,
                            fontWeight: FontWeight.bold,
                            fontSize: 10,
                          ),
                        ),
                      ),
                      Text(
                        formattedDate,
                        style: theme.textTheme.bodyMedium?.copyWith(fontSize: 11),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    notice.title,
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 6),
                  Text(
                    notice.description,
                    style: theme.textTheme.bodyMedium,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 12),
                  const Divider(height: 1),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Icon(Icons.person_outline_rounded, size: 14, color: theme.colorScheme.onSurface.withOpacity(0.5)),
                      const SizedBox(width: 4),
                      Text(
                        "Posted by: ${notice.creator?.name ?? 'Admin'}",
                        style: theme.textTheme.bodyMedium?.copyWith(
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }
}

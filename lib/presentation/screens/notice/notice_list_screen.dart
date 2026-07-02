import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../providers/notice_provider.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/error_view.dart';
import '../../widgets/empty_view.dart';
import 'notice_detail_screen.dart';

class NoticeListScreen extends ConsumerStatefulWidget {
  const NoticeListScreen({super.key});

  @override
  ConsumerState<NoticeListScreen> createState() => _NoticeListScreenState();
}

class _NoticeListScreenState extends ConsumerState<NoticeListScreen> {
  final _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(noticeProvider.notifier).fetchNotices(refresh: true);
    });
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 200) {
      final state = ref.read(noticeProvider);
      if (state.hasNextPage && !state.isLoading) {
        ref.read(noticeProvider.notifier).fetchNotices();
      }
    }
  }

  Future<void> _handleRefresh() async {
    await ref.read(noticeProvider.notifier).fetchNotices(refresh: true);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final state = ref.watch(noticeProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text("Notices & Circulars"),
      ),
      body: RefreshIndicator(
        onRefresh: _handleRefresh,
        child: state.isLoading && state.notices.isEmpty
            ? const LoadingView(message: "Loading notices...")
            : state.errorMessage != null && state.notices.isEmpty
                ? ErrorView(
                    message: state.errorMessage!,
                    onRetry: _handleRefresh,
                  )
                : state.notices.isEmpty
                    ? const EmptyView(
                        title: "Notice Board Empty",
                        description: "There are no notices published recently.",
                        icon: Icons.campaign_outlined,
                      )
                    : ListView.builder(
                        controller: _scrollController,
                        physics: const AlwaysScrollableScrollPhysics(),
                        padding: const EdgeInsets.all(16),
                        itemCount: state.notices.length + (state.hasNextPage ? 1 : 0),
                        itemBuilder: (context, index) {
                          if (index == state.notices.length) {
                            return const Padding(
                              padding: EdgeInsets.symmetric(vertical: 16.0),
                              child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
                            );
                          }

                          final item = state.notices[index];
                          return Card(
                            margin: const EdgeInsets.only(bottom: 12),
                            child: ListTile(
                              contentPadding: const EdgeInsets.all(16),
                              leading: CircleAvatar(
                                backgroundColor: theme.colorScheme.primary.withOpacity(0.08),
                                child: Icon(Icons.campaign, color: theme.colorScheme.primary),
                              ),
                              title: Text(
                                item.title,
                                style: const TextStyle(fontWeight: FontWeight.bold),
                              ),
                              subtitle: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const SizedBox(height: 6),
                                  Text(
                                    "Date: ${item.noticeDate}",
                                    style: TextStyle(color: theme.colorScheme.primary, fontSize: 12, fontWeight: FontWeight.bold),
                                  ),
                                  if (item.creator != null)
                                    Text(
                                      "By: ${item.creator!.name}",
                                      style: TextStyle(color: theme.colorScheme.onSurface.withOpacity(0.6), fontSize: 11),
                                    ),
                                ],
                              ),
                              trailing: const Icon(Icons.chevron_right_rounded),
                              onTap: () {
                                Navigator.of(context).push(
                                  MaterialPageRoute(
                                    builder: (_) => NoticeDetailScreen(noticeId: item.id),
                                  ),
                                );
                              },
                            ),
                          );
                        },
                      ),
      ),
    );
  }
}

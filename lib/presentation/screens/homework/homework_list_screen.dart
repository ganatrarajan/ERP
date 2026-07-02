import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../providers/homework_provider.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/error_view.dart';
import '../../widgets/empty_view.dart';
import 'homework_detail_screen.dart';
import 'homework_create_screen.dart';

class HomeworkListScreen extends ConsumerStatefulWidget {
  const HomeworkListScreen({super.key});

  @override
  ConsumerState<HomeworkListScreen> createState() => _HomeworkListScreenState();
}

class _HomeworkListScreenState extends ConsumerState<HomeworkListScreen> {
  final _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(homeworkProvider.notifier).fetchHomeworks(refresh: true);
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
      final state = ref.read(homeworkProvider);
      if (state.hasNextPage && !state.isLoading) {
        ref.read(homeworkProvider.notifier).fetchHomeworks();
      }
    }
  }

  Future<void> _handleRefresh() async {
    await ref.read(homeworkProvider.notifier).fetchHomeworks(refresh: true);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final state = ref.watch(homeworkProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text("Homework List"),
      ),
      body: RefreshIndicator(
        onRefresh: _handleRefresh,
        child: state.isLoading && state.homeworks.isEmpty
            ? const LoadingView(message: "Fetching homeworks...")
            : state.errorMessage != null && state.homeworks.isEmpty
                ? ErrorView(
                    message: state.errorMessage!,
                    onRetry: _handleRefresh,
                  )
                : state.homeworks.isEmpty
                    ? const EmptyView(
                        title: "No Homework Created",
                        description: "You have not published any homework yet. Click the + button below to create one.",
                        icon: Icons.assignment_outlined,
                      )
                    : ListView.builder(
                        controller: _scrollController,
                        physics: const AlwaysScrollableScrollPhysics(),
                        padding: const EdgeInsets.all(16),
                        itemCount: state.homeworks.length + (state.hasNextPage ? 1 : 0),
                        itemBuilder: (context, index) {
                          if (index == state.homeworks.length) {
                            return const Padding(
                              padding: EdgeInsets.symmetric(vertical: 16.0),
                              child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
                            );
                          }

                          final item = state.homeworks[index];
                          return Card(
                            margin: const EdgeInsets.only(bottom: 14),
                            child: ListTile(
                              contentPadding: const EdgeInsets.all(16),
                              title: Text(
                                item.title,
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                              ),
                              subtitle: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const SizedBox(height: 8),
                                  Text(
                                    "Class: ${item.className?.name ?? ''} - ${item.sectionName?.name ?? ''}",
                                    style: TextStyle(color: theme.colorScheme.onSurface.withOpacity(0.7)),
                                  ),
                                  Text(
                                    "Subject: ${item.subjectName?.name ?? ''}",
                                    style: TextStyle(color: theme.colorScheme.onSurface.withOpacity(0.7)),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    "Due: ${item.submissionDate}",
                                    style: TextStyle(color: theme.colorScheme.secondary, fontWeight: FontWeight.bold),
                                  ),
                                ],
                              ),
                              trailing: const Icon(Icons.chevron_right_rounded),
                              onTap: () {
                                Navigator.of(context).push(
                                  MaterialPageRoute(
                                    builder: (_) => HomeworkDetailScreen(homeworkId: item.id),
                                  ),
                                );
                              },
                            ),
                          );
                        },
                      ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          Navigator.of(context).push(
            MaterialPageRoute(builder: (_) => const HomeworkCreateScreen()),
          );
        },
        icon: const Icon(Icons.add),
        label: const Text("Create Homework"),
      ),
    );
  }
}

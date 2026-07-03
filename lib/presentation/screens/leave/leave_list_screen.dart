import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../providers/leave_provider.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/empty_view.dart';
import 'leave_apply_screen.dart';

class LeaveListScreen extends ConsumerWidget {
  const LeaveListScreen({super.key});

  void _handleCancel(BuildContext context, WidgetRef ref, String id) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text("Cancel Leave Request?"),
        content: const Text("Are you sure you want to cancel this leave request?"),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text("No"),
          ),
          ElevatedButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white),
            child: const Text("Yes, Cancel"),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      final success = await ref.read(leaveProvider.notifier).cancelLeave(id);
      if (context.mounted) {
        if (success) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text("Leave request cancelled successfully"), backgroundColor: Colors.green),
          );
        } else {
          final errorMsg = ref.read(leaveProvider).errorMessage ?? "Failed to cancel leave request";
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(errorMsg), backgroundColor: Colors.red),
          );
        }
      }
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final state = ref.watch(leaveProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text("My Leave Requests"),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.read(leaveProvider.notifier).loadLeaves(),
        child: state.isLoading && state.requests.isEmpty
            ? const LoadingView(message: "Loading leaves log...")
            : state.requests.isEmpty
                ? const EmptyView(
                    title: "No Leave History",
                    description: "You have not submitted any leave requests yet.",
                    icon: Icons.calendar_today_rounded,
                  )
                : ListView.builder(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    itemCount: state.requests.length,
                    itemBuilder: (context, index) {
                      final item = state.requests[index];
                      Color statusColor = Colors.orange;
                      if (item.status == 'Approved') {
                        statusColor = Colors.green;
                      } else if (item.status == 'Rejected') {
                        statusColor = Colors.red;
                      }

                      return Card(
                        margin: const EdgeInsets.only(bottom: 12),
                        child: Padding(
                          padding: const EdgeInsets.all(16.0),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    item.leaveType,
                                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                                  ),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                    decoration: BoxDecoration(
                                      color: statusColor.withOpacity(0.1),
                                      borderRadius: BorderRadius.circular(6),
                                      border: Border.all(color: statusColor.withOpacity(0.3)),
                                    ),
                                    child: Text(
                                      item.status,
                                      style: TextStyle(
                                        color: statusColor,
                                        fontWeight: FontWeight.bold,
                                        fontSize: 12,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 12),
                              Text(
                                "Duration: ${item.startDate} to ${item.endDate}",
                                style: const TextStyle(fontWeight: FontWeight.bold),
                              ),
                              const SizedBox(height: 6),
                              Text(
                                "Reason: ${item.reason}",
                                style: TextStyle(color: theme.colorScheme.onSurface.withOpacity(0.7)),
                              ),
                              const SizedBox(height: 8),
                              Divider(color: theme.colorScheme.surfaceContainerHighest),
                              const SizedBox(height: 4),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    "Requested on: ${item.requestedDate}",
                                    style: const TextStyle(fontSize: 10, color: Colors.grey),
                                  ),
                                  if (item.status == 'Pending')
                                    TextButton.icon(
                                      onPressed: () => _handleCancel(context, ref, item.id),
                                      style: TextButton.styleFrom(
                                        foregroundColor: Colors.red,
                                        padding: EdgeInsets.zero,
                                        minimumSize: const Size(50, 30),
                                        tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                                      ),
                                      icon: const Icon(Icons.cancel_outlined, size: 14),
                                      label: const Text("Cancel", style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                                    ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          Navigator.of(context).push(
            MaterialPageRoute(builder: (_) => const LeaveApplyScreen()),
          );
        },
        icon: const Icon(Icons.add),
        label: const Text("Apply Leave"),
      ),
    );
  }
}

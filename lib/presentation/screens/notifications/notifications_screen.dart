import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../../core/services/notification_service.dart';
import '../../widgets/empty_view.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  List<NotificationModel> _notifications = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadNotifications();
  }

  Future<void> _loadNotifications() async {
    setState(() {
      _isLoading = true;
    });

    final list = await NotificationService().getCachedNotifications();
    
    setState(() {
      _notifications = list;
      _isLoading = false;
    });
  }

  void _markAsRead(String id) async {
    await NotificationService().markAsRead(id);
    _loadNotifications();
  }

  void _clearAll() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text("Clear Notifications"),
        content: const Text("Are you sure you want to delete all cached notifications?"),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text("Cancel"),
          ),
          ElevatedButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white),
            child: const Text("Clear All"),
          ),
        ],
      ),
    );

    if (confirm == true) {
      await NotificationService().clearAll();
      _loadNotifications();
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text("Notifications History"),
        actions: [
          if (_notifications.isNotEmpty)
            IconButton(
              icon: const Icon(Icons.delete_sweep_outlined),
              tooltip: "Clear All",
              onPressed: _clearAll,
            ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _notifications.isEmpty
              ? const EmptyView(
                  title: "Notification Inbox Empty",
                  description: "You have no push notifications cached on this device.",
                  icon: Icons.notifications_none_rounded,
                )
              : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: _notifications.length,
                  itemBuilder: (context, index) {
                    final item = _notifications[index];
                    final dateStr = DateFormat('MMM d, h:mm a').format(DateTime.parse(item.timestamp));
                    
                    return Card(
                      margin: const EdgeInsets.only(bottom: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                        side: BorderSide(
                          color: item.read ? theme.colorScheme.surfaceContainerHighest : theme.colorScheme.primary.withOpacity(0.3),
                          width: item.read ? 1 : 1.5,
                        ),
                      ),
                      child: ListTile(
                        contentPadding: const EdgeInsets.all(16),
                        leading: CircleAvatar(
                          backgroundColor: item.read
                              ? theme.colorScheme.surfaceContainerHighest
                              : theme.colorScheme.primary.withOpacity(0.1),
                          child: Icon(
                            item.read ? Icons.notifications_none_rounded : Icons.notifications_active_rounded,
                            color: item.read ? Colors.grey : theme.colorScheme.primary,
                          ),
                        ),
                        title: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: Text(
                                item.title,
                                style: TextStyle(
                                  fontWeight: item.read ? FontWeight.normal : FontWeight.bold,
                                ),
                              ),
                            ),
                            Text(
                              dateStr,
                              style: const TextStyle(fontSize: 10, color: Colors.grey),
                            ),
                          ],
                        ),
                        subtitle: Padding(
                          padding: const EdgeInsets.only(top: 8.0),
                          child: Text(
                            item.body,
                            style: TextStyle(
                              color: theme.colorScheme.onSurface.withOpacity(item.read ? 0.6 : 0.95),
                            ),
                          ),
                        ),
                        onTap: () {
                          if (!item.read) {
                            _markAsRead(item.id);
                          }
                        },
                      ),
                    );
                  },
                ),
    );
  }
}

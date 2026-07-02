import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../providers/notice_provider.dart';
import '../../../core/services/download_service.dart';
import '../../../core/constants/api_endpoints.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/error_view.dart';

class NoticeDetailScreen extends ConsumerStatefulWidget {
  final int noticeId;
  const NoticeDetailScreen({super.key, required this.noticeId});

  @override
  ConsumerState<NoticeDetailScreen> createState() => _NoticeDetailScreenState();
}

class _NoticeDetailScreenState extends ConsumerState<NoticeDetailScreen> {
  bool _isDownloading = false;
  double _downloadProgress = 0.0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(noticeProvider.notifier).fetchDetails(widget.noticeId);
    });
  }

  void _downloadAttachment(String relativePath) async {
    final fileUrl = ApiEndpoints.resolveAttachmentUrl(relativePath);
    final filename = relativePath.split('/').last;

    setState(() {
      _isDownloading = true;
      _downloadProgress = 0.0;
    });

    try {
      final localPath = await DownloadService().downloadFile(
        url: fileUrl,
        filename: filename,
        onProgress: (received, total) {
          if (total > 0) {
            setState(() {
              _downloadProgress = received / total;
            });
          }
        },
      );

      setState(() {
        _isDownloading = false;
      });

      if (mounted && localPath != null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text("Downloaded: $filename"),
            backgroundColor: Colors.green,
            action: SnackBarAction(
              label: "Open",
              textColor: Colors.white,
              onPressed: () {
                DownloadService().openDownloadedFile(filename);
              },
            ),
          ),
        );
      }
    } catch (e) {
      setState(() {
        _isDownloading = false;
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text("Download failed: ${e.toString()}"),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final state = ref.watch(noticeProvider);
    final details = state.activeDetails;

    return Scaffold(
      appBar: AppBar(
        title: const Text("Notice Details"),
      ),
      body: state.isLoading && details == null
          ? const LoadingView(message: "Loading notice...")
          : state.errorMessage != null && details == null
              ? ErrorView(
                  message: state.errorMessage!,
                  onRetry: () => ref.read(noticeProvider.notifier).fetchDetails(widget.noticeId),
                )
              : details == null
                  ? const Center(child: Text("Notice not found"))
                  : SingleChildScrollView(
                      padding: const EdgeInsets.all(20.0),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // Title
                          Text(
                            details.title,
                            style: theme.textTheme.headlineMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                              color: theme.colorScheme.primary,
                            ),
                          ),
                          const SizedBox(height: 12),
                          
                          // Metadata Card
                          Card(
                            child: Padding(
                              padding: const EdgeInsets.all(12.0),
                              child: Row(
                                children: [
                                  Icon(Icons.calendar_month_outlined, color: theme.colorScheme.secondary, size: 20),
                                  const SizedBox(width: 8),
                                  Text(
                                    details.noticeDate,
                                    style: const TextStyle(fontWeight: FontWeight.bold),
                                  ),
                                  const Spacer(),
                                  if (details.creator != null) ...[
                                    Icon(Icons.person_outline, color: theme.colorScheme.secondary, size: 20),
                                    const SizedBox(width: 6),
                                    Text(
                                      details.creator!.name,
                                      style: const TextStyle(fontWeight: FontWeight.bold),
                                    ),
                                  ],
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(height: 24),

                          // Description
                          Text(
                            details.description,
                            style: theme.textTheme.bodyLarge?.copyWith(
                              height: 1.6,
                              color: theme.colorScheme.onSurface.withOpacity(0.85),
                            ),
                          ),
                          const SizedBox(height: 32),

                          // Attachment Section
                          if (details.attachment != null && details.attachment!.isNotEmpty) ...[
                            Text(
                              "Attachment",
                              style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                            ),
                            const SizedBox(height: 12),
                            Card(
                              color: theme.colorScheme.primary.withOpacity(0.04),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12),
                                side: BorderSide(color: theme.colorScheme.primary.withOpacity(0.1)),
                              ),
                              child: Padding(
                                padding: const EdgeInsets.all(16.0),
                                child: Row(
                                  children: [
                                    Icon(Icons.picture_as_pdf_outlined, color: theme.colorScheme.error, size: 36),
                                    const SizedBox(width: 16),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            details.attachment!.split('/').last,
                                            style: const TextStyle(fontWeight: FontWeight.bold),
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                          const SizedBox(height: 4),
                                          const Text("PDF Document", style: TextStyle(fontSize: 11, color: Colors.grey)),
                                        ],
                                      ),
                                    ),
                                    const SizedBox(width: 8),
                                    if (_isDownloading)
                                      SizedBox(
                                        height: 24,
                                        width: 24,
                                        child: CircularProgressIndicator(
                                          value: _downloadProgress > 0 ? _downloadProgress : null,
                                          strokeWidth: 2.5,
                                        ),
                                      )
                                    else
                                      IconButton(
                                        icon: Icon(Icons.download_for_offline_outlined, color: theme.colorScheme.primary),
                                        onPressed: () => _downloadAttachment(details.attachment!),
                                      ),
                                  ],
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
    );
  }
}

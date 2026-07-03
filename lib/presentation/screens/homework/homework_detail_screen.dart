import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'dart:convert';
import 'dart:io';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../providers/homework_provider.dart';
import '../../../core/services/download_service.dart';
import '../../../core/constants/api_endpoints.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/error_view.dart';
import 'homework_edit_screen.dart';

class HomeworkDetailScreen extends ConsumerStatefulWidget {
  final int homeworkId;
  const HomeworkDetailScreen({super.key, required this.homeworkId});

  @override
  ConsumerState<HomeworkDetailScreen> createState() => _HomeworkDetailScreenState();
}

class _HomeworkDetailScreenState extends ConsumerState<HomeworkDetailScreen> {
  bool _isDownloading = false;
  double _downloadProgress = 0.0;
  Map<String, dynamic> _downloadedHomeworkInfo = {};

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(homeworkProvider.notifier).fetchDetails(widget.homeworkId).then((_) {
        final details = ref.read(homeworkProvider).activeDetails;
        if (details != null) {
          _syncAndCleanObsoleteFiles(details);
        }
      });
    });
    _loadDownloadedHomeworkInfo();
  }

  Future<void> _loadDownloadedHomeworkInfo() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final dataStr = prefs.getString('downloaded_homeworks');
      if (dataStr != null) {
        setState(() {
          _downloadedHomeworkInfo = jsonDecode(dataStr);
        });
      }
    } catch (_) {}
  }

  Future<void> _syncAndCleanObsoleteFiles(dynamic details) async {
    if (details == null || details.attachment == null || details.attachment.isEmpty) return;

    try {
      final prefs = await SharedPreferences.getInstance();
      final dataStr = prefs.getString('downloaded_homeworks');
      if (dataStr == null) return;

      Map<String, dynamic> infoMap = jsonDecode(dataStr);
      final key = details.id.toString();

      if (infoMap.containsKey(key)) {
        final docInfo = Map<String, dynamic>.from(infoMap[key]);
        final localPath = docInfo['localPath'] as String?;
        final onlinePath = docInfo['onlinePath'] as String?;

        if (details.attachment != onlinePath) {
          if (localPath != null) {
            final file = File(localPath);
            if (file.existsSync()) {
              file.deleteSync();
            }
          }
          infoMap.remove(key);
          await prefs.setString('downloaded_homeworks', jsonEncode(infoMap));
          setState(() {
            _downloadedHomeworkInfo = infoMap;
          });
        } else {
          if (localPath != null && !File(localPath).existsSync()) {
            infoMap.remove(key);
            await prefs.setString('downloaded_homeworks', jsonEncode(infoMap));
            setState(() {
              _downloadedHomeworkInfo = infoMap;
            });
          }
        }
      }
    } catch (_) {}
  }

  void _downloadAttachment(String relativePath) async {
    final fileUrl = ApiEndpoints.resolveAttachmentUrl(relativePath);
    final filename = 'Homework_${widget.homeworkId}_${relativePath.split('/').last}';

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
        final prefs = await SharedPreferences.getInstance();
        final updatedInfo = Map<String, dynamic>.from(_downloadedHomeworkInfo);
        updatedInfo[widget.homeworkId.toString()] = {
          'localPath': localPath,
          'onlinePath': relativePath,
          'filename': filename,
        };
        await prefs.setString('downloaded_homeworks', jsonEncode(updatedInfo));

        setState(() {
          _downloadedHomeworkInfo = updatedInfo;
        });

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

  void _handleDelete() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text("Delete Homework"),
        content: const Text("Are you sure you want to delete this homework? This action cannot be undone."),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text("Cancel"),
          ),
          ElevatedButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white),
            child: const Text("Delete"),
          ),
        ],
      ),
    );

    if (confirm == true && mounted) {
      final success = await ref.read(homeworkProvider.notifier).deleteHomework(widget.homeworkId);
      if (mounted) {
        if (success) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text("Homework deleted successfully"), backgroundColor: Colors.green),
          );
          Navigator.of(context).pop();
        } else {
          final err = ref.read(homeworkProvider).errorMessage;
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(err ?? "Failed to delete homework"), backgroundColor: Colors.red),
          );
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final state = ref.watch(homeworkProvider);
    final details = state.activeDetails;

    return Scaffold(
      appBar: AppBar(
        title: const Text("Homework Details"),
        actions: details != null
            ? [
                IconButton(
                  icon: const Icon(Icons.edit_outlined),
                  onPressed: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => HomeworkEditScreen(homework: details),
                      ),
                    );
                  },
                ),
                IconButton(
                  icon: const Icon(Icons.delete_outline_rounded),
                  onPressed: _handleDelete,
                ),
              ]
            : null,
      ),
      body: state.isLoading && details == null
          ? const LoadingView(message: "Loading details...")
          : state.errorMessage != null && details == null
              ? ErrorView(
                  message: state.errorMessage!,
                  onRetry: () => ref.read(homeworkProvider.notifier).fetchDetails(widget.homeworkId),
                )
              : details == null
                  ? const Center(child: Text("Homework details not found"))
                  : SingleChildScrollView(
                      padding: const EdgeInsets.all(16.0),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // Class info row
                          Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                decoration: BoxDecoration(
                                  color: theme.colorScheme.primary.withOpacity(0.1),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: Text(
                                  "${details.className?.name ?? ''} - ${details.sectionName?.name ?? ''}",
                                  style: TextStyle(color: theme.colorScheme.primary, fontWeight: FontWeight.bold, fontSize: 12),
                                ),
                              ),
                              const SizedBox(width: 8),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                decoration: BoxDecoration(
                                  color: theme.colorScheme.secondary.withOpacity(0.1),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: Text(
                                  details.subjectName?.name ?? '',
                                  style: TextStyle(color: theme.colorScheme.secondary, fontWeight: FontWeight.bold, fontSize: 12),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 16),

                          // Title
                          Text(
                            details.title,
                            style: theme.textTheme.headlineMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                              color: theme.colorScheme.onSurface,
                            ),
                          ),
                          const SizedBox(height: 16),

                          // Dates & Marks Card
                          Card(
                            child: Padding(
                              padding: const EdgeInsets.all(16.0),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      const Text("Submission Date", style: TextStyle(fontSize: 12, color: Colors.grey)),
                                      const SizedBox(height: 4),
                                      Text(
                                        details.submissionDate,
                                        style: TextStyle(
                                          fontWeight: FontWeight.bold,
                                          color: theme.colorScheme.primary,
                                          fontSize: 16,
                                        ),
                                      ),
                                    ],
                                  ),
                                  Column(
                                    crossAxisAlignment: CrossAxisAlignment.end,
                                    children: [
                                      const Text("Max Marks", style: TextStyle(fontSize: 12, color: Colors.grey)),
                                      const SizedBox(height: 4),
                                      Text(
                                        details.maxMarks != null ? "${details.maxMarks!.toInt()}" : "N/A",
                                        style: const TextStyle(
                                          fontWeight: FontWeight.bold,
                                          fontSize: 16,
                                        ),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(height: 24),

                          // Description
                          Text(
                            "Instructions",
                            style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            details.description,
                            style: theme.textTheme.bodyLarge?.copyWith(
                              height: 1.5,
                              color: theme.colorScheme.onSurface.withOpacity(0.8),
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
                            (() {
                              final isDownloaded = _downloadedHomeworkInfo.containsKey(widget.homeworkId.toString());
                              final docInfo = isDownloaded ? _downloadedHomeworkInfo[widget.homeworkId.toString()] : null;
                              final filename = docInfo != null ? docInfo['filename'] as String? : null;

                              final ext = details.attachment!.contains('.') ? details.attachment!.split('.').last.toLowerCase() : 'pdf';
                              final isImage = ['jpg', 'jpeg', 'png', 'gif', 'jfif', 'webp'].contains(ext);

                              return Card(
                                color: isDownloaded ? Colors.green.withOpacity(0.04) : theme.colorScheme.primary.withOpacity(0.04),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12),
                                  side: BorderSide(
                                    color: isDownloaded ? Colors.green.withOpacity(0.2) : theme.colorScheme.primary.withOpacity(0.1),
                                  ),
                                ),
                                child: InkWell(
                                  onTap: () {
                                    if (isDownloaded && filename != null) {
                                      DownloadService().openDownloadedFile(filename);
                                    } else {
                                      _downloadAttachment(details.attachment!);
                                    }
                                  },
                                  borderRadius: BorderRadius.circular(12),
                                  child: Padding(
                                    padding: const EdgeInsets.all(16.0),
                                    child: Row(
                                      children: [
                                        Icon(
                                          isImage ? Icons.image_outlined : Icons.file_present_rounded,
                                          color: isDownloaded ? Colors.green : (isImage ? Colors.orange : theme.colorScheme.primary),
                                          size: 36,
                                        ),
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
                                              Text(
                                                isDownloaded 
                                                    ? "Downloaded (Tap to view)" 
                                                    : (isImage ? "Image Attachment (Tap to download)" : "Document (Tap to download)"),
                                                style: TextStyle(
                                                  fontSize: 11,
                                                  color: isDownloaded ? Colors.green : Colors.grey,
                                                  fontWeight: isDownloaded ? FontWeight.bold : FontWeight.normal,
                                                ),
                                              ),
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
                                        else if (isDownloaded)
                                          Row(
                                            mainAxisSize: MainAxisSize.min,
                                            children: [
                                              IconButton(
                                                icon: const Icon(Icons.open_in_new_rounded, color: Colors.green),
                                                tooltip: "Open File",
                                                onPressed: () {
                                                  if (filename != null) {
                                                    DownloadService().openDownloadedFile(filename);
                                                  }
                                                },
                                              ),
                                              IconButton(
                                                icon: const Icon(Icons.sync_rounded, color: Colors.blue),
                                                tooltip: "Re-download",
                                                onPressed: () => _downloadAttachment(details.attachment!),
                                              ),
                                            ],
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
                              );
                            })(),
                          ],
                        ],
                      ),
                    ),
    );
  }
}

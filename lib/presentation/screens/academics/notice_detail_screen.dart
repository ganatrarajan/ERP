import 'package:flutter/material.dart';
import '../../../data/models/notice_model.dart';
import '../../../core/services/download_service.dart';
import '../../../core/constants/api_endpoints.dart';
import '../pdf_viewer/pdf_viewer_screen.dart';
import '../pdf_viewer/image_viewer_screen.dart';

class NoticeDetailScreen extends StatefulWidget {
  final NoticeModel notice;

  const NoticeDetailScreen({super.key, required this.notice});

  @override
  State<NoticeDetailScreen> createState() => _NoticeDetailScreenState();
}

class _NoticeDetailScreenState extends State<NoticeDetailScreen> {
  final DownloadService _downloadService = DownloadService();
  bool _isDownloaded = false;
  double? _progress;

  bool get _isImage {
    final attachment = widget.notice.attachment;
    if (attachment == null) return false;
    final path = attachment.toLowerCase();
    return path.endsWith('.png') ||
        path.endsWith('.jpg') ||
        path.endsWith('.jpeg') ||
        path.endsWith('.webp') ||
        path.endsWith('.gif');
  }

  String get _fileExtension {
    final attachment = widget.notice.attachment;
    if (attachment == null) return 'pdf';
    final parts = attachment.split('.');
    if (parts.length > 1) {
      return parts.last.toLowerCase();
    }
    return _isImage ? 'jpg' : 'pdf';
  }

  String get _localFilename {
    final attachment = widget.notice.attachment;
    if (attachment == null || attachment.isEmpty) {
      return 'notice_${widget.notice.id}.pdf';
    }
    final uri = Uri.parse(attachment);
    final fileName = uri.pathSegments.isNotEmpty ? uri.pathSegments.last : 'attachment';
    final decodedFileName = Uri.decodeComponent(fileName);
    if (decodedFileName.contains('.')) {
      return 'notice_${widget.notice.id}_$decodedFileName';
    } else {
      return 'notice_${widget.notice.id}_$decodedFileName.$_fileExtension';
    }
  }

  @override
  void initState() {
    super.initState();
    _checkDownloaded();
  }

  Future<void> _checkDownloaded() async {
    if (widget.notice.attachment != null) {
      final isDownloaded = await _downloadService.isFileDownloaded(_localFilename);
      if (mounted) {
        setState(() {
          _isDownloaded = isDownloaded;
        });
      }
    }
  }

  Future<void> _handleDownloadOrOpen() async {
    if (widget.notice.attachment == null) return;

    if (_isDownloaded) {
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => _isImage
              ? ImageViewerScreen(
                  title: widget.notice.title,
                  filename: _localFilename,
                )
              : PdfViewerScreen(
                  title: widget.notice.title,
                  filename: _localFilename,
                ),
        ),
      );
    } else {
      final messenger = ScaffoldMessenger.of(context);
      final errorColor = Theme.of(context).colorScheme.error;

      setState(() {
        _progress = 0.05;
      });

      try {
        final absoluteUrl = ApiEndpoints.resolveAttachmentUrl(widget.notice.attachment);

        await _downloadService.downloadFile(
          url: absoluteUrl,
          filename: _localFilename,
          onProgress: (rec, tot) {
            if (tot > 0 && mounted) {
              setState(() {
                _progress = rec / tot;
              });
            }
          },
        );

        if (mounted) {
          setState(() {
            _isDownloaded = true;
            _progress = null;
          });
          messenger.showSnackBar(
            const SnackBar(
              content: Text("Downloaded attachment successfully!"),
              backgroundColor: Colors.green,
            ),
          );
        }
      } catch (e) {
        if (mounted) {
          setState(() {
            _progress = null;
          });
          messenger.showSnackBar(
            SnackBar(
              content: Text("Failed to download attachment: ${e.toString()}"),
              backgroundColor: errorColor,
            ),
          );
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isSchoolWide = widget.notice.targetType.toLowerCase() == 'entire school';

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
                  widget.notice.noticeDate,
                  style: theme.textTheme.bodyMedium,
                ),
              ],
            ),
            const SizedBox(height: 20),
            Text(
              widget.notice.title,
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
                  "Author: ${widget.notice.creator?.name ?? 'Management'}",
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
              widget.notice.description,
              style: theme.textTheme.bodyLarge?.copyWith(
                height: 1.6,
                fontSize: 16,
              ),
            ),
            const SizedBox(height: 40),
            if (widget.notice.attachment != null) ...[
              const Divider(),
              const SizedBox(height: 20),
              Text(
                "Attachment Linked",
                style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 12),
              Card(
                child: ListTile(
                  leading: Icon(
                    _isImage ? Icons.image_rounded : Icons.insert_drive_file_rounded,
                    color: _isImage ? Colors.teal : Colors.blue,
                    size: 36,
                  ),
                  title: Text(widget.notice.attachment!.split('/').last),
                  subtitle: Text(_isImage ? "Image Attachment" : "PDF File Attachment"),
                  trailing: _progress != null
                      ? SizedBox(
                          width: 24,
                          height: 24,
                          child: CircularProgressIndicator(
                            value: _progress,
                            strokeWidth: 3,
                          ),
                        )
                      : Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            if (_isDownloaded) ...[
                              IconButton(
                                icon: const Icon(Icons.save_alt_rounded, color: Colors.green),
                                tooltip: "Save to device",
                                onPressed: () async {
                                  final messenger = ScaffoldMessenger.of(context);
                                  try {
                                    await _downloadService.exportToPublicDownloads(_localFilename);
                                    messenger.showSnackBar(
                                      const SnackBar(
                                        content: Text("Saved to Downloads folder"),
                                        backgroundColor: Colors.green,
                                      ),
                                    );
                                  } catch (e) {
                                    messenger.showSnackBar(
                                      SnackBar(
                                        content: Text("Failed to save: ${e.toString()}"),
                                        backgroundColor: Colors.red,
                                      ),
                                    );
                                  }
                                },
                              ),
                            ],
                            Icon(
                              _isDownloaded
                                  ? (_isImage ? Icons.image_search_rounded : Icons.menu_book_rounded)
                                  : Icons.download_rounded,
                              color: theme.colorScheme.primary,
                            ),
                          ],
                        ),
                  onTap: _handleDownloadOrOpen,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}


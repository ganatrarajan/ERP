import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../../providers/homework_provider.dart';
import '../../../data/models/homework_model.dart';
import '../../../core/constants/api_endpoints.dart';
import '../../../core/services/download_service.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/error_view.dart';
import '../../widgets/empty_view.dart';
import '../pdf_viewer/pdf_viewer_screen.dart';
import '../pdf_viewer/image_viewer_screen.dart';

class HomeworkScreen extends StatefulWidget {
  const HomeworkScreen({super.key});

  @override
  State<HomeworkScreen> createState() => _HomeworkScreenState();
}

class _HomeworkScreenState extends State<HomeworkScreen> {
  final DownloadService _downloadService = DownloadService();
  final Map<int, double> _progressMap = {};
  final Map<int, bool> _downloadedMap = {};
  List<String>? _lastAttachments;

  bool _attachmentsChanged(List<HomeworkModel> homeworks) {
    if (_lastAttachments == null || _lastAttachments!.length != homeworks.length) {
      return true;
    }
    for (int i = 0; i < homeworks.length; i++) {
      if (_lastAttachments![i] != (homeworks[i].attachment ?? '')) {
        return true;
      }
    }
    return false;
  }

  bool _isImage(HomeworkModel hw) {
    final attachment = hw.attachment;
    if (attachment == null) return false;
    final path = attachment.toLowerCase();
    return path.endsWith('.png') ||
        path.endsWith('.jpg') ||
        path.endsWith('.jpeg') ||
        path.endsWith('.webp') ||
        path.endsWith('.gif');
  }

  String _fileExtension(HomeworkModel hw) {
    final attachment = hw.attachment;
    if (attachment == null) return 'pdf';
    final parts = attachment.split('.');
    if (parts.length > 1) {
      return parts.last.toLowerCase();
    }
    return _isImage(hw) ? 'jpg' : 'pdf';
  }

  String _localFilename(HomeworkModel hw) {
    final attachment = hw.attachment;
    if (attachment == null || attachment.isEmpty) {
      return 'homework_${hw.id}.pdf';
    }
    final uri = Uri.parse(attachment);
    final fileName = uri.pathSegments.isNotEmpty ? uri.pathSegments.last : 'attachment';
    final decodedFileName = Uri.decodeComponent(fileName);
    if (decodedFileName.contains('.')) {
      return 'homework_${hw.id}_$decodedFileName';
    } else {
      return 'homework_${hw.id}_$decodedFileName.${_fileExtension(hw)}';
    }
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final prov = context.read<HomeworkProvider>();
      prov.fetchHomework();
      prov.fetchSubjects();
    });
  }

  Future<void> _checkDownloaded(List<HomeworkModel> homeworks) async {
    for (var hw in homeworks) {
      if (hw.attachment != null) {
        final filename = _localFilename(hw);
        final isDownloaded = await _downloadService.isFileDownloaded(filename);
        if (mounted && _downloadedMap[hw.id] != isDownloaded) {
          setState(() {
            _downloadedMap[hw.id] = isDownloaded;
          });
        }
      }
    }
  }

  Future<void> _handleDownloadOrOpen(HomeworkModel hw) async {
    if (hw.attachment == null) return;

    final hwId = hw.id;
    final filename = _localFilename(hw);
    final isDownloaded = _downloadedMap[hwId] ?? false;

    if (isDownloaded) {
      // Open file
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => _isImage(hw)
              ? ImageViewerScreen(
                  title: hw.title,
                  filename: filename,
                )
              : PdfViewerScreen(
                  title: hw.title,
                  filename: filename,
                ),
        ),
      );
    } else {
      final messenger = ScaffoldMessenger.of(context);
      final errorColor = Theme.of(context).colorScheme.error;

      // Download
      setState(() {
        _progressMap[hwId] = 0.05;
      });

      try {
        final absoluteUrl = ApiEndpoints.resolveAttachmentUrl(hw.attachment);

        await _downloadService.downloadFile(
          url: absoluteUrl,
          filename: filename,
          onProgress: (rec, tot) {
            if (tot > 0 && mounted) {
              setState(() {
                _progressMap[hwId] = rec / tot;
              });
            }
          },
        );

        if (mounted) {
          setState(() {
            _downloadedMap[hwId] = true;
            _progressMap.remove(hwId);
          });
          messenger.showSnackBar(
            SnackBar(
              content: Text("Downloaded ${hw.title} attachment successfully!"),
              backgroundColor: Colors.green,
            ),
          );
        }
      } catch (e) {
        if (mounted) {
          setState(() {
            _progressMap.remove(hwId);
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
    final hwProv = context.watch<HomeworkProvider>();

    // Triggers local download cache audits whenever homework list updates
    if (hwProv.homeworks.isNotEmpty) {
      if (_attachmentsChanged(hwProv.homeworks)) {
        _lastAttachments = hwProv.homeworks.map((e) => e.attachment ?? '').toList();
        _checkDownloaded(hwProv.homeworks);
      }
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text("Homework Assigned"),
        actions: [
          if (hwProv.selectedSubjectId != null || hwProv.selectedDate != null)
            IconButton(
              icon: const Icon(Icons.filter_alt_off_rounded),
              onPressed: () => hwProv.clearFilters(),
              tooltip: "Clear Filters",
            ),
        ],
      ),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Filter Row / Chips
          _buildFilterBar(hwProv, theme),
          const Divider(height: 1),

          Expanded(
            child: RefreshIndicator(
              onRefresh: () async {
                await Future.wait([
                  hwProv.fetchHomework(),
                  hwProv.fetchSubjects(),
                ]);
              },
              child: _buildList(hwProv, theme),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterBar(HomeworkProvider prov, ThemeData theme) {
    final selectedSubject = prov.selectedSubjectId != null && prov.subjects.isNotEmpty
        ? prov.subjects.firstWhere(
            (s) => s.id == prov.selectedSubjectId,
            orElse: () => SubjectModel(id: 0, name: '', code: ''),
          )
        : null;
    final buttonLabel = selectedSubject != null && selectedSubject.id != 0
        ? selectedSubject.name
        : "Select Subject";

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 10.0),
      child: Row(
        children: [
          // Date Filter Button
          Expanded(
            child: OutlinedButton.icon(
              icon: const Icon(Icons.date_range_rounded, size: 18),
              label: Text(
                prov.selectedDate != null
                    ? DateFormat('MMM d, yyyy').format(prov.selectedDate!)
                    : "Due Date",
                overflow: TextOverflow.ellipsis,
              ),
              onPressed: () async {
                final date = await showDatePicker(
                  context: context,
                  initialDate: DateTime.now(),
                  firstDate: DateTime.now().subtract(const Duration(days: 365)),
                  lastDate: DateTime.now().add(const Duration(days: 365)),
                );
                if (date != null) {
                  prov.setDateFilter(date);
                }
              },
              style: OutlinedButton.styleFrom(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
          ),
          const SizedBox(width: 12),
          // Subject Filter button loaded dynamically
          Expanded(
            child: OutlinedButton.icon(
              icon: const Icon(Icons.subject_rounded, size: 18),
              label: Text(
                buttonLabel,
                overflow: TextOverflow.ellipsis,
              ),
              onPressed: () {
                showModalBottomSheet(
                  context: context,
                  builder: (context) => SafeArea(
                    child: ListView(
                      shrinkWrap: true,
                      padding: const EdgeInsets.all(16),
                      children: [
                        Text("Filter by Subject", style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold)),
                        const SizedBox(height: 12),
                        ListTile(
                          title: const Text("All Subjects"),
                          onTap: () {
                            prov.setSubjectFilter(null);
                            Navigator.pop(context);
                          },
                        ),
                        ...prov.subjects.map((sub) => ListTile(
                          title: Text(
                            (sub.code.trim().isNotEmpty && sub.code.trim().toLowerCase() != 'null')
                                ? "${sub.name} (${sub.code})"
                                : sub.name,
                          ),
                          onTap: () {
                            prov.setSubjectFilter(sub.id);
                            Navigator.pop(context);
                          },
                        )),
                      ],
                    ),
                  ),
                );
              },
              style: OutlinedButton.styleFrom(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildList(HomeworkProvider prov, ThemeData theme) {
    if (prov.isLoading && prov.homeworks.isEmpty) {
      return const LoadingView(message: "Loading assignments feed...");
    }

    if (prov.errorMessage != null && prov.homeworks.isEmpty) {
      return ErrorView(
        message: prov.errorMessage!,
        onRetry: () => prov.fetchHomework(),
      );
    }

    if (prov.homeworks.isEmpty) {
      return const EmptyView(
        title: "No Homework Found",
        description: "Great job! There are no homework assignments pending filter parameters.",
        icon: Icons.checklist_rtl_rounded,
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: prov.homeworks.length,
      separatorBuilder: (context, index) => const SizedBox(height: 12),
      itemBuilder: (context, index) {
        final hw = prov.homeworks[index];
        final isDownloaded = _downloadedMap[hw.id] ?? false;
        final downloadProgress = _progressMap[hw.id];

        // Format dates nicely
        String formattedDueDate = hw.submissionDate;
        try {
          final pDate = DateTime.parse(hw.submissionDate);
          formattedDueDate = DateFormat('EEEE, MMM dd, yyyy').format(pDate);
        } catch (_) {}

        return Card(
          margin: EdgeInsets.zero,
          child: Padding(
            padding: const EdgeInsets.all(16.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: theme.colorScheme.primary.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        hw.subject?.code ?? 'GENERAL',
                        style: TextStyle(
                          color: theme.colorScheme.primary,
                          fontWeight: FontWeight.bold,
                          fontSize: 11,
                        ),
                      ),
                    ),
                    Text(
                      "Due: $formattedDueDate",
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: Colors.red[700],
                        fontWeight: FontWeight.w600,
                        fontSize: 12,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Text(
                  hw.title,
                  style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 6),
                Text(
                  hw.description,
                  style: theme.textTheme.bodyLarge,
                ),
                const SizedBox(height: 16),
                const Divider(height: 1),
                const SizedBox(height: 12),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      hw.subject?.name ?? 'General Assignment',
                      style: theme.textTheme.bodyMedium,
                    ),
                    if (hw.attachment != null) ...[
                      downloadProgress != null
                          ? SizedBox(
                              height: 36,
                              width: 120,
                              child: Center(
                                child: LinearProgressIndicator(
                                  value: downloadProgress,
                                  borderRadius: BorderRadius.circular(4),
                                ),
                              ),
                            )
                          : Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                if (isDownloaded) ...[
                                  TextButton.icon(
                                    onPressed: () async {
                                      final messenger = ScaffoldMessenger.of(context);
                                      try {
                                        final filename = _localFilename(hw);
                                        await _downloadService.exportToPublicDownloads(filename);
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
                                    icon: const Icon(Icons.save_alt_rounded, size: 16),
                                    label: const Text("Save", style: TextStyle(fontSize: 12)),
                                    style: TextButton.styleFrom(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
                                      minimumSize: Size.zero,
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                ],
                                ElevatedButton.icon(
                                  onPressed: () => _handleDownloadOrOpen(hw),
                                  icon: Icon(
                                    isDownloaded
                                        ? (_isImage(hw) ? Icons.image_search_rounded : Icons.menu_book_rounded)
                                        : Icons.download_rounded,
                                    size: 16,
                                  ),
                                  label: Text(
                                    isDownloaded
                                        ? (_isImage(hw) ? "View Image" : "View PDF")
                                        : (_isImage(hw) ? "Download Image" : "Download PDF"),
                                    style: const TextStyle(fontSize: 12),
                                  ),
                                  style: ElevatedButton.styleFrom(
                                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                                    minimumSize: Size.zero,
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                  ),
                                ),
                              ],
                            ),
                    ] else ...[
                      const Text(
                        "No Attachments",
                        style: TextStyle(fontSize: 11, fontStyle: FontStyle.italic),
                      ),
                    ],
                  ],
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}

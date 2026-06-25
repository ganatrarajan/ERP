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

class HomeworkScreen extends StatefulWidget {
  const HomeworkScreen({super.key});

  @override
  State<HomeworkScreen> createState() => _HomeworkScreenState();
}

class _HomeworkScreenState extends State<HomeworkScreen> {
  final DownloadService _downloadService = DownloadService();
  final Map<int, double> _progressMap = {};
  final Map<int, bool> _downloadedMap = {};

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<HomeworkProvider>().fetchHomework();
    });
  }

  Future<void> _checkDownloaded(List<HomeworkModel> homeworks) async {
    for (var hw in homeworks) {
      if (hw.attachment != null) {
        final filename = 'homework_${hw.id}.pdf';
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
    final filename = 'homework_$hwId.pdf';
    final isDownloaded = _downloadedMap[hwId] ?? false;

    if (isDownloaded) {
      // Open file
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => PdfViewerScreen(
            title: hw.title,
            filename: filename,
          ),
        ),
      );
    } else {
      // Download
      setState(() {
        _progressMap[hwId] = 0.05;
      });

      try {
        final absoluteUrl = hw.attachment!.startsWith('http')
            ? hw.attachment!
            : '${ApiEndpoints.baseUrl}/${hw.attachment}';

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
          ScaffoldMessenger.of(context).showSnackBar(
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
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text("Failed to download attachment: ${e.toString()}"),
              backgroundColor: Theme.of(context).colorScheme.error,
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
      _checkDownloaded(hwProv.homeworks);
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
              onRefresh: () => hwProv.fetchHomework(),
              child: _buildList(hwProv, theme),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterBar(HomeworkProvider prov, ThemeData theme) {
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
          // Subject Filter button placeholder/mock options since subject list isn't an explicit endpoint
          Expanded(
            child: OutlinedButton.icon(
              icon: const Icon(Icons.subject_rounded, size: 18),
              label: Text(
                prov.selectedSubjectId != null
                    ? "Sub ID: ${prov.selectedSubjectId}"
                    : "Select Subject",
                overflow: TextOverflow.ellipsis,
              ),
              onPressed: () {
                // Mock selection menu for demonstration
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
                        ListTile(
                          title: const Text("Mathematics"),
                          onTap: () {
                            prov.setSubjectFilter(2);
                            Navigator.pop(context);
                          },
                        ),
                        ListTile(
                          title: const Text("Science"),
                          onTap: () {
                            prov.setSubjectFilter(3);
                            Navigator.pop(context);
                          },
                        ),
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
                          : ElevatedButton.icon(
                              onPressed: () => _handleDownloadOrOpen(hw),
                              icon: Icon(
                                isDownloaded ? Icons.menu_book_rounded : Icons.download_rounded,
                                size: 16,
                              ),
                              label: Text(
                                isDownloaded ? "View PDF" : "Download PDF",
                                style: const TextStyle(fontSize: 12),
                              ),
                              style: ElevatedButton.styleFrom(
                                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                                minimumSize: Size.zero,
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              ),
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

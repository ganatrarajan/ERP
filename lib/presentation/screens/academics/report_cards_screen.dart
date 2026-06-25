import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../providers/report_card_provider.dart';
import '../../../data/models/report_card_model.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/error_view.dart';
import '../../widgets/empty_view.dart';
import '../pdf_viewer/pdf_viewer_screen.dart';

class ReportCardsScreen extends StatefulWidget {
  const ReportCardsScreen({super.key});

  @override
  State<ReportCardsScreen> createState() => _ReportCardsScreenState();
}

class _ReportCardsScreenState extends State<ReportCardsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<ReportCardProvider>().fetchReportCards();
    });
  }

  Future<void> _handleDownload(ReportCardProvider prov, ReportCardModel rc) async {
    try {
      final path = await prov.downloadReportCard(rc);
      if (path != null && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text("Downloaded ${rc.examName} Report Card successfully!"),
            backgroundColor: Colors.green,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text("Failed to download PDF: ${e.toString()}"),
            backgroundColor: Theme.of(context).colorScheme.error,
          ),
        );
      }
    }
  }

  void _handleView(ReportCardModel rc) {
    final filename = 'report_card_${rc.id}.pdf';
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => PdfViewerScreen(
          title: "${rc.examName} Report Card",
          filename: filename,
        ),
      ),
    );
  }

  Future<void> _handleShare(ReportCardProvider prov, ReportCardModel rc) async {
    final examId = rc.id;
    final isDownloaded = prov.isFileDownloaded(examId);

    try {
      if (!isDownloaded) {
        // Automatically download first
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text("Downloading report card for sharing...")),
        );
        await prov.downloadReportCard(rc);
      }
      await prov.shareReportCard(examId, rc.examName);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text("Failed to share report card: ${e.toString()}"),
            backgroundColor: Theme.of(context).colorScheme.error,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final rcProv = context.watch<ReportCardProvider>();

    return Scaffold(
      appBar: AppBar(
        title: const Text("Report Cards"),
      ),
      body: RefreshIndicator(
        onRefresh: () => rcProv.fetchReportCards(),
        child: _buildBody(rcProv, theme),
      ),
    );
  }

  Widget _buildBody(ReportCardProvider prov, ThemeData theme) {
    if (prov.isLoading && prov.reportCards.isEmpty) {
      return const LoadingView(message: "Retrieving published report cards...");
    }

    if (prov.errorMessage != null && prov.reportCards.isEmpty) {
      return ErrorView(
        message: prov.errorMessage!,
        onRetry: () => prov.fetchReportCards(),
      );
    }

    if (prov.reportCards.isEmpty) {
      return const EmptyView(
        title: "No Report Cards Published",
        description: "Official signed report cards will be shown here once generated and published.",
        icon: Icons.picture_as_pdf_outlined,
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: prov.reportCards.length,
      separatorBuilder: (context, index) => const SizedBox(height: 12),
      itemBuilder: (context, index) {
        final rc = prov.reportCards[index];
        final isDownloaded = prov.isFileDownloaded(rc.id);
        final downloadProgress = prov.getDownloadProgress(rc.id);

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
                    Expanded(
                      child: Text(
                        rc.examName,
                        style: theme.textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                    Icon(
                      Icons.verified_user_rounded,
                      color: theme.colorScheme.primary,
                      size: 20,
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Text(
                  "Assessment Period: ${rc.startDate} to ${rc.endDate}",
                  style: theme.textTheme.bodyMedium,
                ),
                const SizedBox(height: 16),
                const Divider(height: 1),
                const SizedBox(height: 12),
                Row(
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    // Share
                    TextButton.icon(
                      onPressed: () => _handleShare(prov, rc),
                      icon: const Icon(Icons.share_rounded, size: 18),
                      label: const Text("Share", style: TextStyle(fontSize: 12)),
                    ),
                    const SizedBox(width: 8),

                    // Download / View
                    if (downloadProgress > 0 && downloadProgress < 1.0)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        height: 36,
                        width: 120,
                        child: Center(
                          child: LinearProgressIndicator(
                            value: downloadProgress,
                            borderRadius: BorderRadius.circular(4),
                          ),
                        ),
                      )
                    else if (isDownloaded) ...[
                      TextButton.icon(
                        onPressed: () async {
                          try {
                            await prov.exportReportCard(rc.id);
                            if (mounted) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text("Saved to Downloads folder"), backgroundColor: Colors.green),
                              );
                            }
                          } catch (e) {
                            if (mounted) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(e.toString()), backgroundColor: Colors.red),
                              );
                            }
                          }
                        },
                        icon: const Icon(Icons.save_alt_rounded, size: 16),
                        label: const Text("Save", style: TextStyle(fontSize: 12)),
                      ),
                      const SizedBox(width: 8),
                      ElevatedButton.icon(
                        onPressed: () => _handleView(rc),
                        icon: const Icon(Icons.menu_book_rounded, size: 16),
                        label: const Text("View PDF", style: TextStyle(fontSize: 12)),
                        style: ElevatedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                          minimumSize: Size.zero,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        ),
                      )
                    ] else
                      ElevatedButton.icon(
                        onPressed: () => _handleDownload(prov, rc),
                        icon: const Icon(Icons.cloud_download_rounded, size: 16),
                        label: const Text("Download", style: TextStyle(fontSize: 12)),
                        style: ElevatedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                          minimumSize: Size.zero,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        ),
                      ),
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

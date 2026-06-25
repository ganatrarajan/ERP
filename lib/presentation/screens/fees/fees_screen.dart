import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../providers/fee_provider.dart';
import '../../../providers/receipt_provider.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/error_view.dart';
import '../../widgets/empty_view.dart';
import '../pdf_viewer/pdf_viewer_screen.dart';

class FeesScreen extends StatefulWidget {
  const FeesScreen({super.key});

  @override
  State<FeesScreen> createState() => _FeesScreenState();
}

class _FeesScreenState extends State<FeesScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);

    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<FeeProvider>().fetchFees();
      context.read<ReceiptProvider>().fetchReceipts();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text("Fees & Receipts"),
        bottom: TabBar(
          controller: _tabController,
          indicatorSize: TabBarIndicatorSize.tab,
          tabs: const [
            Tab(text: "Summary & Dues"),
            Tab(text: "Receipts History"),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildSummaryTab(theme),
          _buildReceiptsTab(theme),
        ],
      ),
    );
  }

  // --- TAB 1: FEES SUMMARY & INSTALLMENTS ---
  Widget _buildSummaryTab(ThemeData theme) {
    final feeProv = context.watch<FeeProvider>();

    if (feeProv.isLoading && feeProv.feeResponse == null) {
      return const LoadingView(message: "Loading fee ledger details...");
    }

    if (feeProv.errorMessage != null && feeProv.feeResponse == null) {
      return ErrorView(
        message: feeProv.errorMessage!,
        onRetry: () => feeProv.fetchFees(),
      );
    }

    final data = feeProv.feeResponse;
    if (data == null) {
      return const Center(child: Text("No fee summary logs found"));
    }

    final total = data.totalFees;
    final paid = data.paidFees;
    final pending = data.pendingFees;
    final paidPercent = total > 0 ? (paid / total) : 0.0;

    // Check if there are overdue installments
    final hasOverdue = data.installments.any((inst) => inst.isOverdue);

    return RefreshIndicator(
      onRefresh: () => feeProv.fetchFees(),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Overdue Banner
            if (hasOverdue) ...[
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                decoration: BoxDecoration(
                  color: Colors.red[50],
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.red[200]!),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.warning_amber_rounded, color: Colors.red, size: 24),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        "Attention: You have overdue fee installments. Please clear outstanding balances immediately to avoid late fines.",
                        style: TextStyle(
                          color: Colors.red[900],
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),
            ],

            // Ledger Cards Progress
            Card(
              margin: EdgeInsets.zero,
              child: Padding(
                padding: const EdgeInsets.all(20.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      "Fee Collection Progress",
                      style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 16),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        _buildFeeStat("Total structure", "\$${total.toStringAsFixed(2)}", theme),
                        _buildFeeStat("Total Paid", "\$${paid.toStringAsFixed(2)}", theme, color: Colors.green),
                        _buildFeeStat("Pending", "\$${pending.toStringAsFixed(2)}", theme, color: Colors.orange),
                      ],
                    ),
                    const SizedBox(height: 24),
                    LinearProgressIndicator(
                      value: paidPercent,
                      backgroundColor: theme.colorScheme.surfaceContainerHighest,
                      valueColor: AlwaysStoppedAnimation<Color>(theme.colorScheme.primary),
                      minHeight: 8,
                      borderRadius: BorderRadius.circular(4),
                    ),
                    const SizedBox(height: 8),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          "${(paidPercent * 100).toStringAsFixed(1)}% Completed",
                          style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.bold),
                        ),
                        Text(
                          "\$${pending.toStringAsFixed(2)} remaining",
                          style: theme.textTheme.bodyMedium,
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),

            // Installment Breakdown
            Text(
              "Installment Breakdown",
              style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 12),

            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: data.installments.length,
              separatorBuilder: (context, index) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                final inst = data.installments[index];
                final isPaid = inst.status.toLowerCase() == 'paid';
                final isOver = inst.isOverdue;

                return Card(
                  margin: EdgeInsets.zero,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                    side: BorderSide(
                      color: isPaid
                          ? Colors.green.withOpacity(0.3)
                          : isOver
                              ? Colors.red.withOpacity(0.3)
                              : theme.dividerColor,
                      width: 1,
                    ),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              inst.name,
                              style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(
                                color: (isPaid
                                        ? Colors.green
                                        : isOver
                                            ? Colors.red
                                            : Colors.orange)
                                    .withOpacity(0.12),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Text(
                                inst.status.toUpperCase(),
                                style: TextStyle(
                                  color: isPaid
                                      ? Colors.green
                                      : isOver
                                          ? Colors.red
                                          : Colors.orange,
                                  fontWeight: FontWeight.w800,
                                  fontSize: 10,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceAround,
                          children: [
                            _buildInstallmentCol("Amount", "\$${inst.amount}", theme),
                            _buildInstallmentCol("Paid", "\$${inst.paid}", theme),
                            _buildInstallmentCol("Due Balance", "\$${inst.remainingDue}", theme, color: inst.remainingDue > 0 ? Colors.red : null),
                          ],
                        ),
                        const SizedBox(height: 16),
                        const Divider(height: 1),
                        const SizedBox(height: 12),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              "Due Date: ${inst.dueDate}",
                              style: theme.textTheme.bodyMedium?.copyWith(
                                color: isOver && !isPaid ? Colors.red : theme.textTheme.bodyMedium?.color,
                                fontWeight: isOver && !isPaid ? FontWeight.bold : FontWeight.normal,
                              ),
                            ),
                            if (inst.overdueDays > 0 && !isPaid)
                              Text(
                                "(${inst.overdueDays} days overdue)",
                                style: const TextStyle(color: Colors.red, fontWeight: FontWeight.bold, fontSize: 11),
                              ),
                          ],
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFeeStat(String label, String value, ThemeData theme, {Color? color}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: theme.textTheme.bodyMedium?.copyWith(fontSize: 11)),
        const SizedBox(height: 4),
        Text(
          value,
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w800,
            color: color,
          ),
        ),
      ],
    );
  }

  Widget _buildInstallmentCol(String label, String value, ThemeData theme, {Color? color}) {
    return Column(
      children: [
        Text(label, style: theme.textTheme.bodyMedium?.copyWith(fontSize: 10)),
        const SizedBox(height: 2),
        Text(
          value,
          style: theme.textTheme.bodyLarge?.copyWith(
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
      ],
    );
  }

  // --- TAB 2: RECEIPTS LIST & ACTIONABLE ACTIONS ---
  Widget _buildReceiptsTab(ThemeData theme) {
    final receiptProv = context.watch<ReceiptProvider>();

    if (receiptProv.isLoading && receiptProv.receipts.isEmpty) {
      return const LoadingView(message: "Loading transaction receipt logs...");
    }

    if (receiptProv.errorMessage != null && receiptProv.receipts.isEmpty) {
      return ErrorView(
        message: receiptProv.errorMessage!,
        onRetry: () => receiptProv.fetchReceipts(),
      );
    }

    if (receiptProv.receipts.isEmpty) {
      return const EmptyView(
        title: "No Receipts Documented",
        description: "Official payment receipt vouchers will be generated as payments are processed.",
        icon: Icons.account_balance_wallet_outlined,
      );
    }

    return RefreshIndicator(
      onRefresh: () => receiptProv.fetchReceipts(),
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: receiptProv.receipts.length,
        separatorBuilder: (context, index) => const SizedBox(height: 12),
        itemBuilder: (context, index) {
          final receipt = receiptProv.receipts[index];
          final isDownloaded = receiptProv.isFileDownloaded(receipt.id);
          final downloadProgress = receiptProv.getDownloadProgress(receipt.id);

          return Card(
            margin: EdgeInsets.zero,
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        receipt.receiptNumber,
                        style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                      ),
                      Text(
                        "\$${receipt.amountPaid.toStringAsFixed(2)}",
                        style: theme.textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w800,
                          color: Colors.green,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    "Installment: ${receipt.installmentName}",
                    style: theme.textTheme.bodyMedium,
                  ),
                  Text(
                    "Date Paid: ${receipt.paymentDate}",
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
                        onPressed: () async {
                          try {
                            if (!isDownloaded) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text("Downloading receipt for sharing...")),
                              );
                              await receiptProv.downloadReceipt(receipt);
                            }
                            await receiptProv.shareReceipt(receipt.id, receipt.receiptNumber);
                          } catch (e) {
                            if (mounted) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(
                                  content: Text("Failed to share receipt: ${e.toString()}"),
                                  backgroundColor: theme.colorScheme.error,
                                ),
                              );
                            }
                          }
                        },
                        icon: const Icon(Icons.share_rounded, size: 18),
                        label: const Text("Share", style: TextStyle(fontSize: 12)),
                      ),
                      const SizedBox(width: 8),

                      // View / Download
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
                              await receiptProv.exportReceipt(receipt.id);
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
                          onPressed: () {
                            final filename = 'receipt_${receipt.id}.pdf';
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (context) => PdfViewerScreen(
                                  title: "Receipt ${receipt.receiptNumber}",
                                  filename: filename,
                                ),
                              ),
                            );
                          },
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
                          onPressed: () async {
                            try {
                              await receiptProv.downloadReceipt(receipt);
                            } catch (e) {
                              if (mounted) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text("Download failed: ${e.toString()}"),
                                    backgroundColor: theme.colorScheme.error,
                                  ),
                                );
                              }
                            }
                          },
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
      ),
    );
  }
}

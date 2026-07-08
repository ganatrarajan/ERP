import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../data/models/payment_history_model.dart';
import '../../../data/models/receipt_model.dart';
import '../../../providers/auth_provider.dart';
import '../../../providers/receipt_provider.dart';

class PaymentDetailsScreen extends StatefulWidget {
  final PaymentHistoryModel transaction;

  const PaymentDetailsScreen({
    super.key,
    required this.transaction,
  });

  @override
  State<PaymentDetailsScreen> createState() => _PaymentDetailsScreenState();
}

class _PaymentDetailsScreenState extends State<PaymentDetailsScreen> {
  bool _isDownloading = false;

  void _downloadReceipt(BuildContext context, ReceiptModel receipt) async {
    setState(() => _isDownloading = true);
    final receiptProv = context.read<ReceiptProvider>();

    try {
      final path = await receiptProv.downloadReceipt(receipt);
      if (path != null && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text("Receipt saved to device."), backgroundColor: Colors.green),
        );
        await receiptProv.openReceipt(receipt.id);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text("Failed to download: $e"), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isDownloading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final authProv = context.watch<AuthProvider>();
    final receiptProv = context.watch<ReceiptProvider>();

    final student = authProv.student;
    final schoolName = student?.schoolName ?? 'N/A';
    final studentName = student?.fullName ?? 'N/A';

    final isSuccess = widget.transaction.status.toLowerCase() == 'successful';
    final isFailed = widget.transaction.status.toLowerCase() == 'failed';

    // Find matching receipt model from receipts list
    ReceiptModel? matchingReceipt;
    if (widget.transaction.receiptNo != null) {
      try {
        matchingReceipt = receiptProv.receipts.firstWhere(
          (r) => r.receiptNumber.toLowerCase() == widget.transaction.receiptNo!.toLowerCase(),
        );
      } catch (_) {
        matchingReceipt = null;
      }
    }

    final canDownloadReceipt = isSuccess && widget.transaction.receiptNo != null && matchingReceipt != null;
    final progress = matchingReceipt != null ? receiptProv.getDownloadProgress(matchingReceipt.id) : 0.0;
    final isDownloaded = matchingReceipt != null ? receiptProv.isFileDownloaded(matchingReceipt.id) : false;

    return Scaffold(
      appBar: AppBar(
        title: const Text("Transaction Details"),
      ),
      body: Stack(
        children: [
          SingleChildScrollView(
            padding: const EdgeInsets.all(16.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Header Info
                Card(
                  margin: EdgeInsets.zero,
                  child: Padding(
                    padding: const EdgeInsets.all(20.0),
                    child: Column(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: (isSuccess
                                    ? Colors.green
                                    : isFailed
                                        ? Colors.red
                                        : Colors.orange)
                                .withOpacity(0.12),
                            shape: BoxShape.circle,
                          ),
                          child: Icon(
                            isSuccess
                                ? Icons.check_circle_rounded
                                : (isFailed ? Icons.cancel_rounded : Icons.pending_actions_rounded),
                            color: isSuccess ? Colors.green : (isFailed ? Colors.red : Colors.orange),
                            size: 48,
                          ),
                        ),
                        const SizedBox(height: 16),
                        Text(
                          isSuccess
                              ? "Transaction Successful"
                              : (isFailed ? "Transaction Failed" : "Transaction Pending"),
                          style: theme.textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                            color: isSuccess ? Colors.green : (isFailed ? Colors.red : Colors.orange),
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          "₹${widget.transaction.amount.toStringAsFixed(2)}",
                          style: theme.textTheme.headlineLarge?.copyWith(
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // Details Card
                Card(
                  margin: EdgeInsets.zero,
                  child: Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          "Metadata & Gateway Details",
                          style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 16),
                        _buildRow("School Name", schoolName),
                        _buildRow("Student Name", studentName),
                        _buildRow("Fee Installment", widget.transaction.installmentName),
                        _buildRow("Gateway", "Razorpay"),
                        if (widget.transaction.receiptNo != null)
                          _buildRow("Receipt Number", widget.transaction.receiptNo!),
                        if (widget.transaction.paymentId != null)
                          _buildRow("Transaction ID", widget.transaction.paymentId!),
                        _buildRow("Date & Time", widget.transaction.transactionDate),
                        _buildRow("Status", widget.transaction.status.toUpperCase(),
                            valueColor: isSuccess
                                ? Colors.green
                                : (isFailed ? Colors.red : Colors.orange)),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 32),

                // Download Receipt Button
                if (canDownloadReceipt) ...[
                  if (progress > 0 && progress < 1.0)
                    const Center(child: CircularProgressIndicator())
                  else if (isDownloaded)
                    ElevatedButton.icon(
                      onPressed: () {
                        receiptProv.openReceipt(matchingReceipt!.id);
                      },
                      icon: const Icon(Icons.picture_as_pdf),
                      label: const Text("View PDF Receipt"),
                    )
                  else
                    ElevatedButton.icon(
                      onPressed: _isDownloading ? null : () => _downloadReceipt(context, matchingReceipt!),
                      icon: const Icon(Icons.cloud_download),
                      label: const Text("Download Receipt"),
                    ),
                ],
              ],
            ),
          ),
          if (_isDownloading)
            Container(
              color: Colors.black26,
              child: const Center(
                child: CircularProgressIndicator(),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildRow(String label, String value, {Color? valueColor}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(color: Colors.grey, fontSize: 13)),
          const SizedBox(width: 16),
          Flexible(
            child: Text(
              value,
              style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 13,
                color: valueColor,
              ),
              textAlign: TextAlign.end,
            ),
          ),
        ],
      ),
    );
  }
}

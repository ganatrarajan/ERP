import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../providers/receipt_provider.dart';
import '../../../data/models/receipt_model.dart';
import '../../widgets/loading_view.dart';
import '../main_navigation_screen.dart';

class PaymentSuccessScreen extends StatefulWidget {
  final String receiptNumber;
  final String transactionId;
  final double amount;
  final String paymentMethod;
  final String dateTime;
  final int? receiptId;

  const PaymentSuccessScreen({
    super.key,
    required this.receiptNumber,
    required this.transactionId,
    required this.amount,
    required this.paymentMethod,
    required this.dateTime,
    this.receiptId,
  });

  @override
  State<PaymentSuccessScreen> createState() => _PaymentSuccessScreenState();
}

class _PaymentSuccessScreenState extends State<PaymentSuccessScreen> with SingleTickerProviderStateMixin {
  late AnimationController _animController;
  late Animation<double> _scaleAnimation;
  bool _isDownloading = false;

  @override
  void initState() {
    super.initState();
    _animController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 800),
    );
    _scaleAnimation = CurvedAnimation(
      parent: _animController,
      curve: Curves.elasticOut,
    );
    _animController.forward();
  }

  @override
  void dispose() {
    _animController.dispose();
    super.dispose();
  }

  void _downloadReceipt(BuildContext context, int receiptId) async {
    setState(() => _isDownloading = true);
    final receiptProv = context.read<ReceiptProvider>();

    try {
      // Find matching receipt model or construct a dummy one if it hasn't loaded
      ReceiptModel? targetReceipt;
      try {
        targetReceipt = receiptProv.receipts.firstWhere((r) => r.id == receiptId);
      } catch (_) {
        targetReceipt = ReceiptModel(
          id: receiptId,
          receiptNumber: widget.receiptNumber,
          amountPaid: widget.amount,
          paymentDate: widget.dateTime.split(' ')[0],
          installmentName: '',
          pdfUrl: '',
        );
      }

      final localPath = await receiptProv.downloadReceipt(targetReceipt);
      if (localPath != null && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text("Receipt saved to device."), backgroundColor: Colors.green),
        );
        // Open the document
        await receiptProv.openReceipt(receiptId);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text("Download failed: $e"), backgroundColor: Colors.red),
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

    return Scaffold(
      appBar: AppBar(
        title: const Text("Payment Success"),
        automaticallyImplyLeading: false,
      ),
      body: Stack(
        children: [
          SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 36.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Animated Success Checkmark
                ScaleTransition(
                  scale: _scaleAnimation,
                  child: Container(
                    padding: const EdgeInsets.all(24),
                    decoration: BoxDecoration(
                      color: Colors.green.withOpacity(0.12),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.check_circle_rounded,
                      color: Colors.green,
                      size: 96,
                    ),
                  ),
                ),
                const SizedBox(height: 24),

                Text(
                  "Payment Successful",
                  style: theme.textTheme.headlineMedium?.copyWith(
                    fontWeight: FontWeight.w800,
                    color: Colors.green,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 8),
                Text(
                  "Your fee installment transaction has been securely captured.",
                  style: theme.textTheme.bodyMedium,
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 36),

                // Transaction Detail Card
                Card(
                  margin: EdgeInsets.zero,
                  child: Padding(
                    padding: const EdgeInsets.all(20.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          "Transaction Summary",
                          style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 16),
                        _buildRow("Receipt Number", widget.receiptNumber),
                        _buildRow("Transaction ID", widget.transactionId),
                        _buildRow("Payment Method", widget.paymentMethod),
                        _buildRow("Date & Time", widget.dateTime),
                        const Divider(height: 24),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              "Amount Paid",
                              style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                            ),
                            Text(
                              "₹${widget.amount.toStringAsFixed(2)}",
                              style: theme.textTheme.titleLarge?.copyWith(
                                color: Colors.green,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 48),

                // Buttons
                if (widget.receiptId != null) ...[
                  ElevatedButton.icon(
                    onPressed: _isDownloading ? null : () => _downloadReceipt(context, widget.receiptId!),
                    icon: const Icon(Icons.download_rounded),
                    label: const Text("Download Receipt"),
                  ),
                  const SizedBox(height: 12),
                ],

                OutlinedButton(
                  onPressed: () {
                    Navigator.pushAndRemoveUntil(
                      context,
                      MaterialPageRoute(builder: (_) => const MainNavigationScreen()),
                      (route) => false,
                    );
                  },
                  style: OutlinedButton.styleFrom(
                    minimumSize: const Size.fromHeight(56),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: const Text("Back to Dashboard"),
                ),
                const SizedBox(height: 12),

                TextButton(
                  onPressed: () {
                    // Navigate to FeesScreen and switch to history tab (index 1)
                    Navigator.pushAndRemoveUntil(
                      context,
                      MaterialPageRoute(builder: (_) => const MainNavigationScreen()),
                      (route) => false,
                    );
                    // Navigate to history logs by pushing navigation screen or triggering tab swap
                  },
                  child: const Text("View Payment History"),
                ),
              ],
            ),
          ),
          if (_isDownloading)
            Container(
              color: Colors.black26,
              child: const LoadingView(message: "Downloading fee receipt document..."),
            ),
        ],
      ),
    );
  }

  Widget _buildRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(color: Colors.grey, fontSize: 13)),
          Flexible(
            child: Text(
              value,
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
              textAlign: TextAlign.end,
            ),
          ),
        ],
      ),
    );
  }
}

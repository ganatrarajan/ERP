import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:razorpay_flutter/razorpay_flutter.dart';
import '../../../data/models/fee_model.dart';
import '../../../providers/auth_provider.dart';
import '../../../providers/dashboard_provider.dart';
import '../../../providers/fee_provider.dart';
import '../../../providers/receipt_provider.dart';
import '../../../data/models/receipt_model.dart';
import 'payment_success_screen.dart';
import 'payment_failed_screen.dart';

class FeeDetailsScreen extends StatefulWidget {
  final FeeInstallment installment;
  final bool onlinePaymentEnabled;

  const FeeDetailsScreen({
    super.key,
    required this.installment,
    required this.onlinePaymentEnabled,
  });

  @override
  State<FeeDetailsScreen> createState() => _FeeDetailsScreenState();
}

class _FeeDetailsScreenState extends State<FeeDetailsScreen> {
  late Razorpay _razorpay;
  bool _isLocalLoading = false;

  @override
  void initState() {
    super.initState();
    _razorpay = Razorpay();
    _razorpay.on(Razorpay.EVENT_PAYMENT_SUCCESS, _handlePaymentSuccess);
    _razorpay.on(Razorpay.EVENT_PAYMENT_ERROR, _handlePaymentError);
    _razorpay.on(Razorpay.EVENT_EXTERNAL_WALLET, _handleExternalWallet);
  }

  @override
  void dispose() {
    _razorpay.clear();
    super.dispose();
  }

  void _handlePaymentSuccess(PaymentSuccessResponse response) async {
    setState(() => _isLocalLoading = true);
    try {
      final feeProv = context.read<FeeProvider>();
      final result = await feeProv.verifyPayment(
        razorpayOrderId: response.orderId ?? '',
        razorpayPaymentId: response.paymentId ?? '',
        razorpaySignature: response.signature ?? '',
      );

      if (mounted) {
        // Refresh all providers
        await Future.wait([
          context.read<DashboardProvider>().fetchDashboardData(),
          context.read<FeeProvider>().fetchFees(),
          context.read<ReceiptProvider>().fetchReceipts(),
        ]);

        if (mounted) {
          Navigator.pushReplacement(
            context,
            MaterialPageRoute(
              builder: (_) => PaymentSuccessScreen(
                receiptNumber: result['receipt_number'] ?? 'N/A',
                transactionId: response.paymentId ?? 'N/A',
                amount: widget.installment.outstandingBalance,
                paymentMethod: 'Online',
                dateTime: DateTime.now().toString(),
                receiptId: result['receipt_id'],
              ),
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(
            builder: (_) => PaymentFailedScreen(
              reason: e.toString().replaceAll('Exception: ', ''),
              onRetry: _startPaymentFlow,
            ),
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isLocalLoading = false);
      }
    }
  }

  void _handlePaymentError(PaymentFailureResponse response) {
    if (mounted) {
      String message = response.message ?? 'Payment cancelled or failed';
      if (response.code == Razorpay.PAYMENT_CANCELLED) {
        message = 'Payment was cancelled';
      }
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => PaymentFailedScreen(
            reason: message,
            onRetry: _startPaymentFlow,
          ),
        ),
      );
    }
  }

  void _handleExternalWallet(ExternalWalletResponse response) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text("External wallet selected: ${response.walletName}")),
    );
  }

  void _startPaymentFlow() async {
    final feeProv = context.read<FeeProvider>();
    final authProv = context.read<AuthProvider>();
    final student = authProv.student;

    if (student == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Student session context not found.")),
      );
      return;
    }

    setState(() => _isLocalLoading = true);

    try {
      final orderData = await feeProv.createPaymentOrder(
        widget.installment.id,
        widget.installment.outstandingBalance,
      );

      final keyId = orderData['key_id'];
      final orderId = orderData['order_id'];
      final schoolName = orderData['school_name'] ?? 'School Fee Payment';

      final options = {
        'key': keyId,
        'amount': (widget.installment.outstandingBalance * 100).toInt(),
        'name': schoolName,
        'order_id': orderId,
        'description': 'Fee Installment: ${widget.installment.name}',
        'prefill': {
          'contact': student.parent?.fatherMobile ?? student.parent?.motherMobile ?? '',
          'email': student.parent?.fatherEmail ?? student.parent?.motherEmail ?? '',
          'name': student.fullName,
        },
        'timeout': 300, // in seconds
      };

      _razorpay.open(options);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text("Failed to initiate payment: ${e.toString()}"),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isLocalLoading = false);
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
    final grNo = student?.grNo ?? 'N/A';
    final admissionNo = student?.admissionNo ?? 'N/A';
    final className = student?.academicRecord?.classInfo?.name ?? 'N/A';
    final sectionName = student?.academicRecord?.sectionInfo?.name ?? 'N/A';

    final isPaid = widget.installment.status.toLowerCase() == 'paid';
    final isPartiallyPaid = widget.installment.status.toLowerCase() == 'partial';

    // Look for matching receipt
    ReceiptModel? matchingReceipt;
    try {
      matchingReceipt = receiptProv.receipts.firstWhere(
        (r) => r.installmentName.toLowerCase() == widget.installment.name.toLowerCase(),
      );
    } catch (_) {
      matchingReceipt = null;
    }

    final showPayNow = widget.onlinePaymentEnabled && !isPaid && widget.installment.outstandingBalance > 0;
    final showDownloadReceipt = (isPaid || isPartiallyPaid) && matchingReceipt != null;

    final progress = matchingReceipt != null ? receiptProv.getDownloadProgress(matchingReceipt.id) : 0.0;
    final isDownloaded = matchingReceipt != null ? receiptProv.isFileDownloaded(matchingReceipt.id) : false;

    return Scaffold(
      appBar: AppBar(
        title: const Text("Fee Details"),
      ),
      body: Stack(
        children: [
          SingleChildScrollView(
            padding: const EdgeInsets.all(16.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // School and Student Header
                Card(
                  margin: EdgeInsets.zero,
                  child: Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          schoolName,
                          style: theme.textTheme.titleMedium?.copyWith(
                            color: theme.colorScheme.primary,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          studentName,
                          style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 12),
                        const Divider(height: 1),
                        const SizedBox(height: 12),
                        _buildRow("GR No.", grNo),
                        _buildRow("Admission No.", admissionNo),
                        _buildRow("Class / Section", "$className - $sectionName"),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // Fee details Breakdown
                Card(
                  margin: EdgeInsets.zero,
                  child: Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          "Payment Information",
                          style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 16),
                        _buildRow("Fee Installment", widget.installment.name),
                        _buildRow("Due Date", widget.installment.dueDate),
                        _buildRow("Status", widget.installment.status.toUpperCase(), 
                          valueColor: isPaid 
                              ? Colors.green 
                              : (widget.installment.isOverdue ? Colors.red : Colors.orange)
                        ),
                        const SizedBox(height: 12),
                        const Divider(height: 1),
                        const SizedBox(height: 12),
                        _buildRow("Installment Amount", "₹${widget.installment.amount.toStringAsFixed(2)}"),
                        _buildRow("Paid Amount", "₹${widget.installment.paid.toStringAsFixed(2)}", valueColor: Colors.green),
                        if (widget.installment.discount > 0)
                          _buildRow("Discount", "-₹${widget.installment.discount.toStringAsFixed(2)}", valueColor: Colors.teal),
                        _buildRow("Late Fines", "₹${widget.installment.fineDue.toStringAsFixed(2)}", valueColor: Colors.red),
                        const SizedBox(height: 12),
                        const Divider(height: 1),
                        const SizedBox(height: 16),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              "Total Payable",
                              style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                            ),
                            Text(
                              "₹${widget.installment.outstandingBalance.toStringAsFixed(2)}",
                              style: theme.textTheme.titleLarge?.copyWith(
                                color: showPayNow ? theme.colorScheme.primary : Colors.green,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 32),

                // Pay Now Button
                if (showPayNow)
                  ElevatedButton(
                    onPressed: _isLocalLoading ? null : _startPaymentFlow,
                    child: const Text("Pay Now"),
                  ),

                // Download / View Receipt Button
                if (showDownloadReceipt) ...[
                  const SizedBox(height: 12),
                  if (progress > 0 && progress < 1.0)
                    const Center(child: CircularProgressIndicator())
                  else if (isDownloaded)
                    OutlinedButton.icon(
                      onPressed: () {
                        receiptProv.openReceipt(matchingReceipt!.id);
                      },
                      icon: const Icon(Icons.picture_as_pdf),
                      label: const Text("View PDF Receipt"),
                      style: OutlinedButton.styleFrom(
                        minimumSize: const Size.fromHeight(56),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                    )
                  else
                    OutlinedButton.icon(
                      onPressed: () async {
                        try {
                          await receiptProv.downloadReceipt(matchingReceipt!);
                        } catch (e) {
                          if (mounted) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(content: Text("Failed to download: $e")),
                            );
                          }
                        }
                      },
                      icon: const Icon(Icons.cloud_download),
                      label: const Text("Download Receipt"),
                      style: OutlinedButton.styleFrom(
                        minimumSize: const Size.fromHeight(56),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                    ),
                ],
              ],
            ),
          ),
          if (_isLocalLoading)
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
        children: [
          Text(label, style: const TextStyle(color: Colors.grey, fontSize: 14)),
          Text(
            value,
            style: TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 14,
              color: valueColor,
            ),
          ),
        ],
      ),
    );
  }
}

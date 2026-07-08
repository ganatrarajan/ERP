import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../providers/fee_provider.dart';
import '../../../data/models/payment_history_model.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/error_view.dart';
import '../../widgets/empty_view.dart';
import 'payment_details_screen.dart';

class PaymentHistoryScreen extends StatefulWidget {
  const PaymentHistoryScreen({super.key});

  @override
  State<PaymentHistoryScreen> createState() => _PaymentHistoryScreenState();
}

class _PaymentHistoryScreenState extends State<PaymentHistoryScreen> {
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<FeeProvider>().fetchPaymentHistory();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final feeProv = context.watch<FeeProvider>();

    return Scaffold(
      appBar: AppBar(
        title: const Text("Payment History"),
      ),
      body: Column(
        children: [
          // Search Bar
          Padding(
            padding: const EdgeInsets.all(12.0),
            child: TextField(
              controller: _searchController,
              decoration: InputDecoration(
                hintText: "Search by Receipt No or Inst. Name...",
                prefixIcon: const Icon(Icons.search_rounded),
                suffixIcon: _searchQuery.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear_rounded),
                        onPressed: () {
                          setState(() {
                            _searchController.clear();
                            _searchQuery = '';
                          });
                        },
                      )
                    : null,
                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              ),
              onChanged: (val) {
                setState(() {
                  _searchQuery = val.trim().toLowerCase();
                });
              },
            ),
          ),

          // Content
          Expanded(
            child: _buildContent(feeProv, theme),
          ),
        ],
      ),
    );
  }

  Widget _buildContent(FeeProvider prov, ThemeData theme) {
    if (prov.isLoading && prov.paymentHistory.isEmpty) {
      return const LoadingView(message: "Loading payment logs...");
    }

    if (prov.errorMessage != null && prov.paymentHistory.isEmpty) {
      return ErrorView(
        message: prov.errorMessage!,
        onRetry: () => prov.fetchPaymentHistory(),
      );
    }

    final List<PaymentHistoryModel> allLogs = prov.paymentHistory;
    
    // Perform search filtering locally
    final filteredLogs = allLogs.where((log) {
      final receiptMatch = log.receiptNo?.toLowerCase().contains(_searchQuery) ?? false;
      final instMatch = log.installmentName.toLowerCase().contains(_searchQuery);
      final paymentIdMatch = log.paymentId?.toLowerCase().contains(_searchQuery) ?? false;
      return receiptMatch || instMatch || paymentIdMatch;
    }).toList();

    if (filteredLogs.isEmpty) {
      return EmptyView(
        title: _searchQuery.isNotEmpty ? "No Matches Found" : "No Payments Recorded",
        description: _searchQuery.isNotEmpty
            ? "Try refining your search queries or clear input filter fields."
            : "All gateway-based transaction history logs will appear here.",
        icon: Icons.history_rounded,
      );
    }

    return RefreshIndicator(
      onRefresh: () => prov.fetchPaymentHistory(),
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: filteredLogs.length,
        separatorBuilder: (context, index) => const SizedBox(height: 12),
        itemBuilder: (context, index) {
          final log = filteredLogs[index];
          final isSuccess = log.status.toLowerCase() == 'successful';
          final isFailed = log.status.toLowerCase() == 'failed';

          return Card(
            margin: EdgeInsets.zero,
            child: ListTile(
              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              title: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Text(
                      log.installmentName,
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  Text(
                    "₹${log.amount.toStringAsFixed(2)}",
                    style: TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 15,
                      color: isSuccess ? Colors.green : (isFailed ? Colors.red : Colors.orange),
                    ),
                  ),
                ],
              ),
              subtitle: Padding(
                padding: const EdgeInsets.only(top: 6.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      log.receiptNo != null ? "Receipt: ${log.receiptNo}" : "TXN ID: ${log.paymentId ?? 'N/A'}",
                      style: const TextStyle(fontSize: 12),
                    ),
                    const SizedBox(height: 2),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          log.transactionDate,
                          style: const TextStyle(fontSize: 12, color: Colors.grey),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: (isSuccess
                                    ? Colors.green
                                    : isFailed
                                        ? Colors.red
                                        : Colors.orange)
                                .withOpacity(0.12),
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: Text(
                            log.status.toUpperCase(),
                            style: TextStyle(
                              color: isSuccess ? Colors.green : (isFailed ? Colors.red : Colors.orange),
                              fontSize: 9,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => PaymentDetailsScreen(transaction: log),
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}

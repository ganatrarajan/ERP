import 'package:flutter/material.dart';

class PaymentFailedScreen extends StatelessWidget {
  final String reason;
  final VoidCallback onRetry;

  const PaymentFailedScreen({
    super.key,
    required this.reason,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text("Payment Failed"),
        automaticallyImplyLeading: false,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 48.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Error icon
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: Colors.red.withOpacity(0.12),
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Icons.error_outline_rounded,
                color: Colors.red,
                size: 96,
              ),
            ),
            const SizedBox(height: 24),

            Text(
              "Payment Failed",
              style: theme.textTheme.headlineMedium?.copyWith(
                fontWeight: FontWeight.w800,
                color: Colors.red,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
            
            Text(
              "Reason:",
              style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 6),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: theme.colorScheme.error.withOpacity(0.05),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: theme.colorScheme.error.withOpacity(0.15)),
              ),
              child: Text(
                reason,
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: Colors.red[800],
                  fontWeight: FontWeight.w600,
                ),
                textAlign: TextAlign.center,
              ),
            ),
            const SizedBox(height: 16),
            Text(
              "No amount was collected. The fee installment remains unpaid.",
              style: theme.textTheme.bodySmall?.copyWith(color: Colors.grey),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 48),

            // Buttons
            ElevatedButton(
              onPressed: () {
                Navigator.pop(context);
                onRetry();
              },
              child: const Text("Retry Payment"),
            ),
            const SizedBox(height: 12),

            OutlinedButton(
              onPressed: () {
                Navigator.pop(context);
              },
              style: OutlinedButton.styleFrom(
                minimumSize: const Size.fromHeight(56),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              child: const Text("Back"),
            ),
          ],
        ),
      ),
    );
  }
}

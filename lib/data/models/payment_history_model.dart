class PaymentHistoryModel {
  final int id;
  final String installmentName;
  final double amount;
  final String status; // pending, successful, failed
  final String? receiptNo;
  final String transactionDate;
  final String? paymentId;

  PaymentHistoryModel({
    required this.id,
    required this.installmentName,
    required this.amount,
    required this.status,
    this.receiptNo,
    required this.transactionDate,
    this.paymentId,
  });

  factory PaymentHistoryModel.fromJson(Map<String, dynamic> json) {
    return PaymentHistoryModel(
      id: json['id'] ?? 0,
      installmentName: json['installment_name'] ?? 'Fee Installment',
      amount: (json['amount'] as num?)?.toDouble() ?? 0.0,
      status: json['status'] ?? 'pending',
      receiptNo: json['receipt_no'],
      transactionDate: json['transaction_date'] ?? '',
      paymentId: json['payment_id'],
    );
  }
}

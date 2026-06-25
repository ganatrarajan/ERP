class ReceiptModel {
  final int id;
  final String receiptNumber;
  final double amountPaid;
  final String paymentDate;
  final String installmentName;
  final String pdfUrl;

  ReceiptModel({
    required this.id,
    required this.receiptNumber,
    required this.amountPaid,
    required this.paymentDate,
    required this.installmentName,
    required this.pdfUrl,
  });

  factory ReceiptModel.fromJson(Map<String, dynamic> json) {
    return ReceiptModel(
      id: json['id'] ?? 0,
      receiptNumber: json['receipt_number'] ?? '',
      amountPaid: (json['amount_paid'] as num?)?.toDouble() ?? 0.0,
      paymentDate: json['payment_date'] ?? '',
      installmentName: json['installment_name'] ?? '',
      pdfUrl: json['pdf_url'] ?? '',
    );
  }
}

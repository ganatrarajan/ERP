class FeeResponse {
  final double totalFees;
  final double paidFees;
  final double pendingFees;
  final List<FeeInstallment> installments;

  FeeResponse({
    required this.totalFees,
    required this.paidFees,
    required this.pendingFees,
    required this.installments,
  });

  factory FeeResponse.fromJson(Map<String, dynamic> json) {
    final list = json['installment_details'] as List? ?? [];
    return FeeResponse(
      totalFees: (json['total_fees'] as num?)?.toDouble() ?? 0.0,
      paidFees: (json['paid_fees'] as num?)?.toDouble() ?? 0.0,
      pendingFees: (json['pending_fees'] as num?)?.toDouble() ?? 0.0,
      installments: list.map((e) => FeeInstallment.fromJson(e)).toList(),
    );
  }
}

class FeeInstallment {
  final int id;
  final String name;
  final String dueDate;
  final bool isOverdue;
  final int overdueDays;
  final double amount;
  final double paid;
  final double discount;
  final double finePaid;
  final double fineDue;
  final double remainingDue;
  final double outstandingBalance;
  final String status; // Paid, Unpaid, Partially Paid

  FeeInstallment({
    required this.id,
    required this.name,
    required this.dueDate,
    required this.isOverdue,
    required this.overdueDays,
    required this.amount,
    required this.paid,
    required this.discount,
    required this.finePaid,
    required this.fineDue,
    required this.remainingDue,
    required this.outstandingBalance,
    required this.status,
  });

  factory FeeInstallment.fromJson(Map<String, dynamic> json) {
    return FeeInstallment(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      dueDate: json['due_date'] ?? '',
      isOverdue: json['is_overdue'] ?? false,
      overdueDays: json['overdue_days'] ?? 0,
      amount: (json['amount'] as num?)?.toDouble() ?? 0.0,
      paid: (json['paid'] as num?)?.toDouble() ?? 0.0,
      discount: (json['discount'] as num?)?.toDouble() ?? 0.0,
      finePaid: (json['fine_paid'] as num?)?.toDouble() ?? 0.0,
      fineDue: (json['fine_due'] as num?)?.toDouble() ?? 0.0,
      remainingDue: (json['remaining_due'] as num?)?.toDouble() ?? 0.0,
      outstandingBalance: (json['outstanding_balance'] as num?)?.toDouble() ?? 0.0,
      status: json['status'] ?? 'Unpaid',
    );
  }
}

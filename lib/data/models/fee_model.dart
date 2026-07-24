class FeeResponse {
  final double totalFees;
  final double paidFees;
  final double pendingFees;
  final List<FeeInstallment> installments;
  final bool onlinePaymentEnabled;

  FeeResponse({
    required this.totalFees,
    required this.paidFees,
    required this.pendingFees,
    required this.installments,
    required this.onlinePaymentEnabled,
  });

  factory FeeResponse.fromJson(Map<String, dynamic> json) {
    final list = json['installment_details'] as List? ?? [];
    final rawOnline = json['online_payment_enabled'];
    // Flexible parsing for bool, int (1/0), or String ("1"/"true")
    final isOnlineEnabled = rawOnline == true || 
                            rawOnline == 1 || 
                            rawOnline == '1' || 
                            rawOnline == 'true' || 
                            rawOnline == null; // Default to true if not specified

    return FeeResponse(
      totalFees: (json['total_fees'] as num?)?.toDouble() ?? 0.0,
      paidFees: (json['paid_fees'] as num?)?.toDouble() ?? 0.0,
      pendingFees: (json['pending_fees'] as num?)?.toDouble() ?? 0.0,
      installments: list.map((e) => FeeInstallment.fromJson(e)).toList(),
      onlinePaymentEnabled: isOnlineEnabled,
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

  /// Returns the effective outstanding balance to pay.
  /// If [outstandingBalance] or [remainingDue] is > 0, it uses that.
  /// Otherwise, it calculates (amount - paid - discount + fineDue).
  double get effectiveBalance {
    if (outstandingBalance > 0) return outstandingBalance;
    if (remainingDue > 0) return remainingDue;
    final calc = (amount - paid - discount + fineDue);
    return calc > 0 ? calc : 0.0;
  }

  factory FeeInstallment.fromJson(Map<String, dynamic> json) {
    final amt = (json['amount'] as num?)?.toDouble() ?? 0.0;
    final pd = (json['paid'] as num?)?.toDouble() ?? 0.0;
    final disc = (json['discount'] as num?)?.toDouble() ?? 0.0;
    final fDue = (json['fine_due'] as num?)?.toDouble() ?? 0.0;

    final remDue = (json['remaining_due'] as num?)?.toDouble() ?? (json['remaining_amount'] as num?)?.toDouble() ?? 0.0;
    final outBal = (json['outstanding_balance'] as num?)?.toDouble() ?? (json['pending_amount'] as num?)?.toDouble() ?? 0.0;

    return FeeInstallment(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      dueDate: json['due_date'] ?? '',
      isOverdue: json['is_overdue'] ?? false,
      overdueDays: json['overdue_days'] ?? 0,
      amount: amt,
      paid: pd,
      discount: disc,
      finePaid: (json['fine_paid'] as num?)?.toDouble() ?? 0.0,
      fineDue: fDue,
      remainingDue: remDue > 0 ? remDue : ((amt - pd - disc + fDue) > 0 ? (amt - pd - disc + fDue) : 0.0),
      outstandingBalance: outBal > 0 ? outBal : ((amt - pd - disc + fDue) > 0 ? (amt - pd - disc + fDue) : 0.0),
      status: json['status'] ?? (pd >= amt && amt > 0 ? 'Paid' : 'Unpaid'),
    );
  }
}

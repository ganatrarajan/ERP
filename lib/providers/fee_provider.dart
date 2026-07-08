import 'package:flutter/material.dart';
import '../data/models/fee_model.dart';
import '../data/models/payment_history_model.dart';
import '../data/repositories/student_repository.dart';

class FeeProvider extends ChangeNotifier {
  final StudentRepository _studentRepository = StudentRepository();

  FeeResponse? _feeResponse;
  bool _isLoading = false;
  String? _errorMessage;
  bool _isProcessingPayment = false;

  FeeResponse? get feeResponse => _feeResponse;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  bool get isProcessingPayment => _isProcessingPayment;

  Future<void> fetchFees() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _feeResponse = await _studentRepository.getFees();
    } catch (e) {
      _errorMessage = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>> createPaymentOrder(int installmentId, double amount) async {
    _isProcessingPayment = true;
    notifyListeners();
    try {
      final orderData = await _studentRepository.createPaymentOrder(installmentId, amount);
      return orderData;
    } finally {
      _isProcessingPayment = false;
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>> verifyPayment({
    required String razorpayOrderId,
    required String razorpayPaymentId,
    required String razorpaySignature,
  }) async {
    _isProcessingPayment = true;
    notifyListeners();
    try {
      final verifyData = await _studentRepository.verifyPayment(
        razorpayOrderId: razorpayOrderId,
        razorpayPaymentId: razorpayPaymentId,
        razorpaySignature: razorpaySignature,
      );
      return verifyData;
    } finally {
      _isProcessingPayment = false;
      notifyListeners();
    }
  }

  List<PaymentHistoryModel> _paymentHistory = [];
  List<PaymentHistoryModel> get paymentHistory => _paymentHistory;

  Future<void> fetchPaymentHistory() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _paymentHistory = await _studentRepository.getPaymentHistory();
    } catch (e) {
      _errorMessage = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
}

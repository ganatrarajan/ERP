import 'package:flutter/material.dart';
import '../data/models/receipt_model.dart';
import '../data/repositories/student_repository.dart';
import '../core/services/download_service.dart';

class ReceiptProvider extends ChangeNotifier {
  final StudentRepository _studentRepository = StudentRepository();
  final DownloadService _downloadService = DownloadService();

  List<ReceiptModel> _receipts = [];
  bool _isLoading = false;
  String? _errorMessage;

  // Track downloading receipt IDs and progress
  final Map<int, double> _downloadProgress = {};
  final Map<int, bool> _isDownloaded = {};

  List<ReceiptModel> get receipts => _receipts;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  double getDownloadProgress(int receiptId) => _downloadProgress[receiptId] ?? 0.0;
  bool isFileDownloaded(int receiptId) => _isDownloaded[receiptId] ?? false;

  Future<void> fetchReceipts() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _receipts = await _studentRepository.getReceipts();
      await checkLocalDownloads();
    } catch (e) {
      _errorMessage = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Pre-check which PDF files are already saved locally
  Future<void> checkLocalDownloads() async {
    for (var receipt in _receipts) {
      final filename = 'receipt_${receipt.id}.pdf';
      _isDownloaded[receipt.id] = await _downloadService.isFileDownloaded(filename);
    }
    notifyListeners();
  }

  // Fetch download URL and download PDF
  Future<String?> downloadReceipt(ReceiptModel receipt) async {
    final receiptId = receipt.id;
    _downloadProgress[receiptId] = 0.05; // initial state
    notifyListeners();

    try {
      // 1. Get official download link
      final directDownloadUrl = await _studentRepository.getReceiptPdfUrl(receiptId);
      final filename = 'receipt_$receiptId.pdf';

      // 2. Download the file
      final path = await _downloadService.downloadFile(
        url: directDownloadUrl,
        filename: filename,
        onProgress: (received, total) {
          if (total > 0) {
            _downloadProgress[receiptId] = received / total;
            notifyListeners();
          }
        },
      );

      _isDownloaded[receiptId] = true;
      _downloadProgress[receiptId] = 1.0;
      notifyListeners();
      return path;
    } catch (e) {
      _downloadProgress.remove(receiptId);
      notifyListeners();
      rethrow;
    }
  }

  // Action: Open
  Future<void> openReceipt(int receiptId) async {
    final filename = 'receipt_$receiptId.pdf';
    await _downloadService.openDownloadedFile(filename);
  }

  // Action: Share
  Future<void> shareReceipt(int receiptId, String docNum) async {
    final filename = 'receipt_$receiptId.pdf';
    await _downloadService.shareDownloadedFile(filename, "Payment Receipt $docNum");
  }

  // Action: Export
  Future<void> exportReceipt(int receiptId) async {
    final filename = 'receipt_$receiptId.pdf';
    await _downloadService.exportToPublicDownloads(filename);
  }
}

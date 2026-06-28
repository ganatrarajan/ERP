import 'package:flutter/material.dart';
import '../data/models/report_card_model.dart';
import '../data/repositories/student_repository.dart';
import '../core/services/download_service.dart';

class ReportCardProvider extends ChangeNotifier {
  final StudentRepository _studentRepository = StudentRepository();
  final DownloadService _downloadService = DownloadService();

  List<ReportCardModel> _reportCards = [];
  bool _isLoading = false;
  String? _errorMessage;

  // Track downloading report card IDs and progress
  final Map<int, double> _downloadProgress = {};
  final Map<int, bool> _isDownloaded = {};

  List<ReportCardModel> get reportCards => _reportCards;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  double getDownloadProgress(int examId) => _downloadProgress[examId] ?? 0.0;
  bool isFileDownloaded(int examId) => false;

  Future<void> fetchReportCards() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _reportCards = await _studentRepository.getReportCards();
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
    _isDownloaded.clear();
    notifyListeners();
  }

  // Fetch download URL and download PDF
  Future<String?> downloadReportCard(ReportCardModel reportCard) async {
    final examId = reportCard.id;
    _downloadProgress[examId] = 0.05; // initial state
    notifyListeners();

    try {
      // 1. Get official download link
      final directDownloadUrl = await _studentRepository.getReportCardPdfUrl(examId);
      final filename = 'report_card_$examId.pdf';

      // 2. Download the file
      final path = await _downloadService.downloadFile(
        url: directDownloadUrl,
        filename: filename,
        onProgress: (received, total) {
          if (total > 0) {
            _downloadProgress[examId] = received / total;
            notifyListeners();
          }
        },
      );

      _isDownloaded[examId] = true;
      _downloadProgress[examId] = 1.0;
      notifyListeners();
      return path;
    } catch (e) {
      _downloadProgress.remove(examId);
      notifyListeners();
      rethrow;
    }
  }

  // Action: Open
  Future<void> openReportCard(int examId) async {
    final filename = 'report_card_$examId.pdf';
    await _downloadService.openDownloadedFile(filename);
  }

  // Action: Share
  Future<void> shareReportCard(int examId, String examName) async {
    final filename = 'report_card_$examId.pdf';
    await _downloadService.shareDownloadedFile(filename, "Report Card - $examName");
  }

  // Action: Export
  Future<void> exportReportCard(int examId) async {
    final filename = 'report_card_$examId.pdf';
    await _downloadService.exportToPublicDownloads(filename);
  }
}

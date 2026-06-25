import 'package:flutter/material.dart';
import 'package:flutter_pdfview/flutter_pdfview.dart';
import '../../../core/services/download_service.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/error_view.dart';

class PdfViewerScreen extends StatefulWidget {
  final String title;
  final String filename;

  const PdfViewerScreen({
    super.key,
    required this.title,
    required this.filename,
  });

  @override
  State<PdfViewerScreen> createState() => _PdfViewerScreenState();
}

class _PdfViewerScreenState extends State<PdfViewerScreen> {
  final DownloadService _downloadService = DownloadService();
  
  bool _isLoading = true;
  String? _pdfPath;
  String? _error;
  
  int _totalPages = 0;
  int _currentPage = 0;
  bool _isReady = false;

  @override
  void initState() {
    super.initState();
    _loadPdf();
  }

  Future<void> _loadPdf() async {
    try {
      final file = await _downloadService.getLocalFile(widget.filename);
      if (await file.exists()) {
        setState(() {
          _pdfPath = file.path;
          _isLoading = false;
        });
      } else {
        setState(() {
          _error = "File not found locally. Please download again.";
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = "Failed to load PDF viewer: ${e.toString()}";
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.title),
        actions: [
          if (_isReady && _totalPages > 0)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16.0),
              child: Center(
                child: Text(
                  "Page ${_currentPage + 1} / $_totalPages",
                  style: const TextStyle(fontWeight: FontWeight.bold),
                ),
              ),
            ),
        ],
      ),
      body: _buildBody(theme),
    );
  }

  Widget _buildBody(ThemeData theme) {
    if (_isLoading) {
      return const LoadingView(message: "Rendering document...");
    }

    if (_error != null) {
      return ErrorView(message: _error!);
    }

    if (_pdfPath == null) {
      return const Center(child: Text("Document rendering error"));
    }

    return Stack(
      children: [
        PDFView(
          filePath: _pdfPath,
          enableSwipe: true,
          swipeHorizontal: false,
          autoSpacing: true,
          pageFling: true,
          pageSnap: true,
          defaultPage: _currentPage,
          fitPolicy: FitPolicy.WIDTH,
          preventLinkNavigation: false,
          onRender: (pages) {
            setState(() {
              _totalPages = pages ?? 0;
              _isReady = true;
            });
          },
          onError: (error) {
            setState(() {
              _error = error.toString();
            });
          },
          onPageError: (page, error) {
            setState(() {
              _error = "Error rendering page $page: $error";
            });
          },
          onPageChanged: (page, total) {
            setState(() {
              _currentPage = page ?? 0;
            });
          },
        ),
        if (!_isReady)
          const LoadingView(message: "Initializing PDF view..."),
      ],
    );
  }
}

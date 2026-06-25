import 'dart:io';
import 'package:flutter/material.dart';
import '../../../core/services/download_service.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/error_view.dart';

class ImageViewerScreen extends StatefulWidget {
  final String title;
  final String filename;

  const ImageViewerScreen({
    super.key,
    required this.title,
    required this.filename,
  });

  @override
  State<ImageViewerScreen> createState() => _ImageViewerScreenState();
}

class _ImageViewerScreenState extends State<ImageViewerScreen> {
  final DownloadService _downloadService = DownloadService();
  
  bool _isLoading = true;
  File? _imageFile;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadImage();
  }

  Future<void> _loadImage() async {
    try {
      final file = await _downloadService.getLocalFile(widget.filename);
      if (await file.exists()) {
        setState(() {
          _imageFile = file;
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
        _error = "Failed to load image viewer: ${e.toString()}";
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
        title: Text(widget.title),
        actions: [
          IconButton(
            icon: const Icon(Icons.share_rounded),
            tooltip: "Share Image",
            onPressed: () async {
              final messenger = ScaffoldMessenger.of(context);
              try {
                await _downloadService.shareDownloadedFile(widget.filename, widget.title);
              } catch (e) {
                messenger.showSnackBar(
                  SnackBar(
                    content: Text("Failed to share: ${e.toString()}"),
                    backgroundColor: Colors.red,
                  ),
                );
              }
            },
          ),
          IconButton(
            icon: const Icon(Icons.save_alt_rounded),
            tooltip: "Save to Downloads",
            onPressed: () async {
              final messenger = ScaffoldMessenger.of(context);
              try {
                await _downloadService.exportToPublicDownloads(widget.filename);
                messenger.showSnackBar(
                  const SnackBar(
                    content: Text("Saved to Downloads folder"),
                    backgroundColor: Colors.green,
                  ),
                );
              } catch (e) {
                messenger.showSnackBar(
                  SnackBar(
                    content: Text("Failed to save: ${e.toString()}"),
                    backgroundColor: Colors.red,
                  ),
                );
              }
            },
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(
        child: LoadingView(message: "Loading image..."),
      );
    }

    if (_error != null) {
      return Center(
        child: ErrorView(message: _error!),
      );
    }

    if (_imageFile == null) {
      return const Center(
        child: Text(
          "Image rendering error",
          style: TextStyle(color: Colors.white),
        ),
      );
    }

    return Center(
      child: InteractiveViewer(
        minScale: 0.5,
        maxScale: 4.0,
        child: Image.file(
          _imageFile!,
          fit: BoxFit.contain,
        ),
      ),
    );
  }
}
